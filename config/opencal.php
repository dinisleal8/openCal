<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Supported Locales
    |--------------------------------------------------------------------------
    |
    | Locales available in the application. The first entry is used as the
    | fallback when a user has no locale set. Adding a new language means
    | adding its code here plus a dictionary in resources/js/lang/.
    |
    */

    'locales' => ['en', 'pt'],

    /*
    |--------------------------------------------------------------------------
    | Owner Account
    |--------------------------------------------------------------------------
    |
    | The first seeded account owns the instance and is the only one allowed
    | to create additional accounts. These values are used by the database
    | seeder on first install.
    |
    */

    'owner' => [
        'name' => env('OWNER_NAME', 'Owner'),
        'email' => env('OWNER_EMAIL', 'owner@opencal.local'),
        'password' => env('OWNER_PASSWORD', 'password'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Photo Uploads
    |--------------------------------------------------------------------------
    */

    'photos' => [
        'disk' => env('OPENCAL_PHOTO_DISK', 'local'),
        'directory' => 'meal-photos',
        'max_kb' => 10240,
        'mimes' => 'jpg,jpeg,png,webp,heic,heif',
    ],

];
