import React from 'react';
import { Redirect } from 'expo-router';

import BUILD_CONFIG from '../generated/app-build-config';
import { getInitialShellHref } from '../constants/app-shell';

const VALID_HREFS = ['/(tabs)', '/(delivery)/(tabs)', '/(partner)/(tabs)'] as const;

function normalizeInitialHref(value: unknown): (typeof VALID_HREFS)[number] | '' {
  const normalized = String(value || '').trim();
  return VALID_HREFS.includes(normalized as (typeof VALID_HREFS)[number])
    ? (normalized as (typeof VALID_HREFS)[number])
    : '';
}

export default function IndexScreen() {
  const embeddedHref = normalizeInitialHref(BUILD_CONFIG?.initialHref);
  const href = embeddedHref || (getInitialShellHref() as (typeof VALID_HREFS)[number]);

  return <Redirect href={href} />;
}