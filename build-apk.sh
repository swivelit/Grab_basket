#!/usr/bin/env bash
set -Eeuo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_DIR="$ROOT_DIR"
MOBILE_DIR="$REPO_DIR/mobile"
DIST_DIR="$REPO_DIR/dist"

info() {
  printf "\n▶ %s\n" "$1"
}

fail() {
  printf "\n❌ %s\n" "$1"
  exit 1
}

if [[ ! -d "$MOBILE_DIR" && -d "$ROOT_DIR/Grab_basket-main/mobile" ]]; then
  REPO_DIR="$ROOT_DIR/Grab_basket-main"
  MOBILE_DIR="$REPO_DIR/mobile"
  DIST_DIR="$REPO_DIR/dist"
fi

[[ -d "$MOBILE_DIR" ]] || fail "Could not find the Expo mobile app folder."
[[ -f "$MOBILE_DIR/package.json" ]] || fail "mobile/package.json not found. Create the Expo app first."

BUILD_TYPE="${BUILD_TYPE:-release}"
BUILD_TYPE="$(printf '%s' "$BUILD_TYPE" | tr '[:upper:]' '[:lower:]')"

case "$BUILD_TYPE" in
  debug|release) ;;
  *)
    fail "BUILD_TYPE must be either 'debug' or 'release'."
    ;;
esac

command -v node >/dev/null 2>&1 || fail "Node.js is required."
command -v npm >/dev/null 2>&1 || fail "npm is required."
command -v java >/dev/null 2>&1 || fail "Java is required. JDK 17 is recommended."

JAVA_MAJOR="$(java -version 2>&1 | awk -F '[\".]' '/version/ {print $2; exit}')"
if [[ -n "${JAVA_MAJOR:-}" && "$JAVA_MAJOR" != "17" && "$JAVA_MAJOR" != "21" ]]; then
  echo "⚠️  Detected Java version $JAVA_MAJOR. JDK 17 is the safest choice."
fi

ANDROID_SDK="${ANDROID_SDK_ROOT:-${ANDROID_HOME:-}}"
if [[ -z "$ANDROID_SDK" ]]; then
  for candidate in \
    "$HOME/Library/Android/sdk" \
    "$HOME/Android/Sdk" \
    "/Users/$USER/Library/Android/sdk"
  do
    if [[ -d "$candidate" ]]; then
      ANDROID_SDK="$candidate"
      break
    fi
  done
fi

[[ -n "$ANDROID_SDK" ]] || fail "Android SDK not found. Set ANDROID_SDK_ROOT or ANDROID_HOME."
[[ -d "$ANDROID_SDK/platform-tools" ]] || fail "Android SDK is incomplete. Missing platform-tools."

export ANDROID_SDK_ROOT="$ANDROID_SDK"
export ANDROID_HOME="$ANDROID_SDK"
export PATH="$ANDROID_SDK/platform-tools:$ANDROID_SDK/emulator:$PATH"

ENV_FILE="$MOBILE_DIR/.env"
if [[ -f "$ENV_FILE" ]]; then
  info "Loading environment from: $ENV_FILE"
  set -a
  # shellcheck disable=SC1090
  source "$ENV_FILE"
  set +a
fi

INPUT_API_BASE_URL="${API_BASE_URL:-${EXPO_PUBLIC_API_BASE_URL:-}}"

if [[ -z "$INPUT_API_BASE_URL" ]]; then
  if [[ "$BUILD_TYPE" == "release" ]]; then
    fail "No API base URL configured. Set EXPO_PUBLIC_API_BASE_URL in mobile/.env or pass API_BASE_URL=... when running the script."
  else
    INPUT_API_BASE_URL="http://10.0.2.2:8000"
    echo "⚠️  No API URL configured for debug build. Falling back to $INPUT_API_BASE_URL"
  fi
fi

INPUT_API_BASE_URL="${INPUT_API_BASE_URL%/}"

if [[ "$BUILD_TYPE" == "release" ]]; then
  case "$INPUT_API_BASE_URL" in
    http://10.0.2.2*|http://127.0.0.1*|http://localhost*|https://127.0.0.1*|https://localhost*)
      fail "Release builds cannot use emulator/local URLs. Use your live backend URL, for example: https://grab-basket.onrender.com"
      ;;
  esac
fi

if [[ "$BUILD_TYPE" == "release" ]]; then
  DEFAULT_APP_ENV="production"
else
  DEFAULT_APP_ENV="development"
fi

export EXPO_PUBLIC_APP_ENV="${EXPO_PUBLIC_APP_ENV:-$DEFAULT_APP_ENV}"
export EXPO_PUBLIC_API_BASE_URL="$INPUT_API_BASE_URL"

APP_VARIANTS_RAW="${APP_VARIANTS:-consumer,delivery,partner}"
APP_VARIANTS_RAW="${APP_VARIANTS_RAW// /,}"
IFS=',' read -r -a APP_VARIANTS <<< "$APP_VARIANTS_RAW"
[[ "${#APP_VARIANTS[@]}" -gt 0 ]] || fail "No app variants were provided."

normalize_variant() {
  local value
  value="$(printf '%s' "$1" | tr '[:upper:]' '[:lower:]' | xargs)"

  case "$value" in
    customer|consumer)
      printf 'consumer'
      ;;
    delivery|rider|partner_delivery)
      printf 'delivery'
      ;;
    seller|merchant|partner)
      printf 'partner'
      ;;
    *)
      return 1
      ;;
  esac
}

