---
title: Introduction
order: 1
---

The Troth Age Verification API lets you confirm whether a user meets an age threshold — for example **18+** — without ever receiving or storing their date of birth.

## How it works

Your application sends an age query such as `{ "age": 18, "type": "equal_above" }`. The API resolves the subject's verified age against a trusted identity provider and returns a single boolean result, a `query_id` for your records, and an expiry for the verification.

You never see the subject's actual age — only the answer to the query you asked.

## Key concepts

- **Age query** — a request pair of an age and an operator (`equal_above` or `equal_below`), e.g. "18+" or "16-".
- **Preset** — an age query your application has explicitly enabled in **Verification Settings**. Requests for queries that are not enabled are rejected with `403 preset_not_allowed`.
- **Environment** — every key belongs to a sandbox or production environment. Sandbox keys verify against mock test identities; production keys verify against real identity provider data.
- **Consent** — end users grant consent for age verification through your application, and you keep records of that consent alongside the verification results.

## Base URL

All API requests are made to:

```
https://api.troth.dev/v1
```

In this sandbox environment the API is available locally. See [Authentication](authentication) for how to send your secret key.

## First request

The fastest way to try the API is the [Quickstart](quickstart) guide, or the interactive [Sandbox](/sandbox) where you can send live requests against test identities without writing any code.
