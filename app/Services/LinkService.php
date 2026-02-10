<?php

namespace App\Services;

use App\Models\DevicePcbLink;
use App\Models\SerialPool;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LinkService
{
    public function __construct(private readonly AuditLogService $auditLogService)
    {
    }

    public function link(Request $request, string $bodySn, string $pcbSn): DevicePcbLink
    {
        return DB::transaction(function () use ($request, $bodySn, $pcbSn): DevicePcbLink {
            $body = SerialPool::query()->where('type', 'BODY')->where('sn', $bodySn)->lockForUpdate()->first();
            $pcb = SerialPool::query()->where('type', 'PCB')->where('sn', $pcbSn)->lockForUpdate()->first();

            if (! $body || ! $pcb) {
                throw new RuntimeException('未受入の番号です（連番登録されていません）');
            }
            if ($body->status !== 'UNUSED' || $pcb->status !== 'UNUSED') {
                throw new RuntimeException('使用済みです（再利用できません）');
            }

            $activeByBody = DevicePcbLink::query()->where('body_sn', $bodySn)->whereNull('unlinked_at')->lockForUpdate()->exists();
            $activeByPcb = DevicePcbLink::query()->where('pcb_sn', $pcbSn)->whereNull('unlinked_at')->lockForUpdate()->exists();
            if ($activeByBody || $activeByPcb) {
                throw new RuntimeException('使用済みです（再利用できません）');
            }

            $body->update(['status' => 'LINKED']);
            $pcb->update(['status' => 'LINKED']);

            $link = DevicePcbLink::create([
                'body_sn' => $bodySn,
                'pcb_sn' => $pcbSn,
                'linked_at' => now(),
                'linked_by_user_id' => $request->user()->id,
            ]);

            $this->auditLogService->write($request, 'link.create', 'device_pcb_links', $link->id, null, $link->toArray());

            return $link;
        });
    }

    public function cancel(Request $request, DevicePcbLink $link, string $reason): void
    {
        DB::transaction(function () use ($request, $link, $reason): void {
            $active = DevicePcbLink::query()->whereKey($link->id)->lockForUpdate()->firstOrFail();

            $body = SerialPool::query()->where('type', 'BODY')->where('sn', $active->body_sn)->lockForUpdate()->firstOrFail();
            $pcb = SerialPool::query()->where('type', 'PCB')->where('sn', $active->pcb_sn)->lockForUpdate()->firstOrFail();

            if ($body->status === 'INSPECTED') {
                throw new RuntimeException('検査済みのため取消できません（管理者に連絡）');
            }

            $before = $active->toArray();

            $active->update([
                'unlinked_at' => now(),
                'unlinked_by_user_id' => $request->user()->id,
                'unlink_reason' => $reason,
            ]);

            $body->update(['status' => 'UNUSED']);
            $pcb->update(['status' => 'UNUSED']);

            $this->auditLogService->write(
                $request,
                'link.cancel',
                'device_pcb_links',
                $active->id,
                $before,
                $active->fresh()->toArray(),
                $reason,
            );
        });
    }
}
