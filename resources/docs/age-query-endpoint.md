---
title: Age Query Endpoint
order: 3
---

The Age Query Endpoint is the core of the Troth API. It verifies a single age query against a subject identity and returns a boolean result.

> **Prefer the hosted flow?** If you would rather redirect your users to a branded, Troth-hosted page instead of sending national IDs through your own servers, use [`POST /v1/verify/session`](hosted-flow) — the request body is identical to this endpoint.

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
| `subject` | string | yes | The identifier of the person to verify. In the sandbox, one of the mock test identities (see the [Sandbox](/sandbox)). In production, the national ID of the person to verify. |

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
| `provider_reference` | string | The identity provider's reference for this verification, useful when contacting support. |

## Example response

```json
{
  "result": true,
  "query_id": "6f0e2a3b-9c1d-4e5f-a678-b90123c4d5e6",
  "verified_at": "2026-08-02T12:00:00+00:00",
  "expires_at": "2026-10-31T12:00:00+00:00",
  "provider_reference": "fayda-3f9e2a5b-1c4d-4e6f-8a9b-0c1d2e3f4a5b"
}
```

## Errors

Errors are returned with a non-2xx status and a JSON body in the shape `{"error": {"code": "...", "message": "..."}}`.

| Code | Status | Meaning |
| --- | --- | --- |
| `invalid_key` | 401 | Missing or invalid bearer token. |
| `preset_not_allowed` | 403 | The requested query is not enabled for this application. |
| `identity_provider_declined` | 403 | The national ID presented is not valid for verification at this time. |
| `unknown_subject` | 404 | The subject does not match a known test identity (sandbox). |
| `subject_not_found` | 404 | The national ID does not match a known identity (production). |
| `validation_error` | 422 | Malformed request body. |
| `too_many_requests` | 429 | Rate limit exceeded. |
| `identity_provider_unreachable` | 502 | The identity provider could not be reached. |
| `identity_provider_timeout` | 504 | The identity provider took too long to respond. |

See [Error Codes](error-codes) for details.
