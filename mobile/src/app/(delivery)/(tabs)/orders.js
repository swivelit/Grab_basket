import React from 'react';
import AppShellPlaceholder from '../../../components/app-shell-placeholder';

export default function DeliveryOrdersScreen() {
  return (
    <AppShellPlaceholder
      shell="delivery"
      eyebrow="Assigned orders"
      title="Pickup and drop queue"
      description="Use this route for batched tasks, active route cards, pickup readiness, customer notes, and proof-of-delivery events."
      stats={[
        { value: 'Queue', label: 'Primary view' },
        { value: 'ETA', label: 'Rider KPI' },
        { value: 'Map', label: 'Core dependency' },
        { value: 'OTP', label: 'Delivery action' },
      ]}
      actions={[
        { icon: 'storefront-outline', label: 'Pickup ready', hint: 'Show seller readiness and pickup sequencing.' },
        { icon: 'time-outline', label: 'Delay handling', hint: 'Escalate late prep or route slowdown here.' },
        { icon: 'shield-checkmark-outline', label: 'Proof of delivery', hint: 'OTP, photo or signature flows fit this route.' },
        { icon: 'chatbubble-ellipses-outline', label: 'Contact customer', hint: 'Delivery-only comms should stay in this app shell.' },
      ]}
      sections={[
        { icon: 'git-network-outline', title: 'Batched route logic', body: 'Multi-order routing, stop sequencing and stacked delivery UI should be implemented here, not in consumer explore flows.' },
        { icon: 'alarm-outline', title: 'Exception states', body: 'Customer unreachable, store closed, address issue and return-to-store states can be added safely inside this shell.' },
        { icon: 'checkmark-done-outline', title: 'Completion flow', body: 'Delivered confirmation and post-delivery reporting belong only to the delivery route tree.' },
      ]}
    />
  );
}