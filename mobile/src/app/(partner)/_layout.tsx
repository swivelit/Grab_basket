import React from 'react';
import { Redirect, Stack } from 'expo-router';

import { getAppVariant, getInitialShellHref } from '../../constants/app-shell';

export default function PartnerShellLayout() {
  if (getAppVariant() !== 'partner') {
    return <Redirect href={getInitialShellHref()} />;
  }

  return (
    <Stack screenOptions={{ headerShown: false }}>
      <Stack.Screen name="(tabs)" options={{ headerShown: false }} />
    </Stack>
  );
}