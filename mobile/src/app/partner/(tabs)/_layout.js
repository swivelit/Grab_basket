import React from 'react';
import AppShellTabs from '../../../components/app-shell-tabs';

const PARTNER_TABS = [
  { name: 'index', label: 'Dashboard', icon: 'storefront-outline' },
  { name: 'orders', label: 'Orders', icon: 'receipt-outline' },
  { name: 'catalog', label: 'Catalog', icon: 'grid-outline' },
  { name: 'account', label: 'Account', icon: 'person-outline' },
];

export default function PartnerTabsLayout() {
  return <AppShellTabs shell="partner" screens={PARTNER_TABS} />;
}