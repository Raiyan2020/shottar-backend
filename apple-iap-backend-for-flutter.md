# Apple IAP — Backend is ready (for Flutter / Cursor)

Base URL: `https://shottarapp.com/api`  
Auth: `Authorization: Bearer <token>` على كل الـ endpoints تحت إلا الـ webhook.

الباك خلّص الجزء المطلوب من [backend-requirements-v3](backend-requirements-v3). التطبيق يقدر يمشي الـ cycle end-to-end أول ما منتجات App Store Connect تتعمل والـ `ios_product_id` يتعبّى من الأدمن.

---

## 1) حقل `ios_product_id`

### على المادة

يرجع في:

- `POST /subjects` → داخل كل عنصر في `data.subjects[]`
- `GET /subjects/{id}` → على كائن المادة
- `POST /subjects/purchased` و Home (لو بتستخدم `SubjectResource`)

```jsonc
{
  "id": 42,
  "name": "الرياضيات",
  "price": "2.500",
  "ios_product_id": "com.raiyansoft.shottar.subject.42"  // أو null
}
```

**قاعدة التطبيق:** `null` أو الحقل مش موجود = المادة **مش قابلة للشراء على iOS** → اعرضها باهتة وغير قابلة للضغط. متنداش StoreKit بـ product id فاضي.

### على الباقة (كل المواد)

نفس كائن `grade` جوّه المادة:

```jsonc
"grade": {
  "id": 3,
  "all_materials_price": "15.000",
  "discount_all_materials": "5",
  "ios_product_id": "com.raiyansoft.shottar.bundle.g3.s1"  // أو null
}
```

الباقة مربوطة **(صف × فصل)** مش الصف لوحده. الباك بيختار الـ id حسب `semester_id` الحالي للطالب / الطلب.

StoreKit query = كل `ios_product_id` غير null للمواد الظاهرة + `grade.ios_product_id` لو موجود.

---

## 2) مسار الشراء على iOS (الـ cycle)

```
1. POST /subjects          → خد ios_product_id
2. StoreKit purchase       → آبل بتخلّص الدفع
3. POST /order/apple/verify
      body: product_id + receipt + transaction_id + source
4. لو success: true        → finish transaction في StoreKit + حدّث المواد المشتراة
5. لو فشل / النت قطع       → سيب المعاملة مفتوحة؛ ابعتها تاني عند فتح التطبيق
```

**متستخدمش** `POST /order` ولا WebView ولا كوبون على iOS.

---

## 3) `POST /order/apple/verify`  🔐 Bearer

### Request

```http
POST /api/order/apple/verify
Authorization: Bearer <token>
Content-Type: application/json
```

```jsonc
{
  "product_id": "com.raiyansoft.shottar.subject.42",
  "receipt": "<JWS transaction أو base64 app receipt>",
  "transaction_id": "2000000912345678",
  "source": "purchase"   // أو "restore"
}
```

| Field | Required | Notes |
|---|---|---|
| `product_id` | نعم | نفس `ios_product_id` اللي رجع من الباك |
| `receipt` | نعم | StoreKit 2: JWS (`signedTransaction`). SK1: `serverVerificationData` |
| `transaction_id` | مستحسن | `transactionId` من آبل |
| `source` | لا | `purchase` أو `restore` — default `purchase` |

**متبعتش** `items` / `subject_ids` / `total`. الباك هو اللي يحوّل `product_id` → المواد.

### Response — نجاح (شراء جديد)

```jsonc
{
  "status": true,
  "data": {
    "success": true,
    "order_id": 9911,
    "subject_ids": [42],
    "already_granted": false
  }
}
```

باقة كاملة → `subject_ids` فيها كل مواد الصف/الفصل.

### Response — نفس العملية اتبعت قبل كده (idempotent)

HTTP **200** — **مش error**:

```jsonc
{
  "status": true,
  "data": {
    "success": true,
    "order_id": 9911,
    "subject_ids": [42],
    "already_granted": true
  }
}
```

اعمل `completePurchase` / finish transaction عادي. متظهرش error للطالب.

### Response — إيصال مرفوض

برضو HTTP **200** (عشان Dio متعملش exception على 4xx لو مش محتاجين):

```jsonc
{
  "status": true,
  "data": {
    "success": false,
    "message": "تعذر التحقق من عملية الشراء"
  }
}
```

هنا **متقولش لـ StoreKit إن العملية خلصت**. سيّبها تتبعت تاني.

### سلوك مطلوب من التطبيق

- متخلّصش الـ StoreKit transaction إلا لو `data.success == true`.
- نفس `transaction_id` ممكن يتبعت كذا مرة (فتح التطبيق / restore / retry) — الباك جاهز.
- بعد النجاح: نادِ `POST /subjects/purchased` (أو refresh البروفايل/الهوم) عشان المحتوى يتفتح.
- `source: "restore"` من شاشة استعادة المشتريات — نفس الـ endpoint.

---

## 4) Webhook (مش شغل فلاتر)

`POST /api/apple/notifications` — آبل بتنده عليه (REFUND / REVOKE).  
التطبيق مش بيحتاج ينادي الـ URL ده.

لو الطالب اتعمل له refund، الطلب بيتحول `cancelled` ومش هيرجع في `/subjects/purchased`.

---

## 5) Checklist لمبرمج الفلاتر / Cursor

- [ ] اقرأ `ios_product_id` من `POST /subjects` ومن `GET /subjects/{id}` ومن `grade`
- [ ] `null` → UI disabled، من غير استدعاء StoreKit
- [ ] StoreKit يشتري بـ `ios_product_id` بالحرف (case-sensitive)
- [ ] بعد الشراء: `POST /order/apple/verify` بالـ receipt + transaction_id
- [ ] finish transaction **فقط** عند `success: true` (سواء `already_granted` true أو false)
- [ ] retry pending transactions عند cold start
- [ ] Restore purchases → نفس الـ verify بـ `"source": "restore"`
- [ ] Android زي ما هو: `POST /order` + WebView — متتكسرش

---

## 6) لسه مش من الباك (عشان الـ cycle تشتغل على جهاز حقيقي)

من غير دول StoreKit هيرجع `product not found` والشاشة هتفضل فاضية:

1. Paid Applications Agreement في App Store Connect
2. منتجات Non-Consumable لكل مادة + كل باقة، **بنفس** الـ `ios_product_id`
3. الأدمن يملأ الحقول في داشبورد شطّار (تعديل المادة / تعديل المرحلة)
4. Sandbox tester للحساب
5. Support URL في App Store Connect → `https://raiyansoft.com/en/contact/` (رفض Guideline 1.5 منفصل)

لو `ios_product_id` لسه `null` من الـ API = الأدمن لسه مدخّلش الـ ID، مش باج فلاتر.
