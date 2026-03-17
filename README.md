# Grabbasket Starter (MVP)

This starter kit gives you:
- **Backend API**: FastAPI + PostgreSQL + JWT auth (roles: CUSTOMER / SELLER / PARTNER)
- **Flutter mobile code**: app entrypoints + screens + API client, designed for **3 flavors**:
  - `customer`
  - `seller`
  - `partner`

> Note: This is an MVP scaffold you can productionize (payments, maps, realtime, ratings, offers, etc.).
> Do NOT copy Swiggy branding/assets; build original UI/UX and content.

---

## 1) Backend: run locally (Docker)

### Prereqs
- Docker Desktop

### Start
```bash
cd backend
docker compose up --build
```

API will run at:
- http://localhost:8000
- Swagger docs: http://localhost:8000/docs

### Seed demo data
In another terminal:
```bash
docker compose exec api python -m app.seed
```

---

## 2) Flutter mobile: bootstrap a Flutter project and apply patches

### Prereqs
- Flutter SDK (stable)
- Android Studio + Android SDK + a device with USB debugging enabled

### Create a new Flutter project
```bash
flutter create grabbasket
cd grabbasket
```

### Apply the starter mobile code
Copy the `mobile/patches` folder from this starter kit into your `grabbasket/` folder, then run:

```bash
chmod +x patches/apply.sh
bash patches/apply.sh
```

This will:
- add flavors to `android/app/build.gradle`
- create the 3 entrypoints:
  - `lib/main_customer.dart`
  - `lib/main_seller.dart`
  - `lib/main_partner.dart`
- add shared app code under `lib/grabbasket/`

### Run on device
```bash
flutter pub get

# Customer
flutter run --flavor customer -t lib/main_customer.dart

# Seller
flutter run --flavor seller -t lib/main_seller.dart

# Partner
flutter run --flavor partner -t lib/main_partner.dart
```

### Build APK locally (unlimited)
```bash
flutter build apk --debug --flavor customer -t lib/main_customer.dart
# output: build/app/outputs/flutter-apk/app-customer-debug.apk
```

Install to phone via USB:
```bash
adb install -r build/app/outputs/flutter-apk/app-customer-debug.apk
```

### Play Store build (AAB)
```bash
flutter build appbundle --release --flavor customer -t lib/main_customer.dart
# output: build/app/outputs/bundle/customerRelease/app-customer-release.aab
```

---

## 3) Configure the API base URL
For Android emulator, `localhost` is not the host machine. Use:
- `http://10.0.2.2:8000` (Android emulator)
- `http://<YOUR_LAPTOP_LAN_IP>:8000` (real device on same Wi-Fi)

Set it here:
- `lib/grabbasket/config.dart` -> `apiBaseUrl`

---

## 4) Next steps for production
- Payments: Razorpay/Stripe + server-side verification
- Maps & tracking: Google Maps SDK, Geofencing, route ETA
- Realtime: WebSockets / Firebase Cloud Messaging + background updates
- Dispatch: nearest-partner assignment + batching + SLA
- Fraud & abuse: device binding, rate limits, anomaly detection
- Observability: structured logs, metrics, tracing
- Security: TLS, refresh tokens, secret rotation, WAF
- Compliance: Play “Data safety”, Privacy policy, user data deletion
