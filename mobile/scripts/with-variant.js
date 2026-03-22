#!/usr/bin/env node

/**
 * Run Expo commands with a selected app variant.
 *
 * Examples:
 *   node scripts/with-variant.js consumer start
 *   node scripts/with-variant.js delivery run:android
 *   node scripts/with-variant.js partner run:ios
 *
 * Notes:
 * - Sets EXPO_PUBLIC_APP_VARIANT for app.config.js + runtime app-shell.
 * - You can still override any EXPO_PUBLIC_* env var explicitly.
 */

const { spawn } = require('child_process');

function normalizeVariant(value) {
  const normalized = String(value || '').trim().toLowerCase();

  if (normalized === 'customer' || normalized === 'consumer') return 'consumer';
  if (normalized === 'seller' || normalized === 'merchant' || normalized === 'partner') return 'partner';
  if (normalized === 'delivery' || normalized === 'rider' || normalized === 'partner_delivery') return 'delivery';

  return ['consumer', 'delivery', 'partner'].includes(normalized) ? normalized : 'consumer';
}

function main() {
  const [, , rawVariant, ...rest] = process.argv;

  if (!rawVariant || rest.length === 0) {
    console.error('Usage: node scripts/with-variant.js <consumer|delivery|partner> <expo-command> [...args]');
    console.error('Example: node scripts/with-variant.js consumer start');
    process.exit(1);
  }

  const variant = normalizeVariant(rawVariant);
  const expoArgs = ['expo', ...rest];

  const npxCmd = process.platform === 'win32' ? 'npx.cmd' : 'npx';

  const child = spawn(npxCmd, expoArgs, {
    stdio: 'inherit',
    env: {
      ...process.env,
      EXPO_PUBLIC_APP_VARIANT: variant,
    },
  });

  child.on('exit', (code) => process.exit(code ?? 0));
}

main();