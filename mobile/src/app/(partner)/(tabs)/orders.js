import React from 'react';
import AppShellPlaceholder from '../../../components/app-shell-placeholder';

export default function PartnerOrdersScreen() {
  return (
    <AppShellPlaceholder
      shell="partner"
      eyebrow="Seller orders"
      title="Accept, prepare, and dispatch"
      description="Use this route for the partner-side order queue, preparation stages, pickup readiness, and rider handoff workflows."
      stats={[
        { value: 'Accept', label: 'First action' },
        { value: 'Prep', label: 'Core phase' },
        { value: 'Ready', label: 'Pickup state' },
        { value: 'Reject', label: 'Fallback action' },
      ]}
      actions={[
        { icon: 'checkmark-circle-outline', label: 'Accept order', hint: 'New order review belongs here.' },
        { icon: 'close-circle-outline', label: 'Reject order', hint: 'Rejection reasons and pause logic stay in the partner shell.' },
        { icon: 'restaurant-outline', label: 'Kitchen status', hint: 'Preparation milestones should be managed from this route.' },
        { icon: 'cube-outline', label: 'Ready for pickup', hint: 'Dispatch and rider handoff live here.' },
      ]}
      sections={[
        { icon: 'flash-outline', title: 'SLA management', body: 'Prep times, queue load and temporary throttling can be modelled without reusing consumer reorder screens.' },
        { icon: 'people-outline', title: 'Dispatch coordination', body: 'Partner-to-rider visibility and pickup notes should stay local to seller operations.' },
        { icon: 'warning-outline', title: 'Issue handling', body: 'Out-of-stock flows, delay warnings and refund escalation should be designed specifically for sellers.' },
      ]}
    />
  );
}