import { useMemo } from 'react';

import {
  FREE_DELIVERY_THRESHOLD,
  PLATFORM_FEE,
  findVendorById,
  getDeliveryFeeAmount,
} from './grab-basket-utils';

export function usePricingDomain({ cart, cartItems, vendors }) {
  const cartSubtotal = useMemo(
    () => cartItems.reduce((sum, item) => sum + Number(item?.price || 0) * Number(item?.qty || 0), 0),
    [cartItems]
  );

  const cartVendor = useMemo(
    () => findVendorById(vendors, cart.vendorId),
    [cart.vendorId, vendors]
  );

  const deliveryFeeAmount = useMemo(() => getDeliveryFeeAmount(cartVendor), [cartVendor]);
  const platformFeeAmount = PLATFORM_FEE;

  const freeDeliveryRemaining = useMemo(
    () => Math.max(0, FREE_DELIVERY_THRESHOLD - cartSubtotal),
    [cartSubtotal]
  );

  const freeDeliveryProgress = useMemo(() => {
    if (FREE_DELIVERY_THRESHOLD <= 0) return 1;
    return Math.min(1, cartSubtotal / FREE_DELIVERY_THRESHOLD);
  }, [cartSubtotal]);

  const cartTotal = useMemo(
    () => cartSubtotal + deliveryFeeAmount + platformFeeAmount,
    [cartSubtotal, deliveryFeeAmount, platformFeeAmount]
  );

  return {
    cartSubtotal,
    cartVendor,
    deliveryFeeAmount,
    platformFeeAmount,
    freeDeliveryRemaining,
    freeDeliveryProgress,
    cartTotal,
  };
}
