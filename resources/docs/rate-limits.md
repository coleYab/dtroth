---
title: Rate Limits
order: 7
---

Rate limits protect the API from accidental or malicious traffic and keep latency predictable for everyone.

## Limits

| Endpoint                        | Limit                                        |
| ------------------------------- | -------------------------------------------- |
| `POST /v1/verify/age`           | 60 requests per minute per IP                |
| `POST /v1/verify/session`       | 60 requests per minute per IP                |
| `GET /v1/verify/{query_id}`     | 60 requests per minute per IP                |
| `POST /v1/consents/{id}/revoke` | 60 requests per minute per IP                |
| Sandbox tool                    | 30 requests per minute per developer account |

All public endpoints share one limiter, so a request to any endpoint counts against the same per-minute budget. The limit is configurable via the `VERIFY_API_REQUESTS_PER_MINUTE` environment variable.

## Headers

Every API response includes the state of your limit:

| Header                  | Description                                        |
| ----------------------- | -------------------------------------------------- |
| `X-RateLimit-Limit`     | The maximum number of requests allowed per minute. |
| `X-RateLimit-Remaining` | How many requests remain in the current window.    |
| `X-RateLimit-Reset`     | Epoch seconds when the window resets.              |

## Exceeding the limit

When the limit is exceeded, the API responds with `429 too_many_requests`, a `Retry-After` header telling you how many seconds to wait, and `X-RateLimit-Reset` with the epoch time the window resets:

```json
{
    "error": {
        "code": "too_many_requests",
        "message": "Too many requests. Please retry later."
    }
}
```

Retry after the time in `Retry-After` (or until `X-RateLimit-Reset`) — requests made before the window resets are still rejected.

## Best practices

- Respect `Retry-After` and implement exponential backoff with jitter for retries.
- Cache verification results in your application for the duration of `expires_at` — most queries do not need to hit the API on every page view.
- Use the sandbox for load testing instead of production keys.
