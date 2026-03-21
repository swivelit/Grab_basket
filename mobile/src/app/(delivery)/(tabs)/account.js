import React from 'react';
import AppShellPlaceholder from '../../../components/app-shell-placeholder';

export default function DeliveryAccountScreen() {
  return (
    <AppShellPlaceholder
      shell="delivery"
      eyebrow="Delivery profile"
      title="Availability, vehicle, and support"
      description="The account area for riders should focus on compliance, vehicle details, shift preferences, and emergency support instead of customer favourites and addresses."
      stats={[
        { value: 'Profile', label: 'Rider identity' },
        { value: 'Vehicle', label: 'Ops asset' },
        { value: 'Docs', label: 'Compliance area' },
        { value: '24x7', label: 'Support path' },
      ]}
      actions={[
        { icon: 'person-circle-outline', label: 'Profile details', hint: 'Basic rider info and working-city context live here.' },
        { icon: 'car-sport-outline', label: 'Vehicle setup', hint: 'Vehicle type, plate details and verification status belong in this shell.' },
        { icon: 'document-lock-outline', label: 'Compliance docs', hint: 'License, ID and insurance flows stay isolated from the customer app.' },
        { icon: 'help-buoy-outline', label: 'Emergency support', hint: 'Fast support paths and incident reporting are delivery-only concerns.' },
      ]}
      sections={[
        { icon: 'time-outline', title: 'Shift preferences', body: 'Preferred slots, weekly availability and zone rotation fit the rider account experience.' },
        { icon: 'shield-outline', title: 'Safety centre', body: 'Emergency contacts, incident reports and policy acknowledgements should be scoped to the delivery shell.' },
        { icon: 'settings-outline', title: 'App preferences', body: 'Navigation defaults, notification tuning and language controls can be designed independently here.' },
      ]}
    />
  );
}