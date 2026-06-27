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

## Identify Customers

Create or update trusted customer traits before or during your event integration:

```php
$perkamo->identify('customer_123', [
    'email' => 'customer@example.test',
    'name' => 'Customer Test',
    'crm_id' => 'crm_123',
]);
```

Use your application's stable user id as the Perkamo customer id. Put only
non-secret customer facts your application is allowed to share in traits.

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

## Client Tokens

For browser SDK integrations, authenticate the user in your backend first and
then sign a short-lived client token with a Perkamo signing key.

Create the signing key in the Perkamo console (Settings → Security → Signing
keys). It gives you a **KID** (shown in the creation dialog and then listed in
the table) and a **one-time secret** (shown once, backend-only). Read both from environment
variables — the names are your own choice:

```php
use Perkamo\ClientTokenSigner;

$token = ClientTokenSigner::sign(
    kid: getenv('PERKAMO_SIGNING_KID'),       // KID
    secret: getenv('PERKAMO_SIGNING_SECRET'), // one-time secret, backend-only
    subject: 'customer_123',
    scope: ['customer:read', 'events:write'],
    events: ['page.viewed'],
    ttlSeconds: 600,
);

return [
    'token' => $token,
    'token_type' => 'Bearer',
];
```

The arguments:

| Argument     | What it is                                                                         |
| ------------ | ---------------------------------------------------------------------------------- |
| `kid`        | KID of your signing key; goes in the token header. Safe to expose.                 |
| `secret`     | The signing key's one-time secret that signs the token. Sensitive — backend-only.  |
| `subject`    | The user the token authorizes — your stable user id.                               |
| `scope`      | Client permissions: `customer:read`, `events:write`, `stream:read`.                |
| `events`     | Optional allow-list of event names the token may emit.                             |
| `ttlSeconds` | Token lifetime in seconds (default `600`). Clamped to the key's maximum at verify. |

Only the secret is sensitive; the `kid` is public and travels in every token
header. `scope` and `events` are clamped to the signing key policy during
verification. Use any secret manager in place of `getenv()`. Do not expose the
server API key or signing secret to browser, mobile or widget code.

## License

MIT
