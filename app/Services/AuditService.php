<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditService
{
    public function log(
        string $action,
        ?User $user = null,
        ?Model $entity = null,
        array $metadata = [],
    ): AuditLog {
        return AuditLog::create([
            'user_id' => $user?->id,
            'action' => $action,
            'entity_type' => $entity ? class_basename($entity) : null,
            'entity_id' => $entity?->id,
            'metadata' => empty($metadata) ? null : $metadata,
        ]);
    }
}
