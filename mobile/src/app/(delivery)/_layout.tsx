import React from 'react';
import { Redirect, Stack } from 'expo-router';

import { getAppVariant, getInitialShellHref } from '../../constants/app-shell';

export default function DeliveryShellLayout() {
  if (getAppVariant() !== 'delivery') {
    return <Redirect href={getInitialShellHref()} />;
  }

  return (
    <Stack screenOptions={{ headerShown: false }}>
      <Stack.Screen name="(tabs)" options={{ headerShown: false }} />
    </Stack>
  );
}