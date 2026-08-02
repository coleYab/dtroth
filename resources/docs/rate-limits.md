---
title: Rate Limits
order: 6
---

Rate limits protect the API from accidental or malicious traffic and keep latency predictable for everyone.

## Limits

| Scope | Limit |
| --- | --- |
| Verification API | 30 requests per minute per API key. |
| Sandbox tool | 30 requests per minute per developer account. |

## Exceeding the limit

When a limit is exceeded, the API responds with `429 too_many_requests` and a `Retry-After` header telling you how many seconds to wait:

```json
{
  "error": {
    "code": "too_many_requests",
    "message": "Too many requests. Please retry later."
  }
}
```

## Best practices

- Respect `Retry-After` and implement exponential backoff with jitter for retries.
- Cache verification results in your application for the duration of `expires_at` — most queries do not need to hit the API on every page view.
- Use the sandbox for load testing instead of production keys.
