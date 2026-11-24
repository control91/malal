<?php

return [
    // Unguessable back-office path prefix is not needed: /admin is protected by
    // authentication, roles, login throttling and an optional IP allow-list.
    'admin_allowed_ips' => array_filter(explode(',', (string) env('ADMIN_ALLOWED_IPS', ''))),

    // Public forms anti-spam
    'form_min_seconds' => (int) env('FORM_MIN_SUBMIT_SECONDS', 3),
    'form_rate_limit' => (int) env('FORM_RATE_LIMIT', 5),          // submissions…
    'form_rate_window' => (int) env('FORM_RATE_WINDOW', 600),      // …per window (seconds) and IP

    // Login brute-force protection
    'login_max_attempts' => (int) env('LOGIN_MAX_ATTEMPTS', 5),
    'login_decay_seconds' => (int) env('LOGIN_DECAY_SECONDS', 1800),

    // First-party, cookie-less analytics
    'analytics_enabled' => (bool) env('ANALYTICS_ENABLED', true),

    'max_image_kb' => (int) env('MAX_IMAGE_UPLOAD_KB', 8192),
    'max_file_kb' => (int) env('MAX_FILE_UPLOAD_KB', 15360),
];
