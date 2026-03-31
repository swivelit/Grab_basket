import React from 'react';
import { Redirect } from 'expo-router';

import { getInitialShellHref } from '../constants/app-shell';

type ShellHref = '/(tabs)' | '/(delivery)/(tabs)' | '/(partner)/(tabs)';

export default function IndexScreen() {
  const href = getInitialShellHref() as ShellHref;

  return <Redirect href={href} />;
}