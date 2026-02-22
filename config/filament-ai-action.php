<?php

declare(strict_types=1);

return [
    'default_label' => env('FILAMENT_AI_ACTION_LABEL', 'Ask AI'),
    'show_usage'    => env('FILAMENT_AI_ACTION_SHOW_USAGE', false),
    'modal_size'    => env('FILAMENT_AI_ACTION_MODAL_SIZE', 'xl'),
    'allow_copy'    => env('FILAMENT_AI_ACTION_ALLOW_COPY', true),
];
