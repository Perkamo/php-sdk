# `perkamo/sdk`

Server-side PHP client for the Perkamo API.

Use this package only from trusted backend code. Never expose Perkamo server API
keys to browser, mobile, or embedded widget code.

Full SDK documentation: https://www.perkamo.com/docs/v1/sdk

```bash
composer require perkamo/sdk
```

## Quick Start

```php
<?php

use Perkamo\Client;
use Perkamo\EventInput;

$perkamo = new Client(
    apiKey: getenv('PERKAMO_SECRET_KEY'),
);

$event = EventInput::create('customer_123', 'purchase.completed')
    ->withTransactionId('order_1092')
    ->withContextValue('order_id', 'order_1092')
    ->withContextValue('amount', 12900)
    ->withContextValue('currency', 'CZK');

$result = $perkamo->emitEvent($event);

if ($result->applied) {
    foreach ($result->delta as $delta) {
        printf("%+g %s\n", $delta->amount, $delta->wallet);
    }
}
```

For one-off calls, `emit()` remains available and builds the same typed event
internally:

```php
$result = $perkamo->emit(
    userId: 'customer_123',
    event: 'purchase.completed',
    context: ['order_id' => 'order_1092'],
    transactionId: 'order_1092',
);
```

The client signs mutating requests with:

- `x-perkamo-api-key`
- `x-perkamo-timestamp`
- `x-perkamo-signature`

The client defaults to the hosted Perkamo API. Pass `baseUrl` only for a custom,
staging or private endpoint.

Reserved server-computed context keys such as `xp`, `wallet`, `wallets`,
`level`, `perks`, `rewards` and `achievements` are rejected before a request is
sent.

`emitEvent()` and `emit()` return `Perkamo\EventIngestResult`. Use `toArray()`
when you need the raw API payload.

## Program Catalog

Trusted backend and admin integrations can read the active Space program and
event catalog:

```php
$program = $perkamo->program();
$events = $perkamo->eventCatalog();

foreach ($events as $event) {
    echo $event['event'] . PHP_EOL;
}
```

Use this to populate customer-admin tooling with configured event keys and
labels. Do not use it as a wallet editing API.

## API Errors

Non-2xx responses throw `Perkamo\Exception\PerkamoApiException`. The exception
includes the HTTP status, parsed body and operational metadata when Perkamo or
an API gateway returns it:

```php
use Perkamo\Exception\PerkamoApiException;

try {
    $perkamo->emit('customer_123', 'purchase.completed', transactionId: 'order_1092');
} catch (PerkamoApiException $error) {
    error_log(json_encode([
        'status' => $error->statusCode(),
        'request_id' => $error->requestId(),
        'retry_after' => $error->retryAfter(),
        'rate_limit' => $error->rateLimit(),
    ]));
}
```

## Browser Tokens

For browser SDK integrations, authenticate the user in your backend first and
then ask Perkamo to issue a short-lived browser token:

```php
$token = $perkamo->createBrowserToken(
    browserKey: getenv('PERKAMO_BROWSER_KEY'),
    userId: 'customer_123',
    ttlSeconds: 600,
);

return [
    'token' => $token->token,
    'token_type' => $token->tokenType,
    'expires_at' => $token->expiresAt->format(DATE_ATOM),
    'expires_in' => $token->expiresIn,
];
```

The PHP SDK uses the configured server API key for this request. Browser key
access policy is configured in Perkamo and enforced server-side; the runtime
token request does not send scopes or event allowlists. Use `*` on the browser
key to allow all current and future configured events. New browser keys default
to the full browser SDK policy: profile reads, allowed browser events and
profile streams. Do not expose the server API key to browser, mobile or widget
code.

## License

MIT
