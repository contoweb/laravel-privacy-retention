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

    /*
    |--------------------------------------------------------------------------
    | Fire Model Events on Anonymization
    |--------------------------------------------------------------------------
    |
    | When true, anonymizing a model fires the usual Eloquent saving/saved/
    | updating/updated events alongside the dedicated anonymizing/anonymized
    | events. Set to false to persist the anonymization quietly using
    | saveQuietly(). Individual models may override this via the
    | shouldFireAnonymizationEvents() method on the HasRetentionPolicy trait.
    |
    */

    'events' => true,

];
