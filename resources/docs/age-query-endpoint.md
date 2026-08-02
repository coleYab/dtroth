---
title: Age Query Endpoint
order: 3
---

The Age Query Endpoint is the core of the Troth API. It verifies a single age query against a subject identity and returns a boolean result.

## Endpoint

```
POST /v1/verify/age
```

## Request

The request body is JSON. All fields are required.

| Field | Type | Required | Description |
| --- | --- | --- | --- |
| `age` | integer | yes | The age to compare against. Must be between 1 and 130. |
| `type` | enum | yes | The operator: `equal_above` (e.g. "18+") or `equal_below` (e.g. "16-"). |
| `subject` | string | yes | The identifier of the person to verify. In the sandbox, one of the mock test identities (see the [Sandbox](/sandbox)). |

The request must also include an `Authorization: Bearer <secret_key>` header — see [Authentication](authentication).

## Example request

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
console.log(data.result); // true or false
```

```python
import requests

response = requests.post(
    'https://api.troth.dev/v1/verify/age',
    headers={'Authorization': 'Bearer sk_sandbox_...'},
    json={'age': 18, 'type': 'equal_above', 'subject': 'fayda-test-a'},
)

data = response.json()
print(data['result'])
```

## Response

On success the API returns `200` with the following JSON body:

| Field | Type | Description |
| --- | --- | --- |
| `result` | boolean | Whether the query matched the subject's verified age. |
| `query_id` | string | A unique identifier for this verification, useful for audits and support. |
| `verified_at` | string | ISO 8601 timestamp of when the verification was performed. |
| `expires_at` | string | ISO 8601 timestamp after which the result should be re-verified. |

## Example response

```json
{
  "result": true,
  "query_id": "6f0e2a3b-9c1d-4e5f-a678-b90123c4d5e6",
  "verified_at": "2026-08-02T12:00:00+00:00",
  "expires_at": "2026-10-31T12:00:00+00:00"
}
```

## Errors

Errors are returned with a non-2xx status and a JSON body in the shape `{"error": {"code": "...", "message": "..."}}`.

| Code | Status | Meaning |
| --- | --- | --- |
| `invalid_key` | 401 | Missing or invalid bearer token. |
| `preset_not_allowed` | 403 | The requested query is not enabled for this application. |
| `validation_error` | 422 | Malformed request body. |
| `too_many_requests` | 429 | Rate limit exceeded. |
| `identity_provider_unavailable` | 501 | Production verification is not available yet. |

See [Error Codes](error-codes) for details.
