# Stack multiple session handlers for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/sebdesign/laravel-stack-session-handler.svg?style=flat-square)](https://packagist.org/packages/sebdesign/laravel-stack-session-handler)
[![Build Status](https://img.shields.io/travis/sebdesign/laravel-stack-session-handler/master.svg?style=flat-square)](https://travis-ci.org/sebdesign/laravel-stack-session-handler)
[![Quality Score](https://img.shields.io/scrutinizer/g/sebdesign/laravel-stack-session-handler.svg?style=flat-square)](https://scrutinizer-ci.com/g/sebdesign/laravel-stack-session-handler)
[![Total Downloads](https://img.shields.io/packagist/dt/sebdesign/laravel-stack-session-handler.svg?style=flat-square)](https://packagist.org/packages/sebdesign/laravel-stack-session-handler)


A session handler that stacks multiple session handlers in Laravel's session store. The session data will be read from the first handler in the stack if possible, and will be written to all handlers.

## Installation

You can install the package via composer:

```bash
composer require sebdesign/laravel-stack-session-handler
```

## Usage

In your `.env` set the `SESSION_DRIVER` to `stack`.
``` env
SESSION_DRIVER=stack
```

Then in your `config/session.php` add a `drivers` array with the drivers you want to stack.

``` php
'drivers' => ['redis', 'database'],
```

Note that the order of drivers is important when starting the session: the first handler will be used to read the session data, and if there is no data it will read from the second handler, etc.

When the session is saved, it will be written to all the handlers, the order is not important in that case.

E.g. We can use `redis` to read sessions efficiently, and also `database` to be able to query the sessions.

If you forget to set the `drivers` array, the `file` driver will be used.

### Testing

``` bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email info@sebdesign.eu instead of using the issue tracker.

## Credits

- [Sébastien Nikolaou](https://github.com/sebdesign)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
