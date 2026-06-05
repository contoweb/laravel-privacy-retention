<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Anonymizable Models
    |--------------------------------------------------------------------------
    |
    | Fully qualified class names processed by the `retention:anonymize` command.
    | Each model must implement \Contoweb\PrivacyRetention\Contracts\Anonymizable
    | and use the \Contoweb\PrivacyRetention\Concerns\HasRetentionPolicy trait.
    |
    */

    'models' => [
        // \App\Models\User::class,
    ],

];
