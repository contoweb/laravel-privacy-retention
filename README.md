# Laravel Privacy Retention

Retention-based anonymization for Eloquent models. Implements the GDPR/DSGVO pattern of stamping `anonymized_at` once a record's personal-data fields have been masked.

## Install

```bash
composer require contoweb/laravel-privacy-retention
php artisan vendor:publish --tag=privacy-retention-config
```

Requires PHP 8.2+ and Laravel 10, 11, or 12.

## Usage

### 1. Add the column

```bash
php artisan retention:make-migration --model="App\\Models\\User"
# or
php artisan retention:make-migration --table=users
```

The column defaults to `anonymized_at`. Pass `--column=masked_at` to override, or — when using `--model` — the command picks up whatever your model returns from `getAnonymizedAtColumn()` (see [Custom column](#custom-column)).

Then `php artisan migrate`.

### 2. Mark the model

```php
use Contoweb\PrivacyRetention\Concerns\HasRetentionPolicy;
use Contoweb\PrivacyRetention\Contracts\Anonymizable;
use Contoweb\PrivacyRetention\Enums\AnonymizationStrategy;
use Illuminate\Database\Eloquent\Builder;

class User extends Model implements Anonymizable
{
    use HasRetentionPolicy;

    public function anonymizableFields(): array
    {
        return [
            'name'   => AnonymizationStrategy::String,
            'email'  => AnonymizationStrategy::Email,
            'notes'  => AnonymizationStrategy::Nullify,
            'status' => AnonymizationStrategy::Fixed,
            'phone'  => fn (?string $value): string => 'hash-'.md5((string) $value),
        ];
    }

    public static function anonymizableQuery(): Builder
    {
        return self::query()
            ->where('active', false)
            ->where('deactivated_at', '<=', now()->subYear());
    }
}
```

> Already-anonymized records are filtered out automatically via the `notAnonymized()` scope — no need to add `whereNull('anonymized_at')` yourself. Calling `anonymize()` on an already-anonymized record is a no-op (idempotent), and the field updates plus `anonymized_at` stamp are wrapped in a single DB transaction.

### 3. Register & schedule

`config/privacy-retention.php`:

```php
'models' => [
    \App\Models\User::class,
],
```

The `retention:anonymize` command is meant to run on a schedule. Eligible records are processed in chunks of 1000 (`chunkById`), so it is safe to point it at large tables.

`app/Console/Kernel.php`:

```php
$schedule->command('retention:anonymize')->daily()->at('23:00');
```

## Strategies

| Strategy   | Result                                          |
|------------|-------------------------------------------------|
| `String`   | First+last char preserved, middle masked       |
| `Email`    | `anonymized-<uuid>@anonymized.invalid`         |
| `Nullify`  | `null`                                          |
| `Fixed`    | Literal `"anonymized"`                          |

You can pass a closure instead of a strategy for custom logic. Closures receive the current field value (which may be `null`) and must return the anonymized value. Anything that isn't an `AnonymizationStrategy` or callable will throw an `InvalidArgumentException` at runtime.

## Events

The trait fires two Eloquent model events around `anonymize()`:

- `anonymizing` — before fields are mutated. Return `false` from a listener to cancel.
- `anonymized` — after the record is saved.

Hook in via the static helpers:

```php
User::anonymizing(function (User $user) {
    // ...
});

User::anonymized(function (User $user) {
    // ...
});
```

Or via an observer with matching `anonymizing` / `anonymized` methods:

```php
namespace App\Observers;

use App\Models\User;

class UserObserver
{
    public function anonymizing(User $user): void
    {
        // ...
    }

    public function anonymized(User $user): void
    {
        // ...
    }
}
```

Register it on the model:

```php
#[ObservedBy(UserObserver::class)]
class User extends Model implements Anonymizable
{
    // ...
}
```

## Custom column

Override `getAnonymizedAtColumn()` on the model:

```php
public function getAnonymizedAtColumn(): string
{
    return 'masked_at';
}
```

The migration command picks this up automatically when called with `--model`.
