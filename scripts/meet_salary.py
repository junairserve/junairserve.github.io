# -*- coding: utf-8 -*-
"""Utility for generating payroll Excel and PDF summaries from meet.xlsx.

This script replicates the behaviour of the standalone payroll automation
provided by the operations team.  It reads an input Excel file, enriches the
resulting dataframe with derived totals, exports a per-staff Excel workbook,
and generates per-staff PDF statements using ReportLab.

Usage
-----
Adjust the path constants in the configuration section to match your
environment before running the script:

    python scripts/meet_salary.py
"""

from __future__ import annotations

import decimal
import os
import re
from datetime import date, datetime, time, timedelta

import pandas as pd
from reportlab.lib import colors
from reportlab.lib.pagesizes import landscape, letter
from reportlab.lib.styles import ParagraphStyle, getSampleStyleSheet
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.platypus import (
    Paragraph,
    SimpleDocTemplate,
    Spacer,
    Table,
    TableStyle,
)

# --- 設定エリア ---
file_path = r"C:\\Users\\user\\Desktop\\Mypython\\meet.xlsx"  # 入力元 Excel ファイル
output_excel = r"C:\\Users\\user\\Desktop\\Mypython\\雑給.xlsx"  # 出力先 Excel ファイル
output_dir = r"C:\\Users\\user\\Desktop\\Mypython\\attachments"  # PDF 出力先ディレクトリ
font_path = r"C:\\Windows\\Fonts\\msmincho.ttc"  # 日本語フォント (例: MS 明朝)


def _register_font() -> None:
    os.makedirs(output_dir, exist_ok=True)
    try:
        pdfmetrics.registerFont(TTFont("JapaneseFont", font_path))
        print("● PDF用日本語フォントを登録しました。")
    except Exception as exc:  # pragma: no cover - runtime logging only
        print(f"[警告] PDF用フォントの登録に失敗: {exc}")
        print("→ PDFで日本語が文字化けする可能性があります。")


def _load_dataframe() -> pd.DataFrame:
    try:
        df_local = pd.read_excel(file_path)
        print(f"● 元データを読み込みました (shape={df_local.shape})")
        print("  列名一覧:", df_local.columns.tolist())
        if df_local.empty:
            print("  [警告] 読み込んだ DataFrame が空です。")
        return df_local
    except FileNotFoundError:  # pragma: no cover - runtime logging only
        print(f"[エラー] ファイルが見つかりません: {file_path}")
        raise SystemExit() from None
    except Exception as exc:  # pragma: no cover - runtime logging only
        print(f"[エラー] Excel の読み込み中に例外が発生しました: {exc}")
        raise SystemExit() from exc


def _sum_tatekae(row: pd.Series) -> float:
    total = 0.0
    for col in ["立替金1", "立替金2", "立替金3"]:
        if col in row:
            try:
                val = pd.to_numeric(row[col], errors="coerce")
                if pd.notnull(val):
                    total += float(val)
            except Exception:  # pragma: no cover - robust parsing
                pass
    return total


def _sum_column(row: pd.Series, col: str) -> float:
    if col in row:
        try:
            val = pd.to_numeric(row[col], errors="coerce")
            if pd.notnull(val):
                return float(val)
        except Exception:  # pragma: no cover - robust parsing
            pass
    return 0.0


def _apply_totals(df_local: pd.DataFrame) -> pd.DataFrame:
    df_local["立替金合計"] = df_local.apply(_sum_tatekae, axis=1)
    df_local["交通費合計"] = df_local.apply(lambda r: _sum_column(r, "交通費"), axis=1)
    df_local["同行交通費合計"] = df_local.apply(lambda r: _sum_column(r, "同行交通費"), axis=1)
    df_local["通信費合計"] = df_local.apply(lambda r: _sum_column(r, "通信費"), axis=1)
    print("\n● 合計列（立替金は1+2+3、他は単独カラム）を追加しました。")
    return df_local


def _safe_to_time(val):
    if pd.isnull(val):
        return None
    if isinstance(val, time):
        return val
    if isinstance(val, datetime):
        return val.time()
    try:
        return pd.to_datetime(val).time()
    except Exception:  # pragma: no cover - robust parsing
        return None


