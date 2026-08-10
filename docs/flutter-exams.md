# Flutter — Exams (الامتحانات)

## Overview

Each **subject** can have multiple **exams**.  
Every exam is a **PDF file** only.

Exams are returned inside the **subject details** API (same endpoint you already use for lessons/notes).

There is **no separate exams list endpoint**.

---

## Endpoint

```http
GET /api/subjects/{id}
Authorization: Bearer {token}
lang: ar | en
```

| Header | Required | Notes |
|--------|----------|--------|
| `Authorization` | Yes | Bearer token |
| `lang` | No | Default `ar`. Controls `title` language |

---

## Response shape (relevant part)

```json
{
  "status": true,
  "message": null,
  "data": {
    "id": 77,
    "name": "اللغة الإنجليزية",
    "image": "https://shottarapp.com/storage/images/xxx.jpg",
    "total_lessons": 12,
    "total_duration": "5 س 20 د",
    "price": "10.000",
    "is_purchased": true,
    "progress_percent": 40.0,
    "sections": [
      {
        "id": 1,
        "name": "الوحدة الأولى",
        "lessons": [],
        "notes": []
      }
    ],
    "exams": [
      {
        "id": 1,
        "title": "امتحان منتصف الفصل",
        "file": "https://shottarapp.com/storage/exams/abc123.pdf",
        "type": "exam",
        "is_free": false
      },
      {
        "id": 2,
        "title": "امتحان تجريبي",
        "file": "https://shottarapp.com/storage/exams/def456.pdf",
        "type": "exam",
        "is_free": true
      }
    ]
  }
}
```

---

## Exam object fields

| Field | Type | Description |
|-------|------|-------------|
| `id` | `int` | Exam ID |
| `title` | `string` | Localized title (`lang` header) |
| `file` | `string \| null` | Full PDF URL |
| `type` | `string` | Always `"exam"` |
| `is_free` | `bool` | Free exam or requires purchase |

---

## Flutter model example

```dart
class Exam {
  final int id;
  final String title;
  final String? file;
  final String type;
  final bool isFree;

  Exam({
    required this.id,
    required this.title,
    required this.file,
    required this.type,
    required this.isFree,
  });

  factory Exam.fromJson(Map<String, dynamic> json) {
    return Exam(
      id: json['id'] as int,
      title: json['title'] as String? ?? '',
      file: json['file'] as String?,
      type: json['type'] as String? ?? 'exam',
      isFree: json['is_free'] as bool? ?? false,
    );
  }
}
```

Parse from subject details:

```dart
final exams = (data['exams'] as List? ?? [])
    .map((e) => Exam.fromJson(e as Map<String, dynamic>))
    .toList();
```

---

## UI / behavior guidelines

1. Show an **Exams** tab/section on the subject details screen (same level as sections/lessons/notes — **not inside a section**).
2. Each exam row:
   - title
   - free badge if `is_free == true`
   - open/download PDF button
3. Open PDF using `file` URL (in-app PDF viewer or external browser).
4. Access rules (same idea as notes):
   - if `is_free == true` → allow open
   - if `is_free == false` → allow only when `is_purchased == true`
   - otherwise show subscribe/pay CTA
5. If `exams` is empty `[]` → hide the section or show empty state.
6. If `file` is `null` → disable open button.

---

## Important notes

- Exams are **subject-level**, not under `sections`.
- File type is always **PDF**.
- Only **active** exams are returned (`status = true` on backend).
- URL format example:  
  `https://shottarapp.com/storage/exams/{hash}.pdf`

---

## Quick checklist for Flutter

- [ ] Parse `exams` from `GET /api/subjects/{id}`
- [ ] Add Exams UI on subject details
- [ ] Open PDF from `file`
- [ ] Respect `is_free` + `is_purchased`
- [ ] Handle empty list / null file
- [ ] Pass `lang` header for title localization
