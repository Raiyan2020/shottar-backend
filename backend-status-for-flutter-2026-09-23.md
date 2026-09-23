# Backend status — for the Flutter team

**Date:** 23 September 2026 · Base URL: `https://shottarapp.com/api`

Covers: one new endpoint (device token cleanup), two bug fixes in the
lesson-section Challenges flow, two new fields on exams (solution video), a
new, now-populated `category` field on notifications, and two known/in-progress
issues you should be aware of even though no client change is needed for them
yet.

> **Short version:** one new opt-in endpoint (`DELETE /device-token`), two
> small response-shape changes in the Challenges feature (`/challenge/start`,
> `/challenge/{subject_id}`) that make existing fields more correct, two new
> additive fields on exams (`solution_video`, `solution_video_status`), and a
> new `category` field on notifications (`general`/`educational`/
> `achievement`/`transaction`) that's now actually populated by real events —
> nothing renamed, nothing removed except a field that should never have been
> read.

---

## 1) `DELETE /api/device-token` — new endpoint

**Why:** there was a `POST /device-token` to push a new/refreshed FCM token,
but nothing to clear it. If a user disables notifications from inside the app,
or logs out on a shared device, the backend kept sending pushes to that
device's token forever. This endpoint clears it.

**Auth:** required (`Authorization: Bearer <token>`).

**Request**

```
DELETE /api/device-token
Authorization: Bearer <token>
```

No body.

**Response — 200**

```json
{ "status": true, "data": { "device_token_deleted": true } }
```

**Response — 401** if not authenticated (standard `unauthenticated` error,
same shape as everywhere else).

**Suggested use:** call it when the user turns off notifications in the app's
settings. Logout does **not** call this automatically on the backend — if you
want the token cleared on logout too, call this endpoint right before/after
your existing `POST /logout` call.

---

## 2) `POST /challenge/start` — `is_correct` removed from the initial answers

**Why:** this was a real bug, not a design change. When a student started a
lesson-section challenge, the questions payload included `is_correct` on every
answer option — meaning the correct answer was visible in the response before
the student answered anything. It's removed now.

**What changes for you:** if your code ever read `is_correct` from the
`answers` array in the `/challenge/start` response, it will no longer be
there. It was never meant to be used this way — the correct answer is (and
was already) available after answering, from `/challenge/store-answer`,
`/challenge/finish`, and `/challenge/result`, where `is_correct` /
`correct_answer_id` are still present exactly as before.

**Before**
```json
"answers": [{ "id": 1, "title_ar": "...", "is_correct": true }]
```

**Now**
```json
"answers": [{ "id": 1, "title_ar": "..." }]
```

Nothing else in that response changed (`session_id`, `started_at`,
`challenge_duration`, `questions[].id`, `questions[].title_ar` /
`title_en` are all unchanged).

---

## 3) `POST /challenge/store-answer` — new possible error

**Why:** the backend didn't verify that the `question_id` you send actually
belongs to the challenge session (`session_id`) you're answering. In the
normal app flow this can't happen, but as a safety fix, submitting a
`question_id` from a different lesson section than the one the session
started for is now rejected instead of silently corrupting the score.

**New response (only if this mismatch happens — should never occur in normal
use):**

```json
{
  "status": false,
  "message": "هذا السؤال لا يخص هذه الجلسة."
}
```

No change to the success response shape, and no change needed on your side —
this is just documented in case you ever see it in logs, so you know it means
a `question_id`/`session_id` mismatch rather than a random server error.

---

## 4) `GET /challenge/{subject_id}` — `completed_lessons` / `status_label` fix

**Why:** a lesson section with **zero** lessons in it was incorrectly reported
as `"completed_lessons": false, "status_label": "lessons_not_completed"`, even
though there was nothing to complete and the student could already start the
challenge (the start endpoint itself always allowed it in this case — only the
list endpoint's status flag was wrong).

**What changes for you:** for a section with `"total_lessons": 0`, you'll now
correctly get:

```json
{ "completed_lessons": true, "total_lessons": 0, "viewed_lessons": 0, "status_label": "ready_for_challenge" }
```

If your UI was hiding/disabling the "start challenge" button based on
`status_label`/`completed_lessons` for such sections, it should now correctly
enable it. No other fields changed.

---

## 5) Known issue — "today's goal" (`daily_goal` / `daily_goal_done`) looks frozen

**Not fixed yet — heads-up only, no client change needed or possible.**

`daily_goal` and `daily_goal_done` (returned on the user object from
`/login`, `/register`, `/show-profile`, etc.) are currently a lifetime
cumulative counter, not a per-day counter — there's no daily reset. So a
user's progress can appear to "stick" at the same number for several days
instead of resetting each morning. This is a backend data-model bug we're
aware of and plan to fix (recomputing `daily_goal_done` from actual
same-day activity instead of an ever-incrementing column) — the field names
and response shape will stay the same when it's fixed, so no app change will
be required. Just don't build any new logic that assumes this value resets
daily until you hear otherwise.

---

## 6) Exams — new `solution_video` / `solution_video_status` fields

**Why:** teachers can now add exams too (previously admin-only), and the
workflow is two steps — the PDF is uploaded first, and the solution video is
added later, sometimes days after. The exam object now carries two new
fields everywhere it's returned (`SubjectDetailResource.exams[]`, and
anywhere else `ExamResource` is used).

```json
{
  "id": 1,
  "title": "اختبار الوحدة الأولى",
  "file": "https://.../storage/exams/....pdf",
  "type": "exam",
  "download_url": "https://.../api/material-file/exam/1",
  "solution_video": null,
  "solution_video_status": null,
  "is_free": false,
  "lesson_section_id": null,
  "lesson_section_name": null
}
```

