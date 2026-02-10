<?php

namespace App\Services;

use App\Models\Inspection;
use App\Models\SerialPool;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InspectionService
{
    public function __construct(private readonly AuditLogService $auditLogService)
    {
    }

    public function create(Request $request, array $payload): Inspection
    {
        return DB::transaction(function () use ($request, $payload): Inspection {
            $body = SerialPool::query()->where('type', 'BODY')->where('sn', $payload['body_sn'])->lockForUpdate()->first();
            if (! $body) {
                throw new RuntimeException('未受入の番号です（連番登録されていません）');
            }
            if (! in_array($body->status, ['LINKED', 'INSPECTED'], true)) {
                throw new RuntimeException('紐づけ後の本体SNのみ検査登録できます');
            }

            $inspection = Inspection::create([
                'body_sn' => $payload['body_sn'],
                'cert_no' => $payload['cert_no'],
                'date' => $payload['date'],
                'place' => $payload['place'],
                'responsible_user_id' => $request->user()->id,
                'method' => $payload['method'],
                'result' => $payload['result'],
            ]);

            if ($payload['result'] === 'PASS') {
                $body->update(['status' => 'INSPECTED']);
            }

            $this->auditLogService->write($request, 'inspection.create', 'inspections', $inspection->id, null, $inspection->toArray());

            return $inspection;
        });
    }
}
