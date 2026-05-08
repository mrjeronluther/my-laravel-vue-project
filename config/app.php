<?php

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    */

    'name' => env('APP_NAME', 'Laravel'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment & Debug Mode
    |--------------------------------------------------------------------------
    | STRATEGY: Hard-fail to 'production' and 'false'. 
    | This prevents accidental info leakage if the .env file is missing or unreadable.
    */

    'env' => env('APP_ENV', 'production'),

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    */

    'url' => env('APP_URL', 'http://localhost'),

    'asset_url' => env('ASSET_URL'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone & Locales
    |--------------------------------------------------------------------------
    */

    'timezone' => 'UTC',

    'locale' => 'en',

    'fallback_locale' => 'en',

    'faker_locale' => 'en_US',

    /*
    |--------------------------------------------------------------------------
    | Encryption Key & Cipher
    |--------------------------------------------------------------------------
    | SECURITY: Upgraded to AES-256-GCM. 
    | GCM provides "Authenticated Encryption" (AEAD). It ensures that if an 
    | attacker modifies even 1 bit of encrypted data (like a cookie), 
    | the decryption fails immediately. CBC is vulnerable to padding oracles.
    */

    'key' => env('APP_KEY'),

    'cipher' => 'AES-256-GCM',

    /*
    |--------------------------------------------------------------------------
    | Previous Keys (Key Rotation)
    |--------------------------------------------------------------------------
    | PATTERN: Allows for zero-downtime key rotation.
    */

    'previous_keys' => [
        ...array_filter(
            explode(',', (string) env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver
    |--------------------------------------------------------------------------
    */

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

];