- `solution_video_status` is one of `null` (no video added yet), `"processing"`
  (teacher started the upload, Vimeo is still returning it), or `"done"`
  (ready to play).
- `solution_video` is only ever a real URL when `solution_video_status` is
  `"done"` — otherwise it's `null`, including while `"processing"`. Don't try
  to play it until it's `"done"`.
- No existing field changed shape — this is purely additive. Safe to ignore
  if you don't want to show a solution video yet; just don't render a
  "watch solution" button unless `solution_video_status === "done"`.

---

## 7) Notifications — new `category` field, now actually populated

**Why:** the notifications list already had a `type` field, but `type` was
only ever `"all"`, `"user"`, or `"unpaid"` — that's audience targeting, not a
content category. If your notifications screen has tabs like "تعليمية"
(educational) / "إنجازات" (achievement) / "معاملات" (transaction), there was
no field to filter on and nothing ever populated those buckets. That's fixed
now with a new, separate `category` field.

**`GET /notification`** — every item now includes `category`:

```json
{
  "id": 1,
  "user_id": 5,
  "order_id": 12,
  "type": "user",
  "category": "transaction",
  "title": "تم تأكيد اشتراكك بنجاح",
  "body": "تم الدفع وتفعيل المواد اللي اشتركت فيها. بالتوفيق في مذاكرتك!",
  "data": null,
  "is_read": false,
  "created_at": "2 minutes ago"
}
```

`category` is one of: `"general"` (default — includes anything the admin
sends manually without picking a category, and everything sent before this
change), `"educational"`, `"achievement"`, `"transaction"`.

**Optional query param — filter by category:**

```
GET /notification?category=achievement
```

Pass `category` with any of the four values above to get only that bucket
(useful for building the tabs directly from separate calls instead of
filtering client-side). Omit it (or pass an unrecognized value) to get
everything, exactly like before — this param is additive and 100% optional.

**What now actually creates each category, automatically (no admin action
needed):**
- `transaction` — sent to a user the moment their order is confirmed paid
  (regular checkout, cash/offline, 100%-discount/free orders, and Apple IAP
  all covered).
- `achievement` — sent once, the moment a student's daily-challenge answers
  push `daily_goal_done` to reach `daily_goal` for the first time that they
  cross the threshold. Not sent again on later answers past the goal.
- `educational` — sent to every user with a paid order containing that
  subject, when a teacher/admin publishes a new active lesson in it.

Admin-sent broadcast/manual notifications (the existing "Send Notification"
dashboard page) can now also tag a category from a dropdown — defaults to
`general` if the admin doesn't pick one, so old behavior is unchanged unless
they opt in.

**What changes for you:** nothing required. `category` is purely additive —
if you don't build category tabs yet, ignore the field. When you do, filter
either client-side on `category` or via the new `?category=` query param.

---

## 7.1) Note — a user can receive a push even when the admin dashboard says it "failed"

**Why:** when an admin sends a notification to one specific user (not a
broadcast), the dashboard sometimes shows a delivery error even though the
notification arrives on that user's phone. Root cause: Firebase can mark a
device token as stale/invalid in its response for reasons that don't always
mean the token is actually dead — a message can still reach the device
around the same time. When this happens, the backend already clears that
stale `device_token` from the user's account server-side (same as it always
did for genuinely dead tokens).

**This is what the existing `DELETE /api/device-token` endpoint (see §1) is
for** — no new endpoint was added. If a user reports they've stopped getting
push notifications after a case like this, the fix is for the app to
register a fresh token, which already happens automatically the next time it
calls `POST /device-token` (login, app open, or whenever you already
register/refresh the token). If your settings screen has a manual
"disable/clear notifications" action, it's already wired to
`DELETE /device-token` per §1 — that's the same action that resolves this,
nothing extra needed.

**What changes for you:** nothing required — just a heads-up in case a QA
tester reports "admin says it failed but I got the notification anyway."
That's expected and harmless: the row already exists in `GET /notification`
regardless of push delivery status, so the in-app notification list is never
affected by this — only the OS-level push banner delivery timing/reporting
is.

---

## 9) Note on notes/exams occasionally 404'ing when viewing

If you've seen reports of a note or exam PDF 404'ing right after upload for
some users, that turned out to be a server storage-configuration issue
(`php artisan storage:link` symlink), not an app bug — being addressed
server-side. As a reminder, always use the `download_url` field on a note/exam
(which goes through `material-file/{type}/{id}`) rather than the raw `file`
field for viewing/downloading — `download_url` doesn't depend on that symlink
and gives you a real `404`/`403` with a proper JSON body instead of ever
silently serving broken content.

---

## 10) Your checklist

- [ ] Optional: call `DELETE /device-token` when a user disables notifications
      in-app, and/or alongside logout if you want the token cleared then too
- [ ] If anything in your code reads `is_correct` from `/challenge/start`'s
      `answers` array, remove that — it's no longer sent (and shouldn't have
      been relied on)
- [ ] If you have special-case UI for 0-lesson sections in the challenges
      list, double check it still makes sense now that `status_label` reports
      `ready_for_challenge` for them
- [ ] Optional: show a "watch solution" button on an exam when
      `solution_video_status === "done"`, using `solution_video`
- [ ] If your notifications screen has category tabs, wire them up to the new
      `category` field / `?category=` query param on `GET /notification` —
      the three real-content categories (`transaction`, `achievement`,
      `educational`) are now actually populated
- [ ] No action needed for §5, §7.1, and §9 — informational only

**Nothing here breaks existing flows.** The only field actually removed
(`is_correct` in `/challenge/start`) was a bug — the correct answer was never
meant to be visible before answering.
