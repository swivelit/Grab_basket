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

info "Using mobile app at: $MOBILE_DIR"
info "Build type: $BUILD_TYPE"
info "App env: $EXPO_PUBLIC_APP_ENV"
info "API base URL: $EXPO_PUBLIC_API_BASE_URL"

cd "$MOBILE_DIR"

info "Installing dependencies"
if [[ -f package-lock.json ]]; then
  npm ci
else
  npm install
fi

info "Checking Expo CLI"
npx expo --version >/dev/null

info "Generating native Android project"
CI=1 npx expo prebuild --platform android --clean

cat > android/local.properties <<EOF
sdk.dir=${ANDROID_SDK//\\/\\\\}
EOF

cd android
chmod +x gradlew

if [[ "$BUILD_TYPE" == "debug" ]]; then
  GRADLE_TASK="assembleDebug"
else
  GRADLE_TASK="assembleRelease"
fi

info "Running Gradle task: $GRADLE_TASK"
./gradlew "$GRADLE_TASK"
cd ..

APK_SOURCE=""
if [[ "$BUILD_TYPE" == "debug" ]]; then
  if [[ -f "android/app/build/outputs/apk/debug/app-debug.apk" ]]; then
    APK_SOURCE="android/app/build/outputs/apk/debug/app-debug.apk"
  fi
else
  if [[ -f "android/app/build/outputs/apk/release/app-release.apk" ]]; then
    APK_SOURCE="android/app/build/outputs/apk/release/app-release.apk"
  elif [[ -f "android/app/build/outputs/apk/release/app-release-unsigned.apk" ]]; then
    APK_SOURCE="android/app/build/outputs/apk/release/app-release-unsigned.apk"
  fi
fi

[[ -n "$APK_SOURCE" ]] || fail "APK was not found after Gradle build."

mkdir -p "$DIST_DIR"
APK_NAME="grab-basket-${BUILD_TYPE}.apk"
cp "$APK_SOURCE" "$DIST_DIR/$APK_NAME"

info "APK ready"
echo "Saved to: $DIST_DIR/$APK_NAME"
echo ""
echo "Examples:"
echo "  ./build-apk.sh"
echo "  BUILD_TYPE=debug ./build-apk.sh"
echo "  API_BASE_URL=https://grab-basket.onrender.com ./build-apk.sh"
echo ""
echo "Install on device with:"
echo "  adb install -r \"$DIST_DIR/$APK_NAME\""