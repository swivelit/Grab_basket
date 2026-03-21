import React from 'react';
import AppShellTabs from '../../../components/app-shell-tabs';

const DELIVERY_TABS = [
  { name: 'index', label: 'Home', icon: 'speedometer-outline' },
  { name: 'orders', label: 'Orders', icon: 'map-outline' },
  { name: 'earnings', label: 'Earnings', icon: 'wallet-outline' },
  { name: 'account', label: 'Account', icon: 'person-outline' },
];

export default function DeliveryTabsLayout() {
  return <AppShellTabs shell="delivery" screens={DELIVERY_TABS} />;
}