<?php

return [
    'default' => env('APP_LOCALE', 'en'),
    'fallback' => env('APP_FALLBACK_LOCALE', 'en'),
    'cookie' => 'nexora_locale',
    'supported' => [
        'en' => ['name' => 'English', 'native' => 'English', 'country' => 'United States', 'flag' => '🇺🇸', 'flag_asset' => '/brand/flags/us.svg', 'dir' => 'ltr'],
        'ur' => ['name' => 'Urdu', 'native' => 'اردو', 'country' => 'Pakistan', 'flag' => '🇵🇰', 'flag_asset' => '/brand/flags/pk.svg', 'dir' => 'rtl'],
        'tr' => ['name' => 'Turkish', 'native' => 'Türkçe', 'country' => 'Türkiye', 'flag' => '🇹🇷', 'flag_asset' => '/brand/flags/tr.svg', 'dir' => 'ltr'],
        'ar' => ['name' => 'Arabic', 'native' => 'العربية', 'country' => 'Saudi Arabia', 'flag' => '🇸🇦', 'flag_asset' => '/brand/flags/sa.svg', 'dir' => 'rtl'],
        'ru' => ['name' => 'Russian', 'native' => 'Русский', 'country' => 'Russia', 'flag' => '🇷🇺', 'flag_asset' => '/brand/flags/ru.svg', 'dir' => 'ltr'],
    ],
];
