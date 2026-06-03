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

$perkamo = new Client(
    baseUrl: 'https://api.perkamo.com',
    apiKey: getenv('PERKAMO_SECRET_KEY'),
);

$result = $perkamo->emit(
    userId: 'customer_123',
    event: 'purchase.completed',
    context: [
        'order_id' => 'order_1092',
        'amount' => 12900,
        'currency' => 'CZK',
    ],
    transactionId: 'order_1092',
);
```

The client signs mutating requests with:

- `x-perkamo-api-key`
- `x-perkamo-timestamp`
- `x-perkamo-signature`

Reserved server-computed context keys such as `xp`, `wallet`, `wallets`,
`level`, `perks`, `rewards` and `achievements` are rejected before a request is
sent.
