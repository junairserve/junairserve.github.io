<?php

namespace App\Services;

use App\Models\SerialPool;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RangeImportService
{
    public const MAX_COUNT = 50000;
    public const CHUNK_SIZE = 1000;

    public function import(string $type, string $startSn, string $endSn, ?string $lotName = null): int
    {
        $start = (int) $startSn;
        $end = (int) $endSn;

        if ($end < $start) {
            throw new RuntimeException('開始SNと終了SNの大小関係が不正です');
        }

        $count = $end - $start + 1;
        if ($count > self::MAX_COUNT) {
            throw new RuntimeException('件数が多すぎます。レンジを分割してください');
        }

        return DB::transaction(function () use ($type, $start, $end, $lotName): int {
            $firstDuplicate = SerialPool::query()
                ->where('type', $type)
                ->whereBetween('sn', [str_pad((string) $start, 8, '0', STR_PAD_LEFT), str_pad((string) $end, 8, '0', STR_PAD_LEFT)])
                ->orderBy('sn')
                ->value('sn');

            if ($firstDuplicate) {
                throw new RuntimeException("登録済み番号が含まれるため中止しました（例：{$firstDuplicate}）");
            }

            $buffer = [];
            $inserted = 0;
            for ($i = $start; $i <= $end; $i++) {
                $buffer[] = [
                    'type' => $type,
                    'sn' => str_pad((string) $i, 8, '0', STR_PAD_LEFT),
                    'status' => 'UNUSED',
                    'lot_name' => $lotName,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if (count($buffer) === self::CHUNK_SIZE) {
                    DB::table('serial_pool')->insert($buffer);
                    $inserted += count($buffer);
                    $buffer = [];
                }
            }

            if ($buffer !== []) {
                DB::table('serial_pool')->insert($buffer);
                $inserted += count($buffer);
            }

            return $inserted;
        });
    }
}
