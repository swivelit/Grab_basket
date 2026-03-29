#!/usr/bin/env bash
set -Eeuo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_DIR="$ROOT_DIR"
MOBILE_DIR="$REPO_DIR/mobile"
DIST_DIR="$REPO_DIR/dist"

info() {
  printf "\n▶ %s\n" "$1"
}

warn() {
  printf "\n⚠️  %s\n" "$1"
}

fail() {
  printf "\n❌ %s\n" "$1"
  exit 1
}

is_truthy() {
  case "$(printf '%s' "${1:-}" | tr '[:upper:]' '[:lower:]')" in
    1|true|yes|on|enabled)
      return 0
      ;;
    *)
      return 1
      ;;
  esac
}

presence_label() {
  if [[ -n "${1:-}" ]]; then
    printf 'present'
  else
    printf 'missing'
  fi
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
export BUILD_TYPE

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
CALLER_HAS_NODE_ENV=0
CALLER_HAS_APP_ENV=0
CALLER_HAS_API_BASE_URL=0
CALLER_HAS_PUBLIC_API_BASE_URL=0
CALLER_HAS_FORCE_PRODUCTION_APP_ENV=0

ORIGINAL_NODE_ENV="${NODE_ENV-}"
ORIGINAL_EXPO_PUBLIC_APP_ENV="${EXPO_PUBLIC_APP_ENV-}"
ORIGINAL_API_BASE_URL="${API_BASE_URL-}"
ORIGINAL_EXPO_PUBLIC_API_BASE_URL="${EXPO_PUBLIC_API_BASE_URL-}"
ORIGINAL_FORCE_PRODUCTION_APP_ENV="${FORCE_PRODUCTION_APP_ENV-}"

if [[ ${NODE_ENV+x} ]]; then
  CALLER_HAS_NODE_ENV=1
fi

if [[ ${EXPO_PUBLIC_APP_ENV+x} ]]; then
  CALLER_HAS_APP_ENV=1
fi

if [[ ${API_BASE_URL+x} ]]; then
  CALLER_HAS_API_BASE_URL=1
fi

if [[ ${EXPO_PUBLIC_API_BASE_URL+x} ]]; then
  CALLER_HAS_PUBLIC_API_BASE_URL=1
fi

if [[ ${FORCE_PRODUCTION_APP_ENV+x} ]]; then
  CALLER_HAS_FORCE_PRODUCTION_APP_ENV=1
fi

if [[ -f "$ENV_FILE" ]]; then
  info "Loading environment from: $ENV_FILE"
  set -a
  # shellcheck disable=SC1090
  source "$ENV_FILE"
  set +a
fi

ENV_FILE_APP_ENV="${EXPO_PUBLIC_APP_ENV-}"
if [[ "$CALLER_HAS_NODE_ENV" -eq 1 ]]; then
  export NODE_ENV="$ORIGINAL_NODE_ENV"
fi

if [[ "$CALLER_HAS_APP_ENV" -eq 1 ]]; then
  export EXPO_PUBLIC_APP_ENV="$ORIGINAL_EXPO_PUBLIC_APP_ENV"
fi

if [[ "$CALLER_HAS_API_BASE_URL" -eq 1 ]]; then
  export API_BASE_URL="$ORIGINAL_API_BASE_URL"
fi

if [[ "$CALLER_HAS_PUBLIC_API_BASE_URL" -eq 1 ]]; then
  export EXPO_PUBLIC_API_BASE_URL="$ORIGINAL_EXPO_PUBLIC_API_BASE_URL"
fi

if [[ "$CALLER_HAS_FORCE_PRODUCTION_APP_ENV" -eq 1 ]]; then
  export FORCE_PRODUCTION_APP_ENV="$ORIGINAL_FORCE_PRODUCTION_APP_ENV"
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
  if [[ "$INPUT_API_BASE_URL" != https://* ]]; then
    fail "Release builds require an HTTPS EXPO_PUBLIC_API_BASE_URL. Current value: $INPUT_API_BASE_URL"
  fi

  RELEASE_API_HOST="$(node -e "try { console.log(new URL(process.argv[1]).hostname) } catch { process.exit(1) }" "$INPUT_API_BASE_URL")" \
    || fail "EXPO_PUBLIC_API_BASE_URL is invalid for a release build: $INPUT_API_BASE_URL"

  case "$RELEASE_API_HOST" in
    localhost|127.0.0.1|0.0.0.0|10.0.2.2|10.0.3.2|*.local)
      fail "Release builds cannot use localhost or private network API hosts. Current value: $INPUT_API_BASE_URL"
      ;;
  esac

  if [[ "$RELEASE_API_HOST" =~ ^192\.168\. ]] || [[ "$RELEASE_API_HOST" =~ ^10\. ]] || [[ "$RELEASE_API_HOST" =~ ^172\.(1[6-9]|2[0-9]|3[0-1])\. ]]; then
    fail "Release builds cannot use localhost or private network API hosts. Current value: $INPUT_API_BASE_URL"
  fi
fi

if [[ "$BUILD_TYPE" == "release" ]]; then
  DEFAULT_APP_ENV="production"
  DEFAULT_NODE_ENV="production"
else
  DEFAULT_APP_ENV="development"
  DEFAULT_NODE_ENV="development"
fi

export NODE_ENV="${NODE_ENV:-$DEFAULT_NODE_ENV}"

RESOLVED_APP_ENV="${EXPO_PUBLIC_APP_ENV:-$DEFAULT_APP_ENV}"
RESOLVED_APP_ENV="$(printf '%s' "$RESOLVED_APP_ENV" | tr '[:upper:]' '[:lower:]')"

if [[ "$BUILD_TYPE" == "release" ]]; then
  if [[ "$RESOLVED_APP_ENV" != "production" ]]; then
    warn "Release build selected. Forcing EXPO_PUBLIC_APP_ENV=production so release validation stays enabled."
  fi
  RESOLVED_APP_ENV="production"
else
  if [[ "$RESOLVED_APP_ENV" == "production" ]]; then
    if is_truthy "${FORCE_PRODUCTION_APP_ENV:-0}"; then
      warn "Debug build is keeping EXPO_PUBLIC_APP_ENV=production because FORCE_PRODUCTION_APP_ENV=1 was provided. mobile/app.config.js will run production validation."
    else
      if [[ "$ENV_FILE_APP_ENV" == "production" ]]; then
        warn "Ignoring EXPO_PUBLIC_APP_ENV=production from $ENV_FILE for a debug build. Local debug APKs default to development unless FORCE_PRODUCTION_APP_ENV=1 is set."
      else
        warn "Debug build requested with EXPO_PUBLIC_APP_ENV=production. Overriding to development. Set FORCE_PRODUCTION_APP_ENV=1 if you intentionally want production validation during a debug build."
      fi
      RESOLVED_APP_ENV="development"
    fi
  fi
fi

export EXPO_PUBLIC_APP_ENV="$RESOLVED_APP_ENV"
export EXPO_PUBLIC_API_BASE_URL="$INPUT_API_BASE_URL"

ALLOW_CLEARTEXT_VALUE="${EXPO_PUBLIC_ALLOW_CLEARTEXT-}"
if [[ "$BUILD_TYPE" == "release" ]]; then
  if [[ -z "$ALLOW_CLEARTEXT_VALUE" ]]; then
    ALLOW_CLEARTEXT_VALUE="false"
  fi

  if is_truthy "$ALLOW_CLEARTEXT_VALUE"; then
    fail "EXPO_PUBLIC_ALLOW_CLEARTEXT must be false in production. Unset it or set EXPO_PUBLIC_ALLOW_CLEARTEXT=false for release builds."
  fi

  ALLOW_CLEARTEXT_VALUE="false"
fi

if [[ -n "$ALLOW_CLEARTEXT_VALUE" ]]; then
  export EXPO_PUBLIC_ALLOW_CLEARTEXT="$ALLOW_CLEARTEXT_VALUE"
fi

# Keep runtime crash reporting enabled, but disable source-map upload by default for local APK builds.
export EXPO_PUBLIC_SENTRY_UPLOAD_ENABLED="${EXPO_PUBLIC_SENTRY_UPLOAD_ENABLED:-false}"

APP_VARIANTS_RAW="${APP_VARIANTS:-consumer}"
APP_VARIANTS_RAW="${APP_VARIANTS_RAW// /,}"
IFS=',' read -r -a APP_VARIANTS <<< "$APP_VARIANTS_RAW"
[[ "${#APP_VARIANTS[@]}" -gt 0 ]] || fail "No app variants were provided."

if [[ "$BUILD_TYPE" == "debug" ]]; then
  warn "Debug APKs are for local development only. If you install a debug APK directly on a physical device without the expected dev workflow, it may open the Expo dev launcher or close immediately. Use ./build-apk.sh for the standalone release APK."
fi

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
info "Effective build environment"
echo "  BUILD_TYPE=$BUILD_TYPE"
echo "  NODE_ENV=$NODE_ENV"
echo "  EXPO_PUBLIC_APP_ENV=$EXPO_PUBLIC_APP_ENV"
echo "  EXPO_PUBLIC_API_BASE_URL=$EXPO_PUBLIC_API_BASE_URL"
echo "  EXPO_PUBLIC_ALLOW_CLEARTEXT=${EXPO_PUBLIC_ALLOW_CLEARTEXT:-unset}"
echo "  EXPO_PUBLIC_EAS_PROJECT_ID=$(presence_label "${EXPO_PUBLIC_EAS_PROJECT_ID-}")"
echo "  EXPO_PUBLIC_SENTRY_DSN=$(presence_label "${EXPO_PUBLIC_SENTRY_DSN-}")"
echo "  EXPO_PUBLIC_POSTHOG_API_KEY=$(presence_label "${EXPO_PUBLIC_POSTHOG_API_KEY-}")"
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
echo "  BUILD_TYPE=release ./build-apk.sh"
echo "  APP_VARIANTS=consumer ./build-apk.sh"
echo "  APP_VARIANTS=delivery ./build-apk.sh"
echo "  API_BASE_URL=https://your-api-domain.com ./build-apk.sh"
echo ""
echo "Install on device with:"
echo "  adb install -r \"$DIST_DIR/grab-basket-${BUILD_TYPE}.apk\""
echo "  adb install -r \"$DIST_DIR/grab-basket-delivery-${BUILD_TYPE}.apk\""
echo "  adb install -r \"$DIST_DIR/grab-basket-partner-${BUILD_TYPE}.apk\""
echo ""
echo "Capture startup crash logs with:"
echo "  adb logcat -c"
echo "  adb logcat | grep -E \"AndroidRuntime|ReactNativeJS|ReactNative|Expo|Sentry\""
echo "  (or run: cd mobile && npm run android:logcat)"