def _calculate_duration(row: pd.Series) -> float:
    st = row.get("勤務開始時間")
    et = row.get("退勤時間")
    if not isinstance(st, time) or not isinstance(et, time):
        return float("nan")
    sd = datetime.combine(date.today(), st)
    ed = datetime.combine(date.today(), et)
    if ed < sd:
        ed += timedelta(days=1)
    return (ed - sd).total_seconds() / 60.0


def _calculate_late_night_minutes(sdt: datetime, edt: datetime) -> int:
    late_start = time(22, 0, 0)
    late_end = time(5, 0, 0)

    def is_late_night(t_obj: time) -> bool:
        return (t_obj >= late_start) or (t_obj < late_end)

    total = 0
    cur = sdt
    count = 0
    while cur < edt and count < 48 * 60:  # 無限ループ防止
        if is_late_night(cur.time()):
            total += 1
        cur += timedelta(minutes=1)
        count += 1
    return total


def _custom_round(x):
    decimal.getcontext().rounding = decimal.ROUND_HALF_UP
    try:
        if pd.isnull(x):
            return x
        return float(decimal.Decimal(str(x)).to_integral_value())
    except Exception:  # pragma: no cover - robust parsing
        return x


CHARGE_TYPE_TO_BASE_MIN = {
    1500: 120,
    3000: 90,
    4000: 120,
    5000: 150,
    6000: 240,
    7500: 300,
    9000: 360,
    10500: 420,
    12000: 480,
}
ADD_CHARGE_PER_MIN = 25.0
LATE_CHARGE_PER_MIN = 6.25
LANG_ADD = 1000.0


def _calculate_total_charge(row: pd.Series) -> float:
    st = row.get("勤務開始時間")
    et = row.get("退勤時間")
    pay = row.get("業務お支払い", 0)
    if (not isinstance(st, time)) or (not isinstance(et, time)):
        return float("nan")
    try:
        pay_f = float(pay)
    except Exception:
        pay_f = 0.0

    sd = datetime.combine(date.today(), st)
    ed = datetime.combine(date.today(), et)
    if ed < sd:
        ed += timedelta(days=1)

    total_min = (ed - sd).total_seconds() / 60.0
    base_min = CHARGE_TYPE_TO_BASE_MIN.get(pay_f, 0)
    extra_min = max(0.0, total_min - base_min)
    late_min = _calculate_late_night_minutes(sd, ed)

    amt = pay_f + extra_min * ADD_CHARGE_PER_MIN + late_min * LATE_CHARGE_PER_MIN

    lang = str(row.get("言語", "")).strip()
    if lang and (lang != "英語"):
        amt += LANG_ADD
    if row.get("返送確認", False):
        amt += 750.0
    if row.get("研修", False):
        amt += 750.0

    return _custom_round(amt)


def _format_yen(value):
    if pd.isnull(value) or not isinstance(value, (int, float, decimal.Decimal, pd.Int64Dtype)):
        return "-"
    try:
        if value == 0:
            return "¥0"
        return f"¥{int(value):,}"
    except (ValueError, TypeError):  # pragma: no cover - robust formatting
        return "-"


DROP_COLS = [
    "エアサーブからの連絡",
    "指示書添付",
    "看板添付",
    "その他添付",
    "確認済み",
    "スタンバイ済み",
    "業務終了",
    "ｴｱｻｰﾌﾞ確認済",
    "列車座席情報",
    "車両情報",
    "プッシュ通知",
    "メール送信 (ユーザー)",
]


REMARK_TEXT = (
    "備考：<br/>"
    "・ 支給額には、基本給・延長・深夜・語学・研修・返送作業費等が含まれます。<br/>"
    "・ 経費・立替金は実費精算分です。"
)


