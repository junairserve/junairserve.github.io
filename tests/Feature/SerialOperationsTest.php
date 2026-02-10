<?php

namespace Tests\Feature;

use App\Models\DevicePcbLink;
use App\Models\SerialPool;
use App\Models\User;
use App\Services\LinkService;
use App\Services\RangeImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SerialOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_range_import_rolls_back_when_duplicate_exists(): void
    {
        SerialPool::create(['type' => 'BODY', 'sn' => '00000003', 'status' => 'UNUSED']);

        $service = app(RangeImportService::class);

        $this->expectExceptionMessage('登録済み番号が含まれるため中止しました（例：00000003）');
        try {
            $service->import('BODY', '00000001', '00000005');
        } finally {
            $this->assertDatabaseCount('serial_pool', 1);
        }
    }

    public function test_link_rejects_used_serials(): void
    {
        $user = User::factory()->create();
        SerialPool::create(['type' => 'BODY', 'sn' => '00000001', 'status' => 'LINKED']);
        SerialPool::create(['type' => 'PCB', 'sn' => '00000002', 'status' => 'UNUSED']);

        $service = app(LinkService::class);

        $this->actingAs($user);
        $this->expectExceptionMessage('使用済みです（再利用できません）');
        $service->link(request(), '00000001', '00000002');
    }

    public function test_inspected_body_cannot_be_cancelled(): void
    {
        $user = User::factory()->create();
        SerialPool::create(['type' => 'BODY', 'sn' => '00000011', 'status' => 'INSPECTED']);
        SerialPool::create(['type' => 'PCB', 'sn' => '00000012', 'status' => 'LINKED']);
        $link = DevicePcbLink::create([
            'body_sn' => '00000011',
            'pcb_sn' => '00000012',
            'linked_at' => now(),
            'linked_by_user_id' => $user->id,
        ]);

        $service = app(LinkService::class);

        $this->actingAs($user);
        $this->expectExceptionMessage('検査済みのため取消できません（管理者に連絡）');
        $service->cancel(request(), $link, '貼り間違い');
    }
}
