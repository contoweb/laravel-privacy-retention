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

    /*
    |--------------------------------------------------------------------------
    | Anonymizer
    |--------------------------------------------------------------------------
    |
    | Values used by the built-in anonymization strategies.
    |
    | mask_character: Character used to mask a word (String strategy).
    | mask_length:    Number of mask characters per word (String strategy).
    | email_domain:   Domain of the generated address (Email strategy).
    | fixed_value:    Value written to the field (Fixed strategy).
    |
    */

    'anonymizer' => [
        'mask_character' => '*',
        'mask_length'    => 5,
        'email_domain'   => 'anonymized.invalid',
        'fixed_value'    => 'anonymized',
    ],

];
