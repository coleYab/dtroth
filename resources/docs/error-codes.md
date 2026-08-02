---
title: Error Codes
order: 5
---

All errors share the same JSON shape:

```json
{
  "error": {
    "code": "preset_not_allowed",
    "message": "No enabled preset matches the query \"18+\"."
  }
}
```

## Codes

| Code | Status | Description |
| --- | --- | --- |
| `invalid_key` | 401 | The bearer token is missing, invalid, revoked, or expired. |
| `preset_not_allowed` | 403 | The requested age query is not enabled for this application. Enable it in **App settings → Verification settings**. |
| `unknown_subject` | 404 | The subject identifier does not match a known test identity. |
| `validation_error` | 422 | The request body is malformed — for example an out-of-range `age` or an invalid `type`. |
| `too_many_requests` | 429 | You have exceeded the rate limit. See [Rate Limits](rate-limits). |
| `identity_provider_unavailable` | 501 | Production verification is not available yet. Use a sandbox key. |

## Handling errors

- Retry `429` responses after the delay indicated in the `Retry-After` header, backing off if the limit persists.
- `422` indicates a client bug — fix the request before retrying.
- `403 preset_not_allowed` is a configuration issue: enable the query in verification settings, then retry.
- `401` usually means the key is wrong, revoked, or expired — rotate it from **API keys**.
