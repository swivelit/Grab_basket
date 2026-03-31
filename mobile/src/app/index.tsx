import React, { useEffect } from 'react';
import { Redirect } from 'expo-router';
import * as SplashScreen from 'expo-splash-screen';

export default function IndexScreen() {
  useEffect(() => {
    const hide = async () => {
      try {
        await SplashScreen.hideAsync();
      } catch {
        // ignore
      }
    };

    hide();
    const timer = setTimeout(hide, 1000);

    return () => {
      clearTimeout(timer);
    };
  }, []);

  return <Redirect href="/(tabs)" />;
}