<?php

return [
    'files_disk' => env('PAYMENT_FILES_DISK', 'public'),
    'requisites_file_retention_days' => (int) env('REQUISITES_FILE_RETENTION_DAYS', 7),
];
