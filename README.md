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
    env(SENTRY_ENVIRONMENT): '%kernel.environment%'

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
        environment: '%env(SENTRY_ENVIRONMENT)%'
        release: '%env(SENTRY_RELEASE)%'
        # Trace 10% of requests
        traces_sample_rate: 0.1

frosh_sentry:
    # Optional: Report scheduled tasks status to Sentry. See https://docs.sentry.io/product/crons/ for more information and check pricing before enabling this feature.
    report_scheduled_tasks: false
    # Optional: Send the customer number of the logged-in customer as `user.username` with errors, traces and session replays. See "Customer number" below.
    report_customer_number: false
    scheduled_tasks:
        # Optional: minutes Sentry waits for a check-in after the expected time before the run is marked as missed. Defaults to Sentry's monitor default.
        checkin_margin: 5
        # Optional: minutes a run may take before Sentry marks it as timed out. Defaults to Sentry's monitor default.
        max_runtime: 30
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

### Customer number

With `report_customer_number: true`, the customer number of the logged-in customer is sent as `user.username`, so
errors, traces and replays can be searched with `user.username:<customer number>`:

* PHP errors and transactions carry it for every request of a logged-in customer.
* With `storefront.enabled`, the page calls `Sentry.setUser()` right after `Sentry.init()`, so browser errors, traces
  and session replays carry it as well. Sentry shows a replay with the user as soon as one page with the customer
  number was visited during the replay.

#### HTTP cache

The customer number is written into the HTML, so it is only rendered where the page cannot be served to another
customer. By default, Shopware never serves cached pages to logged-in customers (cache state `logged-in` in
`shopware.cache.invalidation.http_cache`), so every page gets the customer number and the cache hit rate does not
change.

If the `CACHE_REWORK` or `PERFORMANCE_TWEAKS` feature flag is active, or `logged-in` was removed from
`shopware.cache.invalidation.http_cache`, Shopware caches pages for logged-in customers and shares them between
customers. Then cacheable pages (home, listing, product detail, ...) do not get the customer number; uncached pages
like account, cart and checkout still do.

#### Data protection

The customer number is personal data and is transferred to Sentry. Before you enable it, make sure you have a legal
basis for it, mention it in your privacy policy, have a data processing agreement with Sentry (or host Sentry
yourself) and choose a suitable data retention.

### Test

The sentry-symfony bundle provides a test command. Execute it!

```bash
bin/console sentry:test
```

## Pictures

![img](https://i.imgur.com/KUwUkxA.png)

![img](https://i.imgur.com/Jm7tjqB.png)
