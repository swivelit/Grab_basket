import React from 'react';
import AppShellPlaceholder from '../../../components/app-shell-placeholder';

export default function DeliveryHomeScreen() {
  return (
    <AppShellPlaceholder
      shell="delivery"
      eyebrow="Grab Basket Delivery"
      title="Delivery operations dashboard"
      description="This is now a dedicated delivery shell. It can grow its own pickup, drop, navigation, and rider workflow screens without inheriting the customer tab structure."
      stats={[
        { value: 'Live', label: 'Shell status' },
        { value: '3', label: 'Primary tabs' },
        { value: '0', label: 'Consumer screens mixed in' },
        { value: 'Partner', label: 'Target role' },
      ]}
      actions={[
        { icon: 'radio-outline', label: 'Go online', hint: 'Availability, shift controls and zone selection belong here.' },
        { icon: 'navigate-outline', label: 'Open navigation', hint: 'Pickup to drop-off mapping can be added without touching consumer routes.' },
        { icon: 'notifications-outline', label: 'Dispatch alerts', hint: 'New assignment prompts and priority tasks should live in this shell.' },
        { icon: 'call-outline', label: 'Support', hint: 'Rider support, SOS and escalation tools stay separated from the customer app.' },
      ]}
      sections={[
        { icon: 'bicycle-outline', title: 'Shift control', body: 'Online or offline state, service zones, battery warnings and active delivery readiness should be owned by the delivery app.' },
        { icon: 'cube-outline', title: 'Pickup workflow', body: 'Store arrival, pickup verification, OTP checks and handoff steps can be modelled here without leaking customer-first components.' },
        { icon: 'flag-outline', title: 'Route execution', body: 'In-route updates, contact customer actions, proof-of-delivery and completion states belong in this shell only.' },
      ]}
    />
  );
}