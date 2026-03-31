#!/usr/bin/env bash
set -Eeuo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_DIR="$ROOT_DIR"
MOBILE_DIR="$REPO_DIR/mobile"
DIST_DIR="$REPO_DIR/dist"
GENERATED_BUILD_CONFIG_FILE="$MOBILE_DIR/src/generated/app-build-config.js"

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

write_default_generated_build_config() {
  mkdir -p "$(dirname "$GENERATED_BUILD_CONFIG_FILE")"
  cat > "$GENERATED_BUILD_CONFIG_FILE" <<'EOF'
const BUILD_CONFIG = {
  appVariant: 'consumer',
  initialHref: '/(tabs)',
};

export const EMBEDDED_APP_VARIANT = BUILD_CONFIG.appVariant;
export const EMBEDDED_INITIAL_HREF = BUILD_CONFIG.initialHref;

export default BUILD_CONFIG;
EOF
}

write_generated_build_config() {
  local variant="$1"
  local initial_href='/(tabs)'

  case "$variant" in
    consumer)
      initial_href='/(tabs)'
      ;;
    delivery)
      initial_href='/delivery/(tabs)'
      ;;
    partner)
      initial_href='/partner/(tabs)'
      ;;
    *)
      fail "Unsupported embedded build config variant: $variant"
      ;;
  esac

  mkdir -p "$(dirname "$GENERATED_BUILD_CONFIG_FILE")"
  cat > "$GENERATED_BUILD_CONFIG_FILE" <<EOF
const BUILD_CONFIG = {
  appVariant: '${variant}',
  initialHref: '${initial_href}',
};

export const EMBEDDED_APP_VARIANT = BUILD_CONFIG.appVariant;
export const EMBEDDED_INITIAL_HREF = BUILD_CONFIG.initialHref;

export default BUILD_CONFIG;
EOF
}

trap 'write_default_generated_build_config >/dev/null 2>&1 || true' EXIT

if [[ ! -d "$MOBILE_DIR" && -d "$ROOT_DIR/Grab_basket-main/mobile" ]]; then
  REPO_DIR="$ROOT_DIR/Grab_basket-main"
  MOBILE_DIR="$REPO_DIR/mobile"
  DIST_DIR="$REPO_DIR/dist"
  GENERATED_BUILD_CONFIG_FILE="$MOBILE_DIR/src/generated/app-build-config.js"
fi

write_default_generated_build_config

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
[[ -n "$INPUT_API_BASE_URL" ]] || fail "No API base URL configured."

export NODE_ENV="${NODE_ENV:-production}"
export EXPO_PUBLIC_APP_ENV="${EXPO_PUBLIC_APP_ENV:-production}"
export EXPO_PUBLIC_API_BASE_URL="${INPUT_API_BASE_URL%/}"
export EXPO_PUBLIC_SENTRY_UPLOAD_ENABLED="${EXPO_PUBLIC_SENTRY_UPLOAD_ENABLED:-false}"

APP_VARIANTS=("consumer")

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
  if [[ -f "android/app/build/outputs/apk/release/app-release.apk" ]]; then
    printf 'android/app/build/outputs/apk/release/app-release.apk'
    return 0
  fi

  if [[ -f "android/app/build/outputs/apk/release/app-release-unsigned.apk" ]]; then
    printf 'android/app/build/outputs/apk/release/app-release-unsigned.apk'
    return 0
  fi

  return 1
}

reset_js_variant_caches() {
  rm -rf "$MOBILE_DIR/.expo" "$MOBILE_DIR/.expo-shared"
  rm -rf "$MOBILE_DIR/node_modules/.cache/metro" "$MOBILE_DIR/node_modules/.cache/expo"
}

info "Using mobile app at: $MOBILE_DIR"
cd "$MOBILE_DIR"

info "Installing dependencies"
npm install

mkdir -p "$DIST_DIR"
rm -f "$DIST_DIR/grab-basket-release.apk"

for variant in "${APP_VARIANTS[@]}"; do
  set_variant_identity "$variant"
  export EXPO_PUBLIC_APP_VARIANT="$variant"

  info "Embedding startup shell for $variant"
  write_generated_build_config "$variant"

  reset_js_variant_caches

  info "Generating native Android project for $variant"
  CI=1 npx expo prebuild --platform android --clean

  cat > android/local.properties <<PROPS
sdk.dir=${ANDROID_SDK//\\/\\\\}
PROPS

  cd android
  chmod +x gradlew
  info "Running Gradle task for $variant: assembleRelease"
  ./gradlew clean assembleRelease
  cd ..

  APK_SOURCE="$(find_apk_source)" || fail "APK was not found after Gradle build for $variant."
  cp "$APK_SOURCE" "$DIST_DIR/$(variant_output_name "$variant")-release.apk"

  info "$variant APK ready"
  echo "Saved to: $DIST_DIR/$(variant_output_name "$variant")-release.apk"
done

echo ""
echo "All APKs built successfully:"
echo "  $DIST_DIR/grab-basket-release.apk"