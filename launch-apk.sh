#!/bin/bash

set -e

echo "🚀 Starting Grab Basket Android Dev Environment..."

PROJECT_ROOT="$(cd "$(dirname "$0")" && pwd)"
MOBILE_DIR="$PROJECT_ROOT/mobile"

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

# ---- 3. Start Metro in NEW TERMINAL ----
echo "📦 Starting Metro in new terminal..."

osascript <<EOF
tell application "Terminal"
    do script "cd \"$MOBILE_DIR\" && npx expo start --dev-client -c"
    activate
end tell
EOF

# Give Metro time to start
sleep 5

# ---- 4. Install & launch app ----
echo "📲 Installing app on emulator..."

cd "$MOBILE_DIR"
npx expo run:android

echo "✅ App launched successfully!"