set_variant_identity() {
  local variant="$1"

  case "$variant" in
    consumer)
      export EXPO_PUBLIC_APP_NAME="Grab Basket"
      export EXPO_PUBLIC_APP_SLUG="grab-basket"
      export EXPO_PUBLIC_APP_SCHEME="grabbasket"
      export EXPO_PUBLIC_IOS_BUNDLE_IDENTIFIER="com.grabbasket.consumer"
      export EXPO_PUBLIC_ANDROID_PACKAGE="com.grabbasket.consumer"
      ;;
    delivery)
      export EXPO_PUBLIC_APP_NAME="Grab Basket Delivery App"
      export EXPO_PUBLIC_APP_SLUG="grab-basket-delivery"
      export EXPO_PUBLIC_APP_SCHEME="grabbasketdelivery"
      export EXPO_PUBLIC_IOS_BUNDLE_IDENTIFIER="com.grabbasket.delivery"
      export EXPO_PUBLIC_ANDROID_PACKAGE="com.grabbasket.delivery"
      ;;
    partner)
      export EXPO_PUBLIC_APP_NAME="Grab Basket Partner App"
      export EXPO_PUBLIC_APP_SLUG="grab-basket-partner"
      export EXPO_PUBLIC_APP_SCHEME="grabbasketpartner"
      export EXPO_PUBLIC_IOS_BUNDLE_IDENTIFIER="com.grabbasket.partner"
      export EXPO_PUBLIC_ANDROID_PACKAGE="com.grabbasket.partner"
      ;;
    *)
      fail "Unsupported app variant: $variant"
      ;;
  esac
}

variant_output_name() {
  case "$1" in
    consumer) printf 'grab-basket' ;;
    delivery) printf 'grab-basket-delivery' ;;
    partner) printf 'grab-basket-partner' ;;
    *) return 1 ;;
  esac
}

find_apk_source() {
  local build_type="$1"

  if [[ "$build_type" == "debug" ]]; then
    if [[ -f "android/app/build/outputs/apk/debug/app-debug.apk" ]]; then
      printf 'android/app/build/outputs/apk/debug/app-debug.apk'
      return 0
    fi
  else
    if [[ -f "android/app/build/outputs/apk/release/app-release.apk" ]]; then
      printf 'android/app/build/outputs/apk/release/app-release.apk'
      return 0
    fi

    if [[ -f "android/app/build/outputs/apk/release/app-release-unsigned.apk" ]]; then
      printf 'android/app/build/outputs/apk/release/app-release-unsigned.apk'
      return 0
    fi
  fi

  return 1
}

info "Using mobile app at: $MOBILE_DIR"
info "Build type: $BUILD_TYPE"
info "App env: $EXPO_PUBLIC_APP_ENV"
info "API base URL: $EXPO_PUBLIC_API_BASE_URL"
info "App variants: ${APP_VARIANTS[*]}"

cd "$MOBILE_DIR"

info "Installing dependencies"
npm install

info "Checking Expo CLI"
npx expo --version >/dev/null

mkdir -p "$DIST_DIR"
BUILT_APKS=()

for raw_variant in "${APP_VARIANTS[@]}"; do
  variant="$(normalize_variant "$raw_variant")" || fail "Unknown app variant: $raw_variant"
  set_variant_identity "$variant"
  export EXPO_PUBLIC_APP_VARIANT="$variant"

  info "Generating native Android project for $variant"
  CI=1 npx expo prebuild --platform android --clean

  cat > android/local.properties <<PROPS
sdk.dir=${ANDROID_SDK//\\/\\\\}
PROPS

  cd android
  chmod +x gradlew

  if [[ "$BUILD_TYPE" == "debug" ]]; then
    GRADLE_TASK="assembleDebug"
  else
    GRADLE_TASK="assembleRelease"
  fi

  info "Running Gradle task for $variant: $GRADLE_TASK"
  ./gradlew "$GRADLE_TASK"
  cd ..

  APK_SOURCE="$(find_apk_source "$BUILD_TYPE")" || fail "APK was not found after Gradle build for $variant."

  APK_NAME="$(variant_output_name "$variant")-${BUILD_TYPE}.apk"
  cp "$APK_SOURCE" "$DIST_DIR/$APK_NAME"
  BUILT_APKS+=("$DIST_DIR/$APK_NAME")

  info "$variant APK ready"
  echo "Saved to: $DIST_DIR/$APK_NAME"
done

echo ""
echo "All APKs built successfully:"
for apk_path in "${BUILT_APKS[@]}"; do
  echo "  $apk_path"
done

echo ""
echo "Examples:"
echo "  ./build-apk.sh"
echo "  BUILD_TYPE=debug ./build-apk.sh"
echo "  APP_VARIANTS=consumer ./build-apk.sh"
echo "  API_BASE_URL=https://grab-basket.onrender.com ./build-apk.sh"
echo ""
echo "Install on device with:"
echo "  adb install -r \"$DIST_DIR/grab-basket-${BUILD_TYPE}.apk\""
echo "  adb install -r \"$DIST_DIR/grab-basket-delivery-${BUILD_TYPE}.apk\""
echo "  adb install -r \"$DIST_DIR/grab-basket-partner-${BUILD_TYPE}.apk\""