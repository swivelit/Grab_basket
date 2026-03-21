import React from 'react';
import AppShellPlaceholder from '../../../components/app-shell-placeholder';

export default function PartnerAccountScreen() {
  return (
    <AppShellPlaceholder
      shell="partner"
      eyebrow="Partner settings"
      title="Store profile, payouts, and support"
      description="The partner account area should own store details, operating hours, payout settings, and support tools instead of customer addresses and favourites."
      stats={[
        { value: 'Store', label: 'Profile owner' },
        { value: 'Hours', label: 'Operational control' },
        { value: 'Payouts', label: 'Finance area' },
        { value: 'Support', label: 'Escalation path' },
      ]}
      actions={[
        { icon: 'business-outline', label: 'Store details', hint: 'Update outlet name, phone, and address from this shell.' },
        { icon: 'time-outline', label: 'Operating hours', hint: 'Manage service windows and temporary closures here.' },
        { icon: 'card-outline', label: 'Banking & payouts', hint: 'Settlement and payout setup belong with the seller app.' },
        { icon: 'help-circle-outline', label: 'Partner support', hint: 'Business-side issue reporting stays separate from consumer help flows.' },
      ]}
      sections={[
        { icon: 'cash-outline', title: 'Settlement setup', body: 'GST, bank account and payout schedule settings are seller-only responsibilities and should stay in this shell.' },
        { icon: 'notifications-outline', title: 'Operational alerts', body: 'Low-stock, missed-order and outage notifications can be configured independently here.' },
        { icon: 'shield-checkmark-outline', title: 'Compliance', body: 'Business verification, tax documents and policy acceptance belong in the partner app tree.' },
      ]}
    />
  );
}