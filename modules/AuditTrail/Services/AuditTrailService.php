<?php

namespace Modules\AuditTrail\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Modules\AuditTrail\Models\AuditLog;

class AuditTrailService
{
    public function record(
        string $module,
        string $event,
        string $description,
        ?User $actor = null,
        ?Model $auditable = null,
        ?array $before = null,
        ?array $after = null,
        ?array $metadata = null,
    ): AuditLog {
        return AuditLog::query()->create([
            'actor_id' => $actor?->id,
            'module' => $module,
            'event' => $event,
            'auditable_type' => $auditable ? $auditable::class : null,
            'auditable_id' => $auditable?->getKey(),
            'description' => $description,
            'before_json' => $before,
            'after_json' => $after,
            'metadata_json' => $metadata,
        ]);
    }

    public function recordModelChange(
        string $module,
        string $event,
        string $description,
        Model $auditable,
        ?User $actor = null,
        ?array $before = null,
        ?array $after = null,
        ?array $metadata = null,
    ): AuditLog {
        return $this->record(
            module: $module,
            event: $event,
            description: $description,
            actor: $actor,
            auditable: $auditable,
            before: $before,
            after: $after,
            metadata: $metadata,
        );
    }
}
