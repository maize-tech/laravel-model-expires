<p align="center">
<picture>
  <source media="(prefers-color-scheme: dark)" srcset="/art/socialcard-dark.png">
  <source media="(prefers-color-scheme: light)" srcset="/art/socialcard-light.png">
  <img src="/art/socialcard-light.png" alt="Social Card of Laravel Model Expires">
</picture>
</p>

# Laravel Model Expires

[![Latest Version on Packagist](https://img.shields.io/packagist/v/maize-tech/laravel-model-expires.svg?style=flat-square)](https://packagist.org/packages/maize-tech/laravel-model-expires)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/maize-tech/laravel-model-expires/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/maize-tech/laravel-model-expires/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/maize-tech/laravel-model-expires/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/maize-tech/laravel-model-expires/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/maize-tech/laravel-model-expires.svg?style=flat-square)](https://packagist.org/packages/maize-tech/laravel-model-expires)

With this package you can add expiration date to any model and exclude expired models from queries.
When needed, you could send a notification for expiring models.
You can also set a deletion date for every model and automatically clean them up with a command.

## Installation

You can install the package via composer:

```bash
composer require maize-tech/laravel-model-expires
```

You can publish the config and migration files and run the migrations with:

```bash
php artisan model-expires:install
```

This is the contents of the published config file:

```php
return [

    /*
    |--------------------------------------------------------------------------
    | Expiration model
    |--------------------------------------------------------------------------
    |
    | Here you may specify the fully qualified class name of the expiration model.
    |
    */

    'expiration_model' => Maize\ModelExpires\Models\Expiration::class,

    'model' => [

        /*
        |--------------------------------------------------------------------------
        | Expires after days
        |--------------------------------------------------------------------------
        |
        | Here you may specify the default amount of days after which a model
        | should expire.
        | If null, all newly created models won't have a default expiration date.
        |
        */

        'expires_after_days' => null,

        /*
        |--------------------------------------------------------------------------
        | Deletes after days
        |--------------------------------------------------------------------------
        |
        | Here you may specify the default amount of days after which a model
        | should be deleted.
        | If null, all newly created models won't have a default deletion date.
        |
        */

        'deletes_after_days' => null,
    ],

    'expiring_notification' => [

        /*
        |--------------------------------------------------------------------------
        | Enable expiring notification
        |--------------------------------------------------------------------------
        |
        | Here you may specify whether you want to enable model expiring
        | notifications or not.
        |
        */

        'enabled' => true,

        /*
        |--------------------------------------------------------------------------
        | Notification class
        |--------------------------------------------------------------------------
        |
        | Here you may specify the fully qualified class name of the default notification.
        | If null, no notifications will be sent.
        |
        */

        'notification' => Maize\ModelExpires\Notifications\ModelExpiringNotification::class,

        /*
        |--------------------------------------------------------------------------
        | Notifiable emails
        |--------------------------------------------------------------------------
        |
        | Here you may specify the default list of notifiable email addresses.
        |
        */

        'notifiables' => [
            //
        ],
    ],
];
```

## Usage

### Basic

To use the package, add the `Maize\ModelExpires\HasExpiration` trait to all models you want to have an expiration date:

``` php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Maize\ModelExpires\HasExpiration;

class User extends Model
{
    use HasExpiration;
}
```

That's it! All you have to do from now on is calling the `setExpiresAt` method every time you want to set an expiration and/or deletion date:

``` php
$user = User::create([])->setExpiresAt(
    expiresAt: now()->addDays(5),
    deletesAt: now()->addDays(10),
); // user will have both an expiration and deletion date

$user = User::create([])->setExpiresAt(
    expiresAt: now()->addDays(5)
); // user will have an expiration date but will not be deleted
```

### Checking expiration and deletion days left

You can also check whether a model is expired and calculate the amount of days before its expiration (or deletion):

``` php
$user = User::create([])->setExpiresAt(
    expiresAt: now()->addDays(5),
    deletesAt: now()->addDays(10),
);

$user->isExpired(); // returns false

$user->getDaysLeftToExpiration(); // returns 5
$user->getDaysLeftToDeletion(); // returns 10


$user = User::create([])->setExpiresAt(
    expiresAt: now()->subDay()
);

$user->isExpired(); // returns true

$user->getDaysLeftToExpiration(); // returns 0, as the model is already expired
$user->getDaysLeftToDeletion(); // returns null, as model does not have a deletion date
```

### Excluding expired models

When you want to exclude expired models, all you have to do is use the `withoutExpired` scope method:

``` php
$user = User::create([]); // user does not have an expiration date
$expiredUser = User::create([])->setExpiresAt(
    expiresAt: now()->subDay(),
); // user is already expired

User::withoutExpired()->count(); // returns 1, which is the $user model
User::withoutExpired()->get(); // returns the $user model
```


### Retrieving only expired models

When you want to retrieve expired models, all you have to do is use the `onlyExpired` scope method:

``` php
$user = User::create([]); // user does not have an expiration date
$expiredUser = User::create([])->setExpiresAt(
    expiresAt: now()->subDay(),
); // user is already expired

User::onlyExpired()->count() // returns 1, which is the $expiredUser model
User::onlyExpired()->get(); // returns the $expiredUser model
```

### Default expiration date

If you wish, you can define a default expiration date. This can be done in two ways.

First, you can set a value for `expires_after_days` property under `config/model-expires.php` config file.
When set, all models including the `Maize\ModelExpires\HasExpiration` trait will automatically have an expiration date upon its creation:

``` php
config()->set('model-expires.model.expires_after_days', 5);

$user = User::create([]);
$user->getDaysLeftToExpiration(); // returns 5
```

The second way is overriding the `defaultExpiresAt` method within all models you want to have a default expiration date:

``` php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Maize\ModelExpires\HasExpiration;

class User extends Model
{
    use HasExpiration;
    
    protected static function defaultExpiresAt(): ?Carbon
    {
        return now()->addDays(10); // all user models will expire 10 days after being created
    }
}
```

``` php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Maize\ModelExpires\HasExpiration;

class Tenant extends Model
{
    use HasExpiration;
    
    protected static function defaultExpiresAt(): ?Carbon
    {
        return now()->addMonth(); // all tenant models will expire 1 month after being created
    }
}
```

### Default deletion date

If you wish, you can define a default deletion date. This can be done in two ways.

First, you can set a value for `deletes_after_days` property under `config/model-expires.php` config file.
When set, all models including the `Maize\ModelExpires\HasExpiration` trait will automatically have a deletion date upon its creation:

``` php
config()->set('model-expires.model.deletes_after_days', 5);

$user = User::create([]);
$user->getDaysLeftToDeletion(); // returns 5
```

The second way is overriding the `defaultDeletesAt` method within all models you want to have a default deletion date:

``` php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Maize\ModelExpires\HasExpiration;

class User extends Model
{
    use HasExpiration;
    
    protected static function defaultDeletesAt(): ?Carbon
    {
        return now()->addDays(10); // all user models will be deleted 10 days after being created
    }
}
```

``` php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Maize\ModelExpires\HasExpiration;

class Tenant extends Model
{
    use HasExpiration;
    
    protected static function defaultDeletesAt(): ?Carbon
    {
        return now()->addMonth(); // all tenant models will be deleted 1 month after being created
    }
}
```

### Scheduling expiration check

The package comes with the `expires:check` command, which automatically fires a `ModelExpiring` event for all expiring models.

To do so, you should define how often you want to fire the event.
All you have to do is overriding the `fireExpiringEventBeforeDays` for all models using the `HasExpiration` trait:

``` php
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable
{
    use HasExpiration;

    public static function fireExpiringEventBeforeDays(): array
    {
        return [5, 10]; // expiring events will be fired 5 and 10 days before each model's expiration
    }
}
```

By default, the method returns an empty array, meaning models will never fire expiring events.

Once done, you can schedule the command on a daily basis using the `schedule` method of the console kernel (usually located under the `App\Console` directory):

``` php
use Maize\ModelExpires\Commands\ModelExpiresDeleteCommand;

$schedule->command(ModelExpiresCheckCommand::class)->daily();
```

### Scheduling models deletion

The package also comes with the `expires:delete` command, which automatically deletes all expired and deletable models.
This comes pretty useful when automatizing its execution using Laravel's scheduling.
All you have to do is add the following instruction to the `schedule` method of the console kernel (usually located under the `App\Console` directory):

``` php
use Maize\ModelExpires\Commands\ModelExpiresDeleteCommand;

$schedule->command(ModelExpiresDeleteCommand::class)->daily();
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](https://github.com/maize-tech/.github/blob/main/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](https://github.com/maize-tech/.github/security/policy) on how to report security vulnerabilities.

## Credits

- [Enrico De Lazzari](https://github.com/enricodelazzari)
- [Riccardo Dalla Via](https://github.com/riccardodallavia)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
