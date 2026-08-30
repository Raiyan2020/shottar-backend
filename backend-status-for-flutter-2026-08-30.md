# Backend status — for the Flutter team

**Date:** 30 August 2026 · Base URL: `https://shottarapp.com/api`

Covers: `is_reviewer` (Apple App Review), subject durations (§6.4), and note/exam
PDFs (§6.5).

> **Short version:** `is_reviewer` and the durations are done and live — please
> re-test both. The PDFs are a data loss, not a code bug; 25 of 198 work today,
> the rest need a backup restore.
>
> **You do not need to touch the admin dashboard.** All admin-side steps are on
> the backend team. Your part is testing only.

---

## 1) `is_reviewer` — Apple App Review mode ✅ live

Everything in §3 of your document is implemented and deployed.

### 1.1 Verified against your §5 acceptance matrix

| # | Request | `is_reviewer` | `data` |
|---|---|---|---|
| 1 | `device-type: ios` + `app-version: 1.1.5` (while field is set) | `true` | `apple_iap` only |
| 2 | `device-type: ios` + `app-version: 1.1.4` | `false` | full list |
| 3 | `device-type: android` + `app-version: 1.1.5` | `false` | full list |
| 4 | no headers | `false` | full list |
| 5 | `ios` + `1.1.5` after the field is cleared | `false` | full list |

`is_reviewer` is always present as a real boolean, top-level, sibling of
`status` and `data` — never omitted, never `null`.

### 1.2 §3.4 — you were right, `apple_iap` did not exist

It has been created and activated:

| Field | Value |
|---|---|
| `slug` | `apple_iap` (lowercase, underscore — exact) |
| `name_en` | `Apple In-App Purchase` |
| `name_ar` | `شراء داخل التطبيق (آبل)` |
| `status` | active |

Match on the slug as you planned. `name` stays translated per `lang`.

### 1.3 Current state — expect `false` right now

`ios_review_version` is intentionally **empty**, so every request returns
`is_reviewer: false`. That is case 5 — the correct production state.

It gets set to `1.1.5` immediately before the build is submitted to Apple, and
cleared once Apple approves. Both are backend-side actions. **Nothing is
required from you, and no app release is needed.**

### 1.4 Two things we added beyond the spec

1. **In reviewer mode `apple_iap` is returned even if it is deactivated in the
   admin panel.** You warned in §6 that an empty list leaves the reviewer unable
   to buy; this removes that risk regardless of admin state.

2. **The old `is_reviewer` was client-controlled and wrong.** It read
   `?is_reviewer=1` straight off the request and filtered to **cash** — any
   client could set it, and cash for digital content is a rejection too. It is
   now computed server-side only. A forged `?is_reviewer=1` has no effect
   (tested).

### 1.5 ⚠️ One question for you

Production payment method slugs are **`knet1`** and **`12345`** — not `knet` /
`visa`. They were edited in the admin panel at some point.

**Does the app match on those slugs anywhere?** If it does, tell us before we
touch them. If you only match `apple_iap`, nothing to do.

---

## 2) §6.4 — subject durations ✅ fixed, please re-test

All **1038** lessons now carry real durations pulled from Vimeo. Zero failures,
62 subjects affected.

Two separate bugs were writing zero:

1. The dashboard asked Vimeo for the duration **immediately after upload**,
   while Vimeo was still transcoding — it returns `0` until transcoding
   finishes, and that `0` was stored as final.
2. A second upload path returned an **undefined variable** for the duration, so
   it always stored `0` as well.

### What you get now

```json
{
  "total_duration": "12 ساعة 13 دقيقة",
  "total_duration_seconds": 44015,
  "hours": 12,
  "minutes": 13
}
```

- ⚠️ **The unit is SECONDS, not minutes.** Your §6.4 asked for minutes — Vimeo
  reports seconds and that is what is stored. You do not need to convert
  anything: `hours`, `minutes` and `total_duration` are computed server-side.
- **`total_duration_seconds` is new** — a plain integer, so you can sort or
  compute without parsing the formatted string.
- `duration` (the per-subject free-text field) is unrelated and still usually
  `null`. Ignore it; use the fields above.

New uploads fix themselves: a job re-checks Vimeo after transcoding, retrying
with backoff until a real duration comes back.

---

## 3) §6.5 — note/exam PDFs 🔴 data loss, restore in progress

**Your diagnosis was right, and the endpoint is behaving correctly.** The 404s
are the server truthfully reporting that the file is not on disk.

### What happened

On **2026-08-10** a commit added a copy-then-`@unlink` block inside
`stored_file_url()` / `image_url()` — helpers whose only job is to *build a
URL*. They ran on every API response that serialised a note or exam. Combined
with `'throw' => false` on the storage disk, a silently failing copy was still
followed by deleting the source.

The evidence is unambiguous:

| Group | Count | Upload date range |
|---|---|---|
| Files still present | 25 | 2026-08-10 → 2026-08-29 |
| Files gone | 173 | 2025-09-03 → **2026-06-06** |

A clean split with no overlap, and the boundary is exactly the date that commit
landed. Everything uploaded before it was destroyed on first view; everything
after went straight to the new location and survived.

### Status

- ✅ **Cause removed.** Nothing deletes files any more — no further loss.
- ✅ **The download path is proven working.** End-to-end test on a note whose
  file exists: `200` + `Content-Type: application/pdf` + a valid PDF on disk.
  Missing file → `404` JSON. Paid file, non-subscriber → `403` JSON.
- ⏳ **173 files need a backup restore.** Searched the entire server — home
  directory, `public_html`, cPanel trash, all archives, `/backup`. They are not
  on this machine. We are working with the host on an off-disk backup from
  between 2026-06-06 and 2026-08-10.

### Nothing changes for you

`download_url` keeps returning the PDF bytes with `Content-Type:
application/pdf` for authorised users, exactly as you asked. When the files come
back, they will just start working — no app change.

### One request

Your current error text is **"تعذر تحميل الملف، حاول مرة أخرى"**, which tells the
student to retry something that can never succeed. The API already returns a
specific reason in the body:

| Status | `message` | Meaning |
|---|---|---|
| `404` | `الملف غير موجود على السيرفر.` | File is gone — **retrying will not help** |
| `403` | `هذا الملف متاح للمشتركين فقط.` | Not subscribed — send them to checkout |
| `401` | `انتهت الجلسة، يرجى تسجيل الدخول مرة أخرى.` | Session ended — go to login |

Showing `response.data['message']`, and hiding the retry button on `404`, would
be a real improvement.

---

## 4) Your checklist

- [ ] Re-test `POST /subjects` — durations should be correct now (§2)
- [ ] Confirm you read `total_duration_seconds` as **seconds**
- [ ] Answer the slug question in §1.5 (`knet1` / `12345`)
- [ ] Optional: surface the server's `message` on PDF download failures (§3)
- [ ] `is_reviewer` — no action needed; we will tell you when the field is set
      so you can run your case 1 and 5 curls against the live API

**Nothing on this list requires the admin dashboard.**
