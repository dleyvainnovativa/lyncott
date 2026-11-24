<?php

/*
|--------------------------------------------------------------------------
| Firebase — Microsoft (Outlook) login gate
|--------------------------------------------------------------------------
| Fill these in .env once the Firebase project exists and the Microsoft
| provider is enabled. Until then the welcome page shows the login button
| but sign-in is inert (no apiKey => the client script does nothing).
|
| .env keys:
|   FIREBASE_API_KEY, FIREBASE_AUTH_DOMAIN, FIREBASE_PROJECT_ID, FIREBASE_APP_ID
|   FIREBASE_MS_TENANT        (Azure AD tenant id, to lock the provider — optional)
|   FIREBASE_CREDENTIALS      (path to the service-account JSON, for server verify)
|   FIREBASE_ALLOWED_DOMAINS  (comma list; default: lyncott.com.mx,lyncott.mx)
*/

return [

    // Public web-SDK config, injected into the welcome page.
    'web' => [
        'apiKey'     => env('FIREBASE_API_KEY'),
        'authDomain' => env('FIREBASE_AUTH_DOMAIN'),
        'projectId'  => env('FIREBASE_PROJECT_ID'),
        'appId'      => env('FIREBASE_APP_ID'),
        'tenant'     => env('FIREBASE_MS_TENANT'),
    ],

    // Service-account JSON path (absolute, or relative to the project root).
    'credentials' => env('FIREBASE_CREDENTIALS'),

    // Require the provider to report the email as verified. Personal Microsoft
    // accounts (outlook.com/hotmail) often come back unverified — set this to
    // false in .env to test with them.
    'require_verified_email' => (bool) env('FIREBASE_REQUIRE_VERIFIED_EMAIL', true),

    // Email domains allowed to sign in.
    'allowed_domains' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('FIREBASE_ALLOWED_DOMAINS', 'lyncott.com.mx,lyncott.mx')),
    ))),
];
