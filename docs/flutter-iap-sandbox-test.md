# Apple IAP — دليل التيست لمطور Flutter / iOS

Base URL: `https://shottarapp.com/api`  
Bundle ID: `com.raiyansoft.shottar`  
Auth: `Authorization: Bearer <token>` على كل الـ endpoints تحت (ما عدا login / register / activate).

الباكند جاهز على الإنتاج. التطبيق **ميخترعش** Product IDs. يقرأها من الـ API ويشتري بنفس القيمة حرفًا.

---

## 0) تأكيد من السيرفر (جاهز — متحتاجش SQL)

اتراجع على الإنتاج:

- كل مادة نشطة عندها `ios_product_id` بصيغة `com.raiyansoft.shottar.subject.{id}`
- الباقات موجودة بصيغة `com.raiyansoft.shottar.bundle.g{grade_id}.s{semester_id}` (مثال: `com.raiyansoft.shottar.bundle.g10.s1`)
- مفيش مادة نشطة من غير ID

**الاشتراك على المواد مش على الصف.**  
`user.grade_id` = صف الطالب في البروفايل فقط، مش اشتراك.

| اشترى | النتيجة |
|---|---|
| مادة | تفتح المادة دي بس — `subject_ids: [10]` |
| باقة `grade.ios_product_id` | تفتح **كل مواد الصف × الفصل وقت الشراء** — ترجع في `subject_ids` |

بعد الشراء المحتوى المفتوح = `POST /api/subjects/purchased` أو `is_purchased: true` على تفاصيل المادة.

مادة تضاف بعد شراء الباقة **مش هتتفتح لوحدها** على الطلب القديم.

---

## 1) صيغة الـ Product IDs

النوع في App Store Connect: **Non-Consumable** فقط (مش Consumable ومش Subscription).

| النوع | الصيغة | مثال من الإنتاج |
|---|---|---|
| مادة واحدة | `com.raiyansoft.shottar.subject.{subject_id}` | `com.raiyansoft.shottar.subject.10` |
| باقة كل المواد (صف × فصل) | `com.raiyansoft.shottar.bundle.g{grade_id}.s{semester_id}` | `com.raiyansoft.shottar.bundle.g10.s1` |

القواعد:

- Case-sensitive. `Subject` ≠ `subject`.
- لو `ios_product_id` رجع `null` أو فاضي → المادة **مش للبيع على iOS**. UI disabled، ومتنداش StoreKit.
- متكتبش IDs في الكود. المصدر الوحيد: الـ API.

---

## 2) منين تجيب الـ IDs (API)

### Login ثم OTP

```http
POST /api/login
Content-Type: application/json
lang: ar
```

```json
{
  "country_code": "+965",
  "phone": "XXXXXXXX",
  "device_type": "ios"
}
```

بعد كود الواتساب:

```http
POST /api/activateAccount
```

```json
{
  "country_code": "+965",
  "phone": "XXXXXXXX",
  "activation_code": "1234"
}
```

الرد فيه `token` — استخدمه Bearer.

> `1234` مثال فقط. الكود عشوائي بيتبعت واتساب. متثبتش OTP في التطبيق.

### قائمة المواد (هنا الـ IDs)

```http
POST /api/subjects
Authorization: Bearer <token>
Content-Type: application/json
lang: ar
```

لو حساب الطالب فيه `grade_id` و `semester_id` مش محتاج تبعتهم. غير كده:

```json
{
  "grade_id": 10,
  "semester_id": 1
}
```

الرد:

```jsonc
{
  "status": true,
  "data": {
    "total_price": "15.000",
    "subjects": [
      {
        "id": 10,
        "name": "اللغة العربية",
        "price": "20.60",
        "ios_product_id": "com.raiyansoft.shottar.subject.10",
        "grade": {
          "id": 10,
          "all_materials_price": "30.00",
          "ios_product_id": "com.raiyansoft.shottar.bundle.g10.s1"
        }
      }
    ]
  }
}
```

StoreKit query = كل `subjects[].ios_product_id` غير null **+** `grade.ios_product_id` لو موجود.

تفاصيل مادة:

```http
GET /api/subjects/{id}
Authorization: Bearer <token>
```

فيها كمان `ios_product_id` و `is_purchased`.

---

## 3) دورة الشراء على iOS

**على iOS متستخدمش** `POST /api/order` ولا WebView ولا كوبون. دول أندرويد / ماي فاتورة.

```
1. POST /api/subjects          → خد ios_product_id
2. StoreKit purchase           → آبل بتخلّص الدفع (Sandbox)
3. POST /api/order/apple/verify
4. لو data.success == true     → finish transaction + حدّث المواد المشتراة
5. لو فشل / النت قطع           → سيب المعاملة مفتوحة؛ ابعتها تاني عند فتح التطبيق
```

### Verify

```http
POST /api/order/apple/verify
Authorization: Bearer <token>
Content-Type: application/json
```

```json
{
  "product_id": "com.raiyansoft.shottar.subject.10",
  "receipt": "<StoreKit 2: signedTransaction JWS | StoreKit 1: serverVerificationData>",
  "transaction_id": "2000000912345678",
  "source": "purchase"
}
```

