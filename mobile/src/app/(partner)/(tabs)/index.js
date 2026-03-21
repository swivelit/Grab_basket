import React from 'react';
import AppShellPlaceholder from '../../../components/app-shell-placeholder';

export default function PartnerDashboardScreen() {
  return (
    <AppShellPlaceholder
      shell="partner"
      eyebrow="Grab Basket Partner"
      title="Seller operations dashboard"
      description="This is the dedicated seller shell. Orders, catalog, prep workflow, and store settings can now evolve separately from the customer app UI."
      stats={[
        { value: 'Store', label: 'Primary entity' },
        { value: '4', label: 'Partner tabs' },
        { value: '0', label: 'Customer tabs reused' },
        { value: 'Seller', label: 'Target role' },
      ]}
      actions={[
        { icon: 'power-outline', label: 'Store status', hint: 'Open or pause the outlet without affecting consumer navigation.' },
        { icon: 'timer-outline', label: 'Prep time', hint: 'Kitchen speed and SLA controls belong in this shell.' },
        { icon: 'pricetags-outline', label: 'Offer control', hint: 'Outlet promos and menu pricing can be managed here.' },
        { icon: 'call-outline', label: 'Ops support', hint: 'Seller support and dispatch coordination stay outside the consumer app.' },
      ]}
      sections={[
        { icon: 'receipt-outline', title: 'Order intake', body: 'Incoming order queue, accept or reject decisions and prep timeline controls belong inside the seller app shell.' },
        { icon: 'bag-check-outline', title: 'Preparation flow', body: 'Ready-for-pickup transitions, packaging status and dispatch handoff should not share customer tabs.' },
        { icon: 'stats-chart-outline', title: 'Business health', body: 'Revenue, conversion, stock-outs and top-selling items can grow here as partner-only surfaces.' },
      ]}
    />
  );
}