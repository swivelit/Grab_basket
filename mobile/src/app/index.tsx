import React from 'react';
import { Redirect } from 'expo-router';
import { getInitialShellHref } from '../constants/app-shell';

const INITIAL_SHELL = getInitialShellHref() as '/(tabs)' | '/(delivery)/(tabs)' | '/(partner)/(tabs)';

export default function IndexScreen() {
  return <Redirect href={INITIAL_SHELL} />;
}