| Field | Required | Notes |
|---|---|---|
| `product_id` | نعم | نفس `ios_product_id` من الـ API بالحرف |
| `receipt` | نعم | StoreKit 2: JWS. SK1: base64 app receipt |
| `transaction_id` | مستحسن جدًا | `transactionId` من آبل |
| `source` | لا | `purchase` أو `restore` — default `purchase` |

**متبعتش** `items` / `subject_ids` / `total`. الباك هو اللي يحوّل `product_id` → المواد.

### نجاح (شراء جديد)

HTTP 200

```json
{
  "status": true,
  "data": {
    "success": true,
    "order_id": 9911,
    "subject_ids": [10],
    "already_granted": false
  }
}
```

باقة كاملة → `subject_ids` فيها كل مواد الصف/الفصل.

### نفس العملية اتبعت قبل كده (idempotent)

HTTP 200 — **مش error**:

```json
{
  "status": true,
  "data": {
    "success": true,
    "order_id": 9911,
    "subject_ids": [10],
    "already_granted": true
  }
}
```

اعمل `completePurchase` / finish عادي. متظهرش error للطالب.

### إيصال مرفوض

HTTP 200 برضو:

```json
{
  "status": true,
  "data": {
    "success": false,
    "message": "تعذر التحقق من عملية الشراء"
  }
}
```

هنا **متقولش لـ StoreKit إن العملية خلصت**. سيّبها تتبعت تاني.

بعد النجاح:

```http
POST /api/subjects/purchased
Authorization: Bearer <token>
```

أو `GET /api/subjects/{id}` وتأكد `is_purchased: true`.

Restore من شاشة استعادة المشتريات = نفس الـ verify بـ `"source": "restore"`.

---

## 4) تيست Sandbox على جهاز حقيقي

الباك **بيحوّل sandbox لوحده**. مفيش URL تيست تاني، ومفيش فلاج في التطبيق. لو آبل رجّعت إيصال sandbox السيرفر يتحقق من `sandbox.itunes.apple.com`.

### تجهيز الحساب (مرة واحدة — App Store Connect)

1. Users and Access → Sandbox → **Add Tester**.
2. Apple ID تجريبي (إيميل مش مستخدم على آبل قبل كده).
3. دولة الحساب: نفس دولة الأسعار (الكويت).
4. المنتجات في App Store Connect تكون **Non-Consumable** و **Ready to Submit / Approved** و Product ID مطابق للـ API.

### على الآيفون

1. Settings → App Store → **Sign Out** من Apple ID الحقيقي.
2. متدخلش حساب الـ Sandbox من Settings يدوي (iOS هيطلبه وقت الشراء).
3. ثبّت التطبيق من **Xcode** أو **TestFlight** (مش من App Store الإنتاج).
4. Bundle ID لازم يكون `com.raiyansoft.shottar`.
5. اعمل Login بحساب طالب في شطّار (رقم عليه واتساب عشان OTP).
6. افتح مادة `ios_product_id != null` واضغط شراء.
7. لما آبل تطلب Apple ID → حساب الـ **Sandbox tester**.
8. الدفع sandbox: **مجاني / [Environment: Sandbox]** — مفيش فلوس حقيقية.
9. التطبيق يبعت `/order/apple/verify` فورًا.
10. لو `success: true` → finish transaction → المحتوى يتفتح.

### StoreKit Configuration file (اختياري على Simulator)

ينفع للـ UI فقط. **التيست المعتمد مع الباك** لازم جهاز حقيقي + Sandbox + منتجات App Store Connect. الـ local StoreKit file مش بيعدّي على سيرفر آبل، والباك هيرفض الإيصال.

---

## 5) Checklist قبل ما تقول «IAP مش شغال»

| العرض | السبب الغالب | مين يصلحه |
|---|---|---|
| StoreKit: product not found | المنتج مش موجود في App Store Connect أو الـ ID مختلف حرف | iOS / App Store Connect |
| `ios_product_id: null` من الـ API | الأدمن مدخلش الـ ID في الداشبورد | Backend / Admin |
| شراء تم وآبل نجحت والمحتوى مقفول | التطبيق منتهيش `verify` أو بيستخدم `POST /order` | Flutter |
| `success: false` من verify | receipt / product_id / bundle_id مش مطابق | Flutter يبعت الـ request body للباكند |
| Prompts بـ Apple ID حقيقي | لسه مسجّل في Settings → App Store | iOS tester |
| Simulator فشل verify | إيصال local مش من آبل | استخدم جهاز حقيقي |

---

## 6) سلوك مطلوب من التطبيق

- [ ] اقرأ `ios_product_id` من `POST /subjects` و `GET /subjects/{id}` و `grade.ios_product_id`
- [ ] `null` → UI disabled، من غير StoreKit
- [ ] StoreKit query بكل الـ IDs غير الفاضية مرة واحدة
- [ ] بعد الشراء: `POST /order/apple/verify` بالـ receipt + transaction_id
- [ ] finish transaction **فقط** عند `data.success == true` (سواء `already_granted` true أو false)
- [ ] retry pending transactions عند cold start
- [ ] Restore → نفس الـ endpoint بـ `"source": "restore"`
- [ ] Android زي ما هو: `POST /order` + WebView — متتكسرش
- [ ] متستخدمش كوبون على مسار iOS

`POST /api/apple/notifications` مش شغل فلاتر. آبل بتنده عليه عند Refund.
