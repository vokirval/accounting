<?php

return [
    'files_disk' => env('PAYMENT_FILES_DISK', 'public'),
    'requisites_file_retention_days' => (int) env('REQUISITES_FILE_RETENTION_DAYS', 7),
    'auto_rules_debug' => (bool) env('AUTO_RULES_DEBUG', false),
];
