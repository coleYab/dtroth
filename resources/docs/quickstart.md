---
title: Quickstart
order: 1
---

This guide gets you from signup to your first successful age verification in about five minutes.

## Step 1 — Create an application

Applications are containers for your API keys, presets, and consent records.

1. Open [Applications](/apps) and click **New Application**.
2. Give it a name — for example `My Mobile App`.
3. Save. A sandbox environment is created for you automatically.

## Step 2 — Generate an API key

1. Open your application and go to **API keys** → **New API key**.
2. Name it `development` and create it.
3. Copy the secret key shown — it is displayed **only once**.

## Step 3 — Enable an age-query preset

1. Open **Verification settings** for your application.
2. Toggle on the queries you need — for example **18+** (`equal_above`).
3. Save. You can now verify that query; anything else is rejected with `403`.

## Step 4 — Make your first request

With your secret key in hand, send your first verification. The sandbox resolves identities from mock test identities — `fayda-test-a` is 20 years old.

```curl
curl -X POST "https://api.troth.dev/v1/verify/age" \
  -H "Authorization: Bearer sk_sandbox_..." \
  -H "Content-Type: application/json" \
  -d '{"age":18,"type":"equal_above","subject":"fayda-test-a"}'
```

```js
const response = await fetch('https://api.troth.dev/v1/verify/age', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer sk_sandbox_...',
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({ age: 18, type: 'equal_above', subject: 'fayda-test-a' }),
});

const data = await response.json();
console.log(data.result);
```

```python
import requests

response = requests.post(
    'https://api.troth.dev/v1/verify/age',
    headers={'Authorization': 'Bearer sk_sandbox_...'},
    json={'age': 18, 'type': 'equal_above', 'subject': 'fayda-test-a'},
)

print(response.json()['result'])
```

## Step 5 — Handle the response

A successful response looks like this:

```json
{
  "result": true,
  "query_id": "6f0e2a3b-9c1d-4e5f-a678-b90123c4d5e6",
  "verified_at": "2026-08-02T12:00:00+00:00",
  "expires_at": "2026-10-31T12:00:00+00:00"
}
```

- `result` tells you whether the user meets the threshold. Gate your feature on it.
- Keep `query_id` for your records and audits.
- Cache the result until `expires_at` instead of re-verifying on every request.

## Next steps

- Read the [Age Query Endpoint](/docs/age-query-endpoint) reference for the full schema.
- Configure [Webhooks](/docs/webhooks) to react to expirations and consent revocations.
- Check [Error Codes](/docs/error-codes) and [Rate Limits](/docs/rate-limits) before going live.

Or skip the setup entirely and try it right now in the sandbox.