def main() -> None:  # pragma: no cover - heavy I/O
    _register_font()
    df = _load_dataframe()

    df = _apply_totals(df)

    if "勤務開始時間" in df.columns and "退勤時間" in df.columns:
        df["勤務開始時間"] = df["勤務開始時間"].apply(_safe_to_time)
        df["退勤時間"] = df["退勤時間"].apply(_safe_to_time)
        print("\n● 勤務/退勤時間を time オブジェクトに変換しました。")
    else:
        print("[警告] '勤務開始時間' または '退勤時間' の列が見つかりません。")

    if (not df.empty) and ("勤務開始時間" in df.columns) and ("退勤時間" in df.columns):
        df["所要時間"] = df.apply(_calculate_duration, axis=1)
        print("\n● 所要時間を計算しました。")
    else:
        df["所要時間"] = pd.Series(dtype="float64")

    if "追加手当" not in df.columns:
        df["追加手当"] = 0
    else:
        df["追加手当"] = pd.to_numeric(df["追加手当"], errors="coerce").fillna(0)

    df["支給額"] = df.apply(_calculate_total_charge, axis=1)
    df["合計金額"] = df["支給額"] + df["追加手当"]
    print("\n● 業務ごとの合計金額（追加手当含む）を計算しました。")

    try:
        with pd.ExcelWriter(output_excel, engine="xlsxwriter") as writer:
            if ("スタッフ" in df.columns) and (not df["スタッフ"].isnull().all()):
                for staff in df["スタッフ"].dropna().unique():
                    if str(staff).strip() == "":
                        continue
                    tmp = df[df["スタッフ"] == staff].copy()
                    tmp = tmp.drop(columns=DROP_COLS, errors="ignore")
                    sheet = re.sub(r"[\\/*?:\"<>|]", "_", str(staff))[:30]
                    tmp.to_excel(writer, sheet_name=sheet, index=False)

                sum_cols = [
                    "合計金額",
                    "支給額",
                    "追加手当",
                    "立替金合計",
                    "交通費合計",
                    "同行交通費合計",
                    "通信費合計",
                ]
                exist_sum = [c for c in sum_cols if c in df.columns]
                if exist_sum:
                    grouped = df.groupby("スタッフ")[exist_sum].sum().reset_index()
                    grouped.to_excel(writer, sheet_name="合計", index=False)
            else:
                tmp = df.drop(columns=DROP_COLS, errors="ignore")
                tmp.to_excel(writer, sheet_name="全データ", index=False)
        print(f"\n● Excelファイル を出力しました: {output_excel}")
    except Exception as exc:  # pragma: no cover - runtime logging only
        print(f"[エラー] Excel出力中に例外が発生しました: {exc}")

    styles = getSampleStyleSheet()
    try:
        title_style = ParagraphStyle(
            "TitleStyle", fontName="JapaneseFont", fontSize=16, alignment=0
        )
        normal_style = ParagraphStyle("NormalStyle", fontName="JapaneseFont", fontSize=10)
        remark_style = ParagraphStyle(
            "RemarkStyle", fontName="JapaneseFont", fontSize=9, textColor=colors.grey
        )
    except Exception as exc:  # pragma: no cover - runtime fallback
        print(f"[警告] ParagraphStyle のカスタム定義に失敗: {exc} → 代替スタイルを使用します。")
        title_style = styles["h2"]
        title_style.fontName = "JapaneseFont"
        normal_style = styles["Normal"]
        normal_style.fontName = "JapaneseFont"
        remark_style = styles["Normal"].clone(
            "RemarkStyle", fontName="JapaneseFont", fontSize=9, textColor=colors.grey
        )

    if ("スタッフ" in df.columns) and (not df["スタッフ"].isnull().all()):
        for staff in df["スタッフ"].dropna().unique():
            if str(staff).strip() == "":
                continue

            person_df = df[df["スタッフ"] == staff].copy()
            safe_staff_name = re.sub(r"[\\/*?:\"<>|]", "_", str(staff))
            pdf_path = os.path.join(output_dir, f"{safe_staff_name}.pdf")
            elements = []

            title = Paragraph(f"{staff} 様　給与明細", title_style)
            issue_date_str = f"発行日: {datetime.now().strftime('%Y年%m月%d日')}"
            issue_date_p = Paragraph(issue_date_str, normal_style)
            header_table = Table(
                [[title, issue_date_p]],
                colWidths=["75%", "25%"],
                style=[
                    ("VALIGN", (0, 0), (-1, -1), "TOP"),
                    ("ALIGN", (1, 0), (1, 0), "RIGHT"),
                ],
            )
            elements.append(header_table)
            elements.append(Spacer(1, 24))

            pdf_cols_map = {
                "日時": "業務日",
                "集合場所": "集合場所",
                "勤務開始時間": "開始",
                "退勤時間": "終了",
                "支給額": "支給額",
                "追加手当": "追加手当",
                "立替金合計": "立替金",
                "同行交通費合計": "同行交通費",
                "交通費合計": "交通費",
                "通信費合計": "通信費",
            }
            pdf_cols, pdf_headers = list(pdf_cols_map.keys()), list(pdf_cols_map.values())
            for col in pdf_cols:
                if col not in person_df.columns:
                    person_df[col] = pd.NA

            display_df = person_df[pdf_cols].copy()
            weekdays = ["月", "火", "水", "木", "金", "土", "日"]
            display_df["日時"] = pd.to_datetime(display_df["日時"], errors="coerce").apply(
                lambda x: x.strftime("%m/%d") + f"({weekdays[x.weekday()]})" if pd.notnull(x) else "-"
            )
            display_df["勤務開始時間"] = display_df["勤務開始時間"].apply(
                lambda x: x.strftime("%H:%M") if isinstance(x, time) else "-"
            )
            display_df["退勤時間"] = display_df["退勤時間"].apply(
                lambda x: x.strftime("%H:%M") if isinstance(x, time) else "-"
            )

            money_cols = [
                "支給額",
                "追加手当",
                "立替金合計",
                "同行交通費合計",
                "交通費合計",
                "通信費合計",
            ]
            for col in money_cols:
                if col in display_df.columns:
                    display_df[col] = person_df[col].apply(_format_yen)

            table_data = [pdf_headers] + display_df.fillna("-").values.tolist()
            tbl = Table(
                table_data,
                colWidths=[60, 120, 45, 45, 75, 75, 75, 75, 75, 75],
                repeatRows=1,
            )
            tbl_style = TableStyle(
                [
                    ("FONTNAME", (0, 0), (-1, -1), "JapaneseFont"),
                    ("FONTSIZE", (0, 0), (-1, -1), 9),
                    ("VALIGN", (0, 0), (-1, -1), "MIDDLE"),
                    ("GRID", (0, 0), (-1, -1), 0.5, colors.darkgrey),
                    ("BACKGROUND", (0, 0), (-1, 0), colors.HexColor("#4F81BD")),
                    ("TEXTCOLOR", (0, 0), (-1, 0), colors.whitesmoke),
                    ("ALIGN", (0, 0), (-1, 0), "CENTER"),
                    ("TOPPADDING", (0, 0), (-1, 0), 8),
                    ("BOTTOMPADDING", (0, 0), (-1, 0), 8),
                    ("ALIGN", (0, 1), (3, -1), "CENTER"),
                    ("ALIGN", (4, 1), (-1, -1), "RIGHT"),
                    ("LEFTPADDING", (4, 1), (-1, -1), 5),
                    ("RIGHTPADDING", (4, 1), (-1, -1), 5),
                ]
            )
            for i in range(1, len(table_data)):
                bg_color = colors.HexColor("#DCE6F1") if i % 2 == 0 else colors.whitesmoke
                tbl_style.add("BACKGROUND", (0, i), (-1, i), bg_color)
            tbl.setStyle(tbl_style)
            elements.append(tbl)
            elements.append(Spacer(1, 24))

            total_base_pay = pd.to_numeric(person_df["支給額"], errors="coerce").fillna(0).sum()
            total_additional_pay = (
                pd.to_numeric(person_df["追加手当"], errors="coerce").fillna(0).sum()
            )
            total_pay = total_base_pay + total_additional_pay
            total_reimburse = sum(
                pd.to_numeric(person_df[c], errors="coerce").fillna(0).sum()
                for c in ["立替金合計", "同行交通費合計", "交通費合計", "通信費合計"]
                if c in person_df.columns
            )
            grand_total = total_pay + total_reimburse

            date_series = None
            for candidate in ("日時", "業務日"):
                if candidate in person_df.columns:
                    series = pd.to_datetime(person_df[candidate], errors="coerce")
                    if series.notna().any():
                        date_series = series
                        break
            work_days = int(date_series.dt.normalize().nunique()) if date_series is not None else 0
            meet_count = int(len(person_df.index))
            paid_leave_total = 0.0
            if "有給休暇" in person_df.columns:
                paid_leave_total = (
                    pd.to_numeric(person_df["有給休暇"], errors="coerce").fillna(0).sum()
                )

            def _format_paid_leave(value: float) -> str:
                if value == 0:
                    return "0"
                if abs(value - round(value)) < 1e-6:
                    return f"{int(round(value))}"
                return f"{value:.2f}"

            summary_counts_data = [
                ["勤務日数", f"{work_days}日"],
                ["ミート回数", f"{meet_count}回"],
                ["有給休暇", _format_paid_leave(paid_leave_total)],
            ]
            counts_table = Table(
                summary_counts_data,
                colWidths=[90, 60],
                style=[
                    ("FONTNAME", (0, 0), (-1, -1), "JapaneseFont"),
                    ("FONTSIZE", (0, 0), (-1, -1), 10),
                    ("GRID", (0, 0), (-1, -1), 1, colors.black),
                    ("ALIGN", (0, 0), (0, -1), "LEFT"),
                    ("ALIGN", (1, 0), (1, -1), "RIGHT"),
                    ("VALIGN", (0, 0), (-1, -1), "MIDDLE"),
                    ("LEFTPADDING", (0, 0), (-1, -1), 8),
                    ("RIGHTPADDING", (0, 0), (-1, -1), 8),
                    ("TOPPADDING", (0, 0), (-1, -1), 6),
                    ("BOTTOMPADDING", (0, 0), (-1, -1), 6),
                ],
            )

            summary_data = [
                ["支給額 合計 (A)", _format_yen(total_pay)],
                ["経費・立替金 合計 (B)", _format_yen(total_reimburse)],
                ["総支払額 (A + B)", _format_yen(grand_total)],
            ]
            summary_table = Table(
                summary_data,
                colWidths=[150, 120],
                style=[
                    ("FONTNAME", (0, 0), (-1, -1), "JapaneseFont"),
                    ("FONTSIZE", (0, 0), (-1, -1), 10),
                    ("GRID", (0, 0), (-1, -1), 1, colors.black),
                    ("ALIGN", (0, 0), (0, -1), "LEFT"),
                    ("ALIGN", (1, 0), (1, -1), "RIGHT"),
                    ("VALIGN", (0, 0), (-1, -1), "MIDDLE"),
                    ("LEFTPADDING", (0, 0), (-1, -1), 10),
                    ("RIGHTPADDING", (0, 0), (-1, -1), 10),
                    ("TOPPADDING", (0, 0), (-1, -1), 6),
                    ("BOTTOMPADDING", (0, 0), (-1, -1), 6),
                    ("BACKGROUND", (0, 2), (-1, 2), colors.HexColor("#B9CDE5")),
                    ("FONTSIZE", (0, 2), (-1, 2), 11),
                ],
            )
            summary_container = Table(
                [[counts_table, summary_table]],
                colWidths=[160, 200],
                style=[
                    ("VALIGN", (0, 0), (-1, -1), "TOP"),
                    ("ALIGN", (0, 0), (1, 0), "RIGHT"),
                    ("LEFTPADDING", (0, 0), (-1, -1), 0),
                    ("RIGHTPADDING", (0, 0), (-1, -1), 0),
                ],
            )
            elements.append(summary_container)
            elements.append(Spacer(1, 12))

            elements.append(Paragraph(REMARK_TEXT, remark_style))
            try:
                doc = SimpleDocTemplate(
                    pdf_path,
                    pagesize=landscape(letter),
                    leftMargin=36,
                    rightMargin=36,
                    topMargin=36,
                    bottomMargin=36,
                )
                doc.build(elements)
            except Exception as exc:  # pragma: no cover - runtime logging only
                print(f"[エラー] PDF 作成中に問題が発生しました ({staff}): {exc}")

        print(
            f"\n● PDFファイル を出力しました: {output_dir} フォルダ内に各スタッフ別の PDF が生成されました。"
        )
    else:
        print("\n[警告] 'スタッフ' 列が存在しないか中身がすべて空のため、PDF は作成されません。")

    print("\n>>> 全ての処理が完了しました。Excel および PDF をご確認ください。 <<<")


if __name__ == "__main__":  # pragma: no cover - CLI entry point
    main()
