import React from 'react';
import AppShellPlaceholder from '../../../components/app-shell-placeholder';

export default function DeliveryEarningsScreen() {
  return (
    <AppShellPlaceholder
      shell="delivery"
      eyebrow="Rider earnings"
      title="Trips, payouts, and incentives"
      description="This screen is reserved for delivery-side earnings, wallet balance, incentive ladders, and payout history without any customer shopping UI mixed in."
      stats={[
        { value: 'Daily', label: 'Summary cadence' },
        { value: 'Wallet', label: 'Payout model' },
        { value: 'Boosts', label: 'Incentive surface' },
        { value: 'Trips', label: 'Underlying unit' },
      ]}
      actions={[
        { icon: 'cash-outline', label: "Today's payout", hint: 'Show completed trip totals, cash handling and net credits.' },
        { icon: 'trophy-outline', label: 'Incentives', hint: 'Quest-based rewards and peak-hour boosts belong here.' },
        { icon: 'calendar-outline', label: 'Weekly summary', hint: 'Break down trips, ratings and average efficiency over time.' },
        { icon: 'document-text-outline', label: 'Statement export', hint: 'Payout statements can become a delivery-only feature.' },
      ]}
      sections={[
        { icon: 'analytics-outline', title: 'Performance-linked pay', body: 'Acceptance rate, on-time delivery, cancellations and incentive unlocking should be modelled in this route tree.' },
        { icon: 'card-outline', title: 'Settlement tools', body: 'Bank account links, payout scheduling and wallet withdrawals fit the delivery shell instead of account screens from the consumer app.' },
        { icon: 'information-circle-outline', title: 'Transparency', body: 'Trip-by-trip earning details and support tickets for payout disputes should stay local to the rider experience.' },
      ]}
    />
  );
}