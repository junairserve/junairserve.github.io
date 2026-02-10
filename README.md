# SN紐づけ＋検査記録 管理Webアプリ（Laravel 11 MVP）

## 1) リポジトリ構成案（主要ディレクトリ）

- `app/Http/Controllers`: 受入/紐づけ/取消/検査/一覧のUI制御
- `app/Http/Requests`: サーバー側バリデーション（8桁SN、必須項目）
- `app/Services`: トランザクション境界（受入・紐づけ・取消・検査・監査ログ）
- `app/Models`: `serial_pool`, `device_pcb_links`, `inspections`, `audit_logs`
- `database/migrations`: テーブル定義・インデックス・制約
- `resources/views`: Blade UI（高速入力、一覧、詳細）
- `routes/web.php`: 画面ルーティング
- `tests/Feature`: 最低限のMVP仕様テスト

## 7) Xserverでのセットアップ手順

1. **PHP/DB準備**
   - XserverでPHP 8.2+を選択
   - MySQLデータベースとユーザーを作成
2. **コード配置**
   - Gitでデプロイ先へ配置
   - `public/` をXserverの公開ディレクトリ（`public_html`）へ向ける（シンボリックリンクまたはドキュメントルート変更）
3. **`.env`作成**
   - `.env.example` から `.env` を作成し、以下を設定
   - `APP_ENV=production`
   - `APP_KEY` は `php artisan key:generate`
   - `DB_CONNECTION=mysql`
   - `DB_HOST`,`DB_PORT`,`DB_DATABASE`,`DB_USERNAME`,`DB_PASSWORD`
4. **初期化**
   - `composer install --no-dev --optimize-autoloader`
   - `php artisan migrate --force`
   - `php artisan config:cache && php artisan route:cache && php artisan view:cache`
5. **権限**
   - `storage/` と `bootstrap/cache/` をWebサーバー書き込み可に設定
6. **運用**
   - MVPはキュー/cron不要
   - 監査ログはDB永続化されるため10年保管ポリシーはDBバックアップ運用で担保

## 改善提案（追加実装は別指示で対応）

- 役割ベースのMiddleware/Policy徹底（factory向け専用画面）
- `device_pcb_links` の「activeのみ一意」をDBレベルで強化（MySQL 8 generated column + unique）
- 一覧の日付フィルタ（今日/今週/期間）UIを追加
- 監査ログのJSON差分表示を見やすく整形
- 工場別アクセス分離（`orgs` 導入）
- 検査記録の更新禁止（作成のみ）をルートレベルで固定
