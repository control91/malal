<?php

namespace App\Services;

use App\Mail\TextMail;
use App\Models\AdminNotification;
use App\Models\User;
use App\Support\Capabilities;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

final class Notifier
{
    /** Queue an email; delivery failures are logged, never shown to the visitor. */
    public static function mail(array $to, string $subject, string $template, array $data = [], array $replyTo = []): void
    {
        $to = array_values(array_filter($to));
        if (! $to) {
            return;
        }
        try {
            Mail::to($to)->send(new TextMail($subject, $template, $data, $replyTo));
        } catch (\Throwable $e) {
            Log::error('Email delivery failed', ['subject' => $subject, 'error' => $e->getMessage()]);
        }
    }

    /** In-app notification for every active staff user holding $capability. */
    public static function notifyCapability(string $capability, string $title, string $body = '', string $url = '', string $icon = 'bell'): int
    {
        $count = 0;
        User::where('is_active', true)->each(function (User $user) use ($capability, $title, $body, $url, $icon, &$count) {
            if (Capabilities::has($user, $capability)) {
                AdminNotification::create(['user_id' => $user->id, 'title' => $title, 'body' => mb_substr($body, 0, 255), 'url' => $url, 'icon' => $icon]);
                $count++;
            }
        });

        return $count;
    }
}
