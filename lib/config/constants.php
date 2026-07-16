<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Platform superadministrator usernames
    |--------------------------------------------------------------------------
    |
    | Comma-separated list of usernames granted platform-superadmin abilities
    | (superadmin, backup, manage_modules) by AuthServiceProvider's Gate::before.
    | These operate the SaaS across all tenants; keep it small and set via env.
    |
    */
    'administrator_usernames' => env('ADMINISTRATOR_USERNAMES', ''),
];
