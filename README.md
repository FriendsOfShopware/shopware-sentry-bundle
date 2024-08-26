# Shopware Sentry Bundle

This plugin integrates the [Sentry](https://sentry.io) error tracking service into Shopware 6.

## Installation

```bash
composer require "frosh/sentry-bundle:*" "sentry/sentry-symfony:*"
```

Then, in `config/bundles.php` add

```php
Sentry\SentryBundle\SentryBundle::class => ['all' => true],
Frosh\SentryBundle\ShopwareSentryBundle::class => ['all' => true],
```

at the end of the `$bundles` array.

Symfony Flex might have added the SentryBundle as `'dev' => true` already. Adapt this.

## Configuration

After installation, create a `config/packages/sentry.yaml` file in your Shopware installation and add the following configuration:

```yaml
parameters:
    env(SENTRY_DSN): ''
    env(SENTRY_RELEASE): ''

# Tells Shopware to forward traces to Sentry
shopware:
    profiler:
        integrations:
            - Sentry

sentry:
    dsn: "%env(SENTRY_DSN)%"
    tracing:
        enabled: true
        dbal:
            enabled: false
        cache:
            enabled: false
        twig:
            enabled: false
        http_client:
            enabled: true
    messenger:
        enabled: true
    options:
        # Do not sent deprecations to sentry
        error_types: E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED
        integrations:
          # Use default exception ignore list of Shopware 
          - 'Frosh\SentryBundle\Integration\UseShopwareExceptionIgnores'
        environment: '%kernel.environment%'
        release: '%env(SENTRY_RELEASE)%'
        # Trace 10% of requests
        traces_sample_rate: 0.1

frosh_sentry:
    # Optional: Report scheduled tasks status to Sentry. See https://docs.sentry.io/product/crons/ for more information and check pricing before enabling this feature.
    report_scheduled_tasks: false
    storefront:
        # optional: if you want track errors occurs within the browser (javascript/cors/csp)
        enabled: true
        # optional: if you want record the user sessions. Please aware the GDPR.
        replay_recording:
          enabled: true
          sample_rate: 0.1
        # optional: if you want measure the performance within the browser
        tracing:
          enabled: true
          sample_rate: 0.1
        # you should always specify a sdk version. If you do not provide any version, a hard-coded version got used. We try to keep the version up to date with the latest version, but cause the fast release-line of the javascript SDK it is recommend to update the version number regularly.
        javascript_sdk_version: "8.26.0"
```
### Test

The sentry-symfony bundle provides a test command. Execute it!

```bash
bin/console sentry:test
```

## Pictures

![img](https://i.imgur.com/KUwUkxA.png)

![img](https://i.imgur.com/Jm7tjqB.png)
