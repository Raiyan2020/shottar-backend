# Backend status — for the Flutter team

**Date:** 13 September 2026 · Base URL: `https://shottarapp.com/api`

Covers three new, **opt-in** endpoints: instant payment status check, progressive
PDF loading, and mark-notification-as-read. Nothing existing was changed.

> **Short version:** three new endpoints below. All are additive — every
> response your app already reads (login, orders, `material-file/{type}/{id}`,
> the payment redirect pages, notification list) is untouched, byte-for-byte.
> You only need to change anything if you want to use one of these.

---

## 1) `GET /order/{id}/check-payment` — instant payment confirmation

**Why:** a customer reported paying (money deducted) but the order stayed
`pending` in the dashboard. Root cause was the payment-gateway redirect
sometimes not completing reliably on mobile. The backend now also verifies
independently via a real MyFatoorah webhook, but that can take a few seconds —
this endpoint lets the app ask **right now**, the moment the user returns from
the payment screen, instead of waiting.

**Auth:** required (`Authorization: Bearer <token>`), same as every other
endpoint in this group.

**Request**

```
GET /api/order/{id}/check-payment
Authorization: Bearer <token>
lang: ar | en   (optional, defaults to en)
```

`{id}` is the order id you already have from `POST /order` (`order_id` in that
response). No body.

**Response — 200**

```json
{
  "status": true,
  "data": {
    "order_id": 427,
    "payment_status": "paid"
  },
  "message": "تم التحقق من حالة الدفع."
}
```

`payment_status` is one of `pending`, `paid`, `failed` — the same values the
order already carries, just read fresh. If MyFatoorah confirms the payment,
this call updates the order to `paid` server-side before responding.

**Response — 404** (order doesn't exist, or belongs to a different user)

```json
{ "status": false, "message": "الطلب غير موجود." }
```

**Suggested use:** call it once right after the payment webview closes and you
land back in the app, then show the result. Safe to call more than once —
it's a no-op once the order is already `paid` or `failed`.

---

## 2) Progressive PDF loading (notes / exams)

**Why:** you asked why opening a large PDF feels slow — the current
`material-file/{type}/{id}` endpoint downloads the whole file before the
viewer can show page 1. These two new endpoints let a native PDF viewer stream
the file and jump to any page immediately via HTTP range requests, without
downloading it all first.

**This is entirely optional.** `material-file/{type}/{id}` still works exactly
as before — nothing forces you to switch.

### 2.1 `GET /material-file/{type}/{id}/signed-url`

Same access rules as the existing endpoint (subscriber check, `is_free`
override). Returns a link, not the file itself.

**Request**

```
GET /api/material-file/note/123/signed-url
Authorization: Bearer <token>
lang: ar | en   (optional, defaults to ar)
```

`{type}` is `note` or `exam`, `{id}` is the material/exam id — same as today.

**Response — 200**

```json
{
  "status": true,
  "data": { "url": "https://shottarapp.com/api/material-file/note/123/download?user_id=55&signature=...&expires=..." },
  "message": "تم إنشاء رابط التحميل."
}
```

- The link **does not expire on a timer.** Access is re-checked live on every
  request to it (see below), so it works for however long the student is
  actually reading, but stops immediately if their subscription is cancelled.
- No `Authorization` header needed on the link itself — it's meant to be
  handed directly to a native PDF viewer component, which can't attach headers.

**Errors:** `404` file not found, `403` subscribers only — same JSON shape as
today's endpoint.

### 2.2 `GET /material-file/{type}/{id}/download` (the signed link itself)

You don't construct this URL — you get it verbatim from §2.1's response and
hand it to your PDF viewer as-is. Supports HTTP `Range` requests
(`206 Partial Content`), so the viewer can request just the pages it needs.
Verified directly against production: a `Range: bytes=0-2047` request returns
`206 Partial Content` with only ~2KB back in under a second, versus ~10s for
the full ~1.2MB file with no `Range` header.

- Valid signature + still has access → PDF bytes, `Accept-Ranges: bytes`.
- Invalid/tampered link → `403 { "status": false, "message": "الرابط غير صالح أو منتهي الصلاحية." }`
- Access revoked since the link was issued (e.g. subscription ended) → `403`
  subscribers-only message, same as §2.1.

**⚠️ Important: use this endpoint, not `material-file/{type}/{id}` with a
manual `Range` header, for progressive loading.** Both technically support
`206 Partial Content` — but a real PDF viewer doesn't make one request, it
makes *many* range requests internally as the user scrolls, through its own
networking code, not through whatever request object your app built.
`material-file/{type}/{id}` requires `Authorization: Bearer <token>` on every
one of those internal requests, and whether a given PDF-viewer
library/plugin re-attaches that header to *all* of its own internal range
fetches is inconsistent and library-dependent — it can silently break on some
pages/versions. The signed `/download` link needs **no header at all** (the
token is baked into the URL's `signature` instead), so every range request the
viewer makes internally just works, regardless of the library.

**Suggested use:** if your PDF viewer library supports opening a URL directly
(with range-request support), call §2.1 once, then open the returned `url` in
the viewer instead of downloading via §2.1's older sibling endpoint.

**Note on total load time:** Range support doesn't make a *full* download
faster — it only helps once your PDF viewer actually requests small ranges
instead of the whole file. If the library you end up using still downloads
the entire file up front (no per-page range requests), you'll still see the
full ~10s for a ~1.2MB file regardless of which endpoint you use — that part
depends on picking a PDF viewer package that genuinely streams via ranges.

---

## 3) `POST /notification/{id}/read` — mark a notification as read

**Why:** `GET /notification` already returns the list with `is_read`; this is
the missing write side so the app can mark one as read once the user opens it.

**Auth:** required.

**Request**

```
POST /api/notification/{id}/read
Authorization: Bearer <token>
```

No body. `{id}` is the notification id from the `GET /notification` list. Works
for your own notifications and for broadcast (`user_id = null`) ones.

**Response — 200**

```json
{ "status": true, "message": "Notification marked as read" }
```

(No `data` key — there's nothing to return, just a confirmation.)

**Response — 404** if the id doesn't exist or belongs to someone else
(standard Laravel `firstOrFail` 404, not a custom JSON body — worth handling
generically like any other 404 you already get elsewhere).

---

## 4) Your checklist

- [ ] Optional: call §1 right after the payment webview closes, instead of
      polling or just trusting the redirect
- [ ] Optional: switch PDF viewing to §2 if you want faster/partial loading —
      no rush, the old endpoint isn't going away
- [ ] Add the "mark as read" call (§3) wherever the user opens a notification

**Nothing here is required to keep the app working as-is.** Everything is
additive; ship it whenever it's convenient.
