#!/usr/bin/env bash
set -Eeuo pipefail

if ! command -v adb >/dev/null 2>&1; then
  echo "adb is required. Install Android platform-tools and ensure adb is on PATH." >&2
  exit 1
fi

adb logcat | grep -i -E "AndroidRuntime|FATAL|ReactNativeJS|grabbasket|Expo"
