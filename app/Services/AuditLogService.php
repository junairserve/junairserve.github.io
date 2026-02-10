<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogService
{
    public function write(
        Request $request,
        string $action,
        string $entityType,
        string|int|null $entityId,
        ?array $before = null,
        ?array $after = null,
        ?string $reason = null,
    ): void {
        AuditLog::create([
            'actor_user_id' => $request->user()?->id,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => (string) $entityId,
            'before_json' => $before ? json_encode($before, JSON_UNESCAPED_UNICODE) : null,
            'after_json' => $after ? json_encode($after, JSON_UNESCAPED_UNICODE) : null,
            'reason' => $reason,
            'ip' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);
    }
}
