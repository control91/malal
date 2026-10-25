<?php

namespace App\Support;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/** Single entry point to write the audit trail. */
final class Audit
{
    private const SENSITIVE = ['password', 'password_confirmation', 'token', 'remember_token', 'secret'];

    public static function record(AuditAction $action, ?Model $object = null, array $changes = [], string $repr = '', ?Request $request = null, $actor = null): AuditLog
    {
        $request ??= request();
        $actor ??= $request?->user();

        $entry = AuditLog::create([
            'actor_id' => $actor?->id,
            'actor_label' => $actor ? (string) $actor : '',
            'action' => $action,
            'object_type' => $object ? class_basename($object) : '',
            'object_id' => $object?->getKey() ? (string) $object->getKey() : '',
            'object_repr' => mb_substr($repr ?: ($object ? (string) self::repr($object) : ''), 0, 255),
            'changes' => self::redact($changes) ?: null,
            'ip_address' => $request?->ip(),
            'user_agent' => mb_substr((string) $request?->userAgent(), 0, 255),
        ]);
        Log::info("audit {$action->value} {$entry->object_type} {$entry->object_id} by ".($entry->actor_label ?: '-'));

        return $entry;
    }

    /** Changed attributes of a model after save(), secrets redacted and long values truncated. */
    public static function diff(Model $model): array
    {
        return self::redact(array_diff_key($model->getChanges(), array_flip(['updated_at', 'created_at'])));
    }

    private static function redact(array $changes): array
    {
        foreach ($changes as $key => $value) {
            if (in_array($key, self::SENSITIVE, true)) {
                $changes[$key] = '***';
            } elseif (is_string($value) && mb_strlen($value) > 300) {
                $changes[$key] = mb_substr($value, 0, 300).'…';
            } elseif ($value instanceof \BackedEnum) {
                $changes[$key] = $value->value;
            }
        }

        return $changes;
    }

    private static function repr(Model $object): string
    {
        return method_exists($object, '__toString') ? (string) $object : class_basename($object).' #'.$object->getKey();
    }
}
