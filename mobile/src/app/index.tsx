import React, { useEffect } from 'react';
import { Redirect } from 'expo-router';
import * as SplashScreen from 'expo-splash-screen';

import { getInitialShellHref } from '../constants/app-shell';

type ShellHref = '/(tabs)' | '/(delivery)/(tabs)' | '/(partner)/(tabs)';

export default function IndexScreen() {
  useEffect(() => {
    const hideSplash = async () => {
      try {
        await SplashScreen.hideAsync();
      } catch {
        // ignore
      }
    };

    hideSplash();
    const timer = setTimeout(hideSplash, 1000);

    return () => clearTimeout(timer);
  }, []);

  return <Redirect href={getInitialShellHref() as ShellHref} />;
}