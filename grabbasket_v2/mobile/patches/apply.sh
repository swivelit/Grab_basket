#!/usr/bin/env bash
set -euo pipefail

if [ ! -f "pubspec.yaml" ]; then
  echo "Run this from inside your Flutter project root (where pubspec.yaml exists)."
  exit 1
fi

echo "Applying Grabbasket patches..."
python3 patches/apply.py
echo "Done. Now run: flutter pub get"
