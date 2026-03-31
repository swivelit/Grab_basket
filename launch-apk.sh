#!/bin/bash

set -e

echo "🚀 Starting Grab Basket (Clean Dev Launch)..."

PROJECT_ROOT="$(cd "$(dirname "$0")" && pwd)"
MOBILE_DIR="$PROJECT_ROOT/mobile"

# ---- 0. CLEAN BUILD ----
echo "🧹 Cleaning previous build artifacts..."

rm -rf "$MOBILE_DIR/android"
rm -rf "$MOBILE_DIR/.expo" "$MOBILE_DIR/.expo-shared"
rm -rf "$MOBILE_DIR/node_modules/.cache"
rm -rf "$PROJECT_ROOT/dist"

echo "✅ Clean complete"

# ---- 1. Start emulator if not running ----
echo "📱 Checking emulator..."

if ! adb devices | grep -q "emulator-"; then
  echo "⚠️ No emulator running. Starting one..."

  EMULATOR_NAME=$(emulator -list-avds | head -n 1)

  if [ -z "$EMULATOR_NAME" ]; then
    echo "❌ No AVD found. Create one in Android Studio."
    exit 1
  fi

  echo "▶️ Launching emulator: $EMULATOR_NAME"
  emulator -avd "$EMULATOR_NAME" &

  echo "⏳ Waiting for emulator to boot..."
  adb wait-for-device

  until adb shell getprop sys.boot_completed | grep -m 1 "1" > /dev/null; do
    sleep 2
  done

  echo "✅ Emulator ready"
else
  echo "✅ Emulator already running"
fi

# ---- 2. Setup port forwarding ----
echo "🔁 Setting up port forwarding..."
adb reverse tcp:8081 tcp:8081 || true

# ---- 3. Install dependencies (safe after clean) ----
echo "📦 Installing dependencies..."
cd "$MOBILE_DIR"
npm install

# ---- 4. Start Metro in NEW TERMINAL ----
echo "📦 Starting Metro in new terminal..."

osascript <<EOF
tell application "Terminal"
    do script "cd \"$MOBILE_DIR\" && npx expo start --dev-client -c"
    activate
end tell
EOF

# Give Metro time to spin up
sleep 6

# ---- 5. Build & launch app ----
echo "📲 Building & installing app..."

npx expo run:android

echo "✅ App launched successfully!"