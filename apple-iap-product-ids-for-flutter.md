# Apple IAP — Product IDs list (for Flutter / App Store Connect)

Base URL: `https://shottarapp.com/api`

## Rules (important)

| Item | Value |
|------|--------|
| Type | **Non-Consumable** (not Consumable) |
| Product ID | Must match API `ios_product_id` **exactly** (case-sensitive) |
| Subject format | `com.raiyansoft.shottar.subject.{subject_id}` |
| Bundle format | `com.raiyansoft.shottar.bundle.g{grade_id}.s{semester_id}` |
| Bundle meaning | All subjects for that **grade × semester** |
| First IAP | Must be submitted with a **new app version** |
| Testing | Sandbox Apple ID |

---

## How Flutter uses them

1. Read `ios_product_id` from `POST /subjects` and `GET /subjects/{id}`
2. Read bundle id from `grade.ios_product_id`
3. StoreKit query = all non-null subject IDs + grade bundle ID
4. After purchase → `POST /api/order/apple/verify`

If `ios_product_id` is `null` → do not offer purchase for that item.

---

## Export from production DB (run in phpMyAdmin)

### A) Subjects

```sql
SELECT
  'subject' AS type,
  s.ios_product_id AS product_id,
  CONCAT(s.name_ar, ' — ', COALESCE(g.name_ar, ''), ' — ', COALESCE(sem.name_ar, '')) AS reference_name,
  s.price AS price_kwd,
  s.id AS subject_id,
  s.grade_id,
  s.semester_id
FROM subjects s
LEFT JOIN grades g ON g.id = s.grade_id
LEFT JOIN semesters sem ON sem.id = s.semester_id
WHERE s.status = 1
  AND s.ios_product_id IS NOT NULL
  AND s.ios_product_id != ''
ORDER BY s.grade_id, s.semester_id, s.id;
```

### B) Bundles (all materials)

```sql
SELECT
  'bundle' AS type,
  ibp.ios_product_id AS product_id,
  CONCAT('باقة كل المواد — ', g.name_ar, ' — ', sem.name_ar) AS reference_name,
  g.all_materials_price AS price_kwd,
  ibp.grade_id,
  ibp.semester_id
FROM ios_bundle_products ibp
JOIN grades g ON g.id = ibp.grade_id
JOIN semesters sem ON sem.id = ibp.semester_id
ORDER BY ibp.grade_id, ibp.semester_id;
```

### C) One list (subjects + bundles) — best to export CSV

```sql
SELECT * FROM (
  SELECT
    'subject' AS type,
    s.ios_product_id AS product_id,
    CONCAT(s.name_ar, ' — ', COALESCE(g.name_ar, ''), ' — ', COALESCE(sem.name_ar, '')) AS reference_name,
    s.price AS price_kwd
  FROM subjects s
  LEFT JOIN grades g ON g.id = s.grade_id
  LEFT JOIN semesters sem ON sem.id = s.semester_id
  WHERE s.status = 1
    AND s.ios_product_id IS NOT NULL
    AND s.ios_product_id != ''

  UNION ALL

  SELECT
    'bundle' AS type,
    ibp.ios_product_id AS product_id,
    CONCAT('باقة كل المواد — ', g.name_ar, ' — ', sem.name_ar) AS reference_name,
    g.all_materials_price AS price_kwd
  FROM ios_bundle_products ibp
  JOIN grades g ON g.id = ibp.grade_id
  JOIN semesters sem ON sem.id = ibp.semester_id
) t
ORDER BY type, product_id;
```

In phpMyAdmin: run query C → **Export** → CSV → send to Flutter / App Store team.

---

## Expected examples (already in DB)

### Subject examples

| product_id | meaning |
|------------|---------|
| `com.raiyansoft.shottar.subject.62` | Science — grade 9 (subject id 62) |
| `com.raiyansoft.shottar.subject.77` | English — subject id 77 |

### Bundle examples (14 rows)

| product_id |
|------------|
| `com.raiyansoft.shottar.bundle.g6.s1` |
| `com.raiyansoft.shottar.bundle.g7.s1` |
| `com.raiyansoft.shottar.bundle.g8.s1` |
| `com.raiyansoft.shottar.bundle.g9.s1` |
| `com.raiyansoft.shottar.bundle.g10.s1` |
| `com.raiyansoft.shottar.bundle.g10.s2` |
| `com.raiyansoft.shottar.bundle.g11.s1` |
| `com.raiyansoft.shottar.bundle.g11.s2` |
| `com.raiyansoft.shottar.bundle.g12.s1` |
| `com.raiyansoft.shottar.bundle.g12.s2` |
| `com.raiyansoft.shottar.bundle.g13.s1` |
| `com.raiyansoft.shottar.bundle.g13.s2` |
| `com.raiyansoft.shottar.bundle.g14.s1` |
| `com.raiyansoft.shottar.bundle.g14.s2` |

Approx counts: **~65 subject products** + **14 bundle products**.

---

## App Store Connect checklist (per product)

- [ ] Type = **Non-Consumable**
- [ ] Product ID = exact string from list / API
- [ ] Reference Name = Arabic name from export (for internal use)
- [ ] Price set (match `price_kwd` as close as App Store tiers allow)
- [ ] Localization (display name + description)
- [ ] Review screenshot uploaded
- [ ] Availability countries selected
- [ ] First IAP submitted with new app binary

---

## Message you can send to Flutter

> الباك جاهز.  
> - كل مادة نشطة عندها `ios_product_id` بصيغة: `com.raiyansoft.shottar.subject.{id}`  
> - كل باقة (صف × فصل) عندها: `com.raiyansoft.shottar.bundle.g{grade}.s{semester}`  
> - اقرأ الـ IDs من الـ API مباشرة، ومتخترعش IDs يدوي.  
> - في App Store Connect: **Non-Consumable** فقط، ونفس الـ Product ID بالحرف.  
> - القائمة الكاملة: شغّل استعلام C في الملف `apple-iap-product-ids-for-flutter.md` وصدّر CSV.  
> - بعد الشراء: `POST /api/order/apple/verify`  
> - أول منتج لازم يتبعت مع نسخة جديدة من التطبيق + screenshot للمراجعة.
