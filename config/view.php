<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$compiled = $root.'/storage/framework/views';

return [
    'paths' => [
        $root.'/resources/views',
    ],

    // Keep this absolute and framework-helper independent. The pre-Laravel
    // Nexora runtime bootstrap guarantees the directory exists before Composer
    // package discovery, Artisan commands, or HTTP view rendering begins.
    'compiled' => $compiled,
];
