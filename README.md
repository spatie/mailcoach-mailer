# The Mailcoach transport for Symfony Mailer

[![Latest Version on Packagist](https://img.shields.io/packagist/v/spatie/mailcoach-mailer.svg?style=flat-square)](https://packagist.org/packages/spatie/mailcoach-mailer)
[![Tests](https://github.com/spatie/mailcoach-mailer/actions/workflows/run-tests.yml/badge.svg?branch=main)](https://github.com/spatie/mailcoach-mailer/actions/workflows/run-tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/spatie/mailcoach-mailer.svg?style=flat-square)](https://packagist.org/packages/spatie/mailcoach-mailer)

This package contains a transport class for [Symfony Mailer](https://symfony.com/doc/current/mailer.html) to send transactional mails through [Mailcoach](https://mailcoach.app).

For Laravel, take a look at [the Laravel specific package](https://github.com/spatie/laravel-mailcoach-mailer).

## Support us

[<img src="https://spatie.be/github-ad-image/mailcoach-mailer.jpg" width="419px" />](https://spatie.be/github-ad-click/mailcoach-mailer)

We invest a lot of resources into creating [best in class open source packages](https://spatie.be/open-source). You can support us by [buying one of our paid products](https://spatie.be/open-source/support-us).

We highly appreciate you sending us a postcard from your hometown, mentioning which of our package(s) you are using. You'll find our address on [our contact page](https://spatie.be/about-us). We publish all received postcards on [our virtual postcard wall](https://spatie.be/open-source/postcards).

## Installation

You can install the package via composer:

```bash
composer require spatie/mailcoach-mailer
```

## Documentation

You can find all documentation on [the Mailcoach website](https://www.mailcoach.app/resources/learn-mailcoach/features/transactional/#content-using-symfony-mailer)

### Custom transactional API base URL

By default, the transport sends requests to `https://{host}/api/transactional-mails/send`. Existing configuration does not need to change.

For self-hosted installations, you can optionally override the base URL, for example to use an internal Kubernetes service over HTTP:

```php
use Spatie\MailcoachMailer\MailcoachApiTransport;

$transport = (new MailcoachApiTransport('your-api-token'))
    ->setHost('domain.mailcoach.app')
    ->setApiBaseUrl('http://mailcoach-service.default.svc.cluster.local:8080');
```

This sends requests to `http://mailcoach-service.default.svc.cluster.local:8080/api/transactional-mails/send`. The override takes precedence over the configured host, and a host is not required when a base URL is supplied.

Use an absolute HTTP or HTTPS URL, optionally including a port and a path prefix (such as `https://internal.example.com/mailcoach`). Do not include the `/api/transactional-mails/send` endpoint, a query string, or a fragment: the transport appends the endpoint itself. Trailing slashes are removed. Passing `null` or an empty string to `setApiBaseUrl()` restores the default HTTPS URL based on the host.

When using `MailcoachTransportFactory`, pass the optional `api_base_url` DSN option:

```php
use Spatie\MailcoachMailer\MailcoachTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;

$transport = (new MailcoachTransportFactory)->create(new Dsn(
    'mailcoach',
    'domain.mailcoach.app',
    options: [
        'token' => 'your-api-token',
        'api_base_url' => 'http://mailcoach-service:8080',
    ],
));
```

For a DSN string, URL-encode the base URL query parameter:

```text
mailcoach://domain.mailcoach.app?token=your-api-token&api_base_url=http%3A%2F%2Fmailcoach-service%3A8080
```

The request method, endpoint path, bearer token, and email payload are unchanged. Custom HTTP clients supplied to the transport or factory continue to work, including clients injected through the Laravel package's `setClient()` method. This option belongs to this transport and its factory; it is not automatically read from Laravel's mail configuration by the Laravel package.

Only use HTTP on a trusted network: the bearer token and email content are sent without TLS encryption. HTTPS remains the default.

## Tests

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](https://github.com/spatie/.github/blob/main/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

-   [Freek Van der Herten](https://github.com/freekmurze)
-   [All Contributors](../../contributors)

This package contains modified code from [Symfony's Postmark mailer](https://github.com/symfony/postmark-mailer).

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
