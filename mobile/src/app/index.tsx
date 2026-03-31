import React, { useEffect } from 'react';
import { Redirect } from 'expo-router';
import * as SplashScreen from 'expo-splash-screen';

import { getInitialShellHref } from '../constants/app-shell';

type ShellHref = '/(tabs)' | '/(delivery)/(tabs)' | '/(partner)/(tabs)';

export default function IndexScreen() {
  useEffect(() => {
    let cancelled = false;

    const hideSplash = async () => {
      try {
        await SplashScreen.hideAsync();
      } catch {
        // Best effort only.
      }
    };

    hideSplash();

    const fallbackTimer = setTimeout(() => {
      if (!cancelled) {
        hideSplash();
      }
    }, 1200);

    return () => {
      cancelled = true;
      clearTimeout(fallbackTimer);
    };
  }, []);

  const href = getInitialShellHref() as ShellHref;

  return <Redirect href={href} />;
}