# CASH Payment — Flutter integration guide

Base URL: `https://shottarapp.com/api`  
Auth: `Authorization: Bearer <token>` on protected endpoints.

This document describes how to integrate the new **CASH** (offline) payment method in the Flutter app. The backend is already implemented.

---

## 1) Payment methods overview

The app supports **three payment categories**:

| Category | Slug(s) | Flutter action |
|----------|---------|----------------|
| **MyFatoorah (online)** | `knet`, `visa`, `apple-pay` | `POST /order` → open `payment_url` in WebView/browser |
| **CASH (offline)** | `cash` | `POST /order` → **no WebView** → show confirmation screen |
| **Apple IAP (iOS only)** | `apple_iap` | **Do not** use `POST /order` — use StoreKit + `POST /order/apple/verify` |

---

## 2) List payment methods

```http
GET /api/payment-method
Authorization: Bearer <token>
lang: ar
```

### Response (example)

```jsonc
{
  "status": true,
  "data": [
    {
      "id": 5,
      "name": "نقدي",
      "slug": "cash",
      "is_offline": true,
      "image": null
    },
    {
      "id": 3,
      "name": "كي نت",
      "slug": "knet",
      "is_offline": false,
      "image": "https://shottarapp.com/storage/..."
    }
  ]
}
```

### New fields (important)

| Field | Type | Meaning |
|-------|------|---------|
| `slug` | string | Stable identifier — use this for branching logic |
| `is_offline` | bool | `true` = CASH flow (no gateway URL) |

### UI rules

- Show all methods where `status` is active (API already filters active only).
- For checkout branching, prefer **`is_offline`** or **`slug == 'cash'`**.
- **Hide or disable** `apple_iap` in MyFatoorah checkout — it must not be sent to `POST /order`.

---

## 3) Create order — shared request

```http
POST /api/order
Authorization: Bearer <token>
Content-Type: application/json
lang: ar
```

```jsonc
{
  "payment_method_id": 5,
  "total": 10.0,
  "is_all_materials": false,
  "items": [62]
}
```

| Field | Required | Notes |
|-------|----------|-------|
| `payment_method_id` | Yes (when total > 0) | ID from `GET /payment-method` |
| `total` | Yes | Must match backend calculation |
| `is_all_materials` | Yes | `true` for full grade bundle |
| `items` | Yes | Array of `subject_id` |
| `coupon_code` | Optional | If supported in UI |

---

## 4) CASH flow (offline)

### When user selects CASH

1. Call `POST /order` with `payment_method_id` of the CASH method.
2. **Do not** open WebView.
3. **Do not** expect `payment_url`.
4. Show success / pending screen with backend message.
5. Subjects stay **locked** until admin confirms payment (`paid` on server).

### Success response

```jsonc
{
  "status": true,
  "message": "تم إنشاء الطلب. يرجى إتمام الدفع نقدًا لتفعيل المواد.",
  "data": {
    "order_id": 123,
    "total": 10.0,
    "payment_url": null,
    "payment_status": "pending",
    "payment_method": "cash"
  }
}
```

### Flutter pseudocode

```dart
Future<void> checkout(SelectedPaymentMethod method, List<int> subjectIds, double total) async {
  if (method.slug == 'apple_iap') {
    await purchaseWithStoreKit(...);
    return;
  }

  final response = await api.postOrder(
    paymentMethodId: method.id,
    total: total,
    items: subjectIds,
    isAllMaterials: false,
  );

  if (method.isOffline || method.slug == 'cash') {
    // CASH — offline
    navigateToCashPendingScreen(
      orderId: response.data.orderId,
      total: response.data.total,
      message: response.message,
    );
    return;
  }

  // MyFatoorah — online
  final url = response.data.paymentUrl;
  if (url == null || url.isEmpty) {
    showError('Payment URL missing');
    return;
  }
  await openPaymentWebView(url);
}
```

### CASH UI copy (suggested)

**Arabic:**
> تم تسجيل طلبك بنجاح.  
> رقم الطلب: #{order_id}  
> يرجى إتمام الدفع نقدًا لتفعيل المواد.  
> سيتم تفعيل اشتراكك بعد تأكيد الدفع من الإدارة.

**English:**
> Your order has been placed.  
> Order #{order_id}  
> Please complete cash payment to activate your subjects.  
> Access will be enabled after admin confirmation.

---

## 5) MyFatoorah flow (unchanged)

When `is_offline == false` and slug is `knet`, `visa`, or `apple-pay`:

```jsonc
{
  "status": true,
  "message": "تم إنشاء الطلب ورابط الدفع بنجاح.",
  "data": {
    "success": true,
    "payment_url": "https://portal.myfatoorah.com/...",
    "order_id": 123,
    "total": 10.0
  }
}
```

- Open `payment_url` in WebView (same as before).
- After payment, MyFatoorah redirects to backend callbacks; user returns to app.
- Refresh purchased subjects: `POST /api/subjects/purchased` or subject detail `is_purchased`.

---

## 6) Apple IAP (unchanged — iOS)

- **Never** send `apple_iap` to `POST /order` — backend returns error.
- Use StoreKit + `POST /api/order/apple/verify`.
- See `apple-iap-backend-for-flutter.md` for full cycle.

---

## 7) Order / payment statuses

| Status | Meaning | User access to subjects |
|--------|---------|-------------------------|
| `pending` | Order created, not paid yet | **No** (CASH stays here until admin confirms) |
| `paid` | Payment confirmed | **Yes** |
| `failed` | Online payment failed | No |
| `cancelled` | Cancelled | No |

After CASH order: user should **not** see subjects as purchased until status becomes `paid` on backend.

---

## 8) Error cases to handle

| Case | HTTP | Message / action |
|------|------|------------------|
| Invalid `payment_method_id` | 400 | Show error, do not proceed |
| `apple_iap` sent to `POST /order` | 400 | Route to StoreKit flow instead |
| MyFatoorah failure | 400 | Show error, order is rolled back on server |
| Missing `payment_url` for online method | — | Show error (should not happen for knet/visa) |

---

## 9) Model suggestions (Dart)

```dart
class PaymentMethodModel {
  final int id;
  final String name;
  final String slug;
  final bool isOffline;
  final String? image;

  bool get isCash => slug == 'cash';
  bool get isAppleIap => slug == 'apple_iap';
  bool get isOnlineGateway => !isOffline && !isAppleIap;
}

class CreateOrderResponse {
  final int orderId;
  final double total;
  final String? paymentUrl;
  final String? paymentStatus;  // "pending" for CASH
  final String? paymentMethod;  // "cash"
}
```

Parse from `response.data` — field names are **snake_case** in JSON (`order_id`, `payment_url`, etc.).

---

## 10) Checklist for Cursor / Flutter dev

- [ ] Parse `slug` and `is_offline` from `GET /payment-method`
- [ ] Branch checkout: `cash` vs online vs `apple_iap`
- [ ] CASH: `POST /order` → show pending screen, **no WebView**
- [ ] Online: `POST /order` → open `payment_url` (existing behavior)
- [ ] iOS IAP: StoreKit only, not `POST /order`
- [ ] Do not mark subjects as purchased locally after CASH until API returns `is_purchased: true`
- [ ] Show `order_id` on CASH confirmation screen
- [ ] Handle Arabic/English using `lang` header and backend `message`
- [ ] Test: CASH order → `payment_url == null`, `payment_status == pending`
- [ ] Test: knet/visa still return valid `payment_url`
- [ ] Test: invalid `payment_method_id` shows error

---

## 11) QA test script

1. `GET /payment-method` — confirm CASH exists with `is_offline: true`
2. Select subject(s), choose **CASH**, submit order
3. Expect `payment_url: null`, `payment_status: pending`, `payment_method: cash`
4. Confirm subject detail still shows `is_purchased: false`
5. Repeat with **Knet** — expect valid `payment_url` and WebView opens
6. (iOS) Confirm Apple IAP still uses verify endpoint, not order

---

## 12) Notes

- CASH orders require **manual admin confirmation** on backend before subjects unlock.
- Do not implement client-side “fake paid” state for CASH.
- Backend slug for CASH is exactly: **`cash`** (lowercase).

Questions → backend team.
