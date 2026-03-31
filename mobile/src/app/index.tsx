import React from 'react';
import { Redirect } from 'expo-router';

import { getAppVariant, getInitialShellHref } from '../constants/app-shell';

type ShellHref = '/(tabs)' | '/(delivery)/(tabs)' | '/(partner)/(tabs)';

export default function IndexScreen() {
  const href = getInitialShellHref(getAppVariant()) as ShellHref;

  return <Redirect href={href} />;
}
