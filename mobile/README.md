# GrabBasket Mobile

Expo/React Native app for the GrabBasket consumer, partner, and delivery shells.

## Local commands

Install dependencies:

```bash
npm install
```

Start the app:

```bash
npm run start
```

Release-candidate verification:

```bash
npm run lint
```

The repository root also provides:

```bash
make mobile-verify
```

APK build shortcuts from the repo root:

```bash
./build-apk.sh
EXPO_PUBLIC_APP_ENV=development ./build-apk.sh
BUILD_TYPE=release ./build-apk.sh
```

Release/prod config must include `EXPO_PUBLIC_ALLOW_CLEARTEXT=false` and `EXPO_PUBLIC_EAS_PROJECT_ID=...`.
