import React from 'react';
import AppShellPlaceholder from '../../../components/app-shell-placeholder';

export default function PartnerCatalogScreen() {
  return (
    <AppShellPlaceholder
      shell="partner"
      eyebrow="Catalog management"
      title="Products, availability, and pricing"
      description="Keep item CRUD, stock status, category setup, and pricing tools inside the partner shell so sellers no longer inherit customer-facing browse routes."
      stats={[
        { value: 'CRUD', label: 'Primary mode' },
        { value: 'Stock', label: 'Operational signal' },
        { value: 'Pricing', label: 'Business lever' },
        { value: 'Menu', label: 'Managed asset' },
      ]}
      actions={[
        { icon: 'add-circle-outline', label: 'Add product', hint: 'Create menu or grocery items from the seller app.' },
        { icon: 'create-outline', label: 'Edit listing', hint: 'Descriptions, imagery and tax data belong here.' },
        { icon: 'remove-circle-outline', label: 'Mark unavailable', hint: 'Stock-out and time-based availability should be seller-owned.' },
        { icon: 'megaphone-outline', label: 'Promo setup', hint: 'Discounts and featured items can be managed in this shell.' },
      ]}
      sections={[
        { icon: 'albums-outline', title: 'Category structure', body: 'Partner-specific category, modifier and bundle editors should live in this route tree.' },
        { icon: 'pricetag-outline', title: 'Pricing controls', body: 'Base price, offer price and tax-inclusive display settings are seller concerns and belong here.' },
        { icon: 'eye-outline', title: 'Availability windows', body: 'Breakfast, lunch, dinner or instant-delivery availability can be scheduled without sharing customer explore UI.' },
      ]}
    />
  );
}