import { useCallback, useEffect, useMemo, useState } from 'react';

import { STORAGE_KEYS, readStoredValue, writeStoredValue } from '../lib/storage';
import { isValidCart } from './grab-basket-utils';

export function useCartDomain() {
  const [cart, setCart] = useState({ vendorId: null, items: {} });
  const [favorites, setFavorites] = useState({});
  const [cartReady, setCartReady] = useState(false);

  useEffect(() => {
    let cancelled = false;

    (async () => {
      const [storedCart, storedFavorites] = await Promise.all([
        readStoredValue(STORAGE_KEYS.cart),
        readStoredValue(STORAGE_KEYS.favorites),
      ]);

      if (cancelled) return;

      try {
        const parsedCart = storedCart ? JSON.parse(storedCart) : null;
        if (isValidCart(parsedCart)) {
          setCart(parsedCart);
        }
      } catch {
        // ignore invalid cache
      }

      try {
        const parsedFavorites = storedFavorites ? JSON.parse(storedFavorites) : null;
        if (parsedFavorites && typeof parsedFavorites === 'object') {
          setFavorites(parsedFavorites);
        }
      } catch {
        // ignore invalid cache
      }

      setCartReady(true);
    })();

    return () => {
      cancelled = true;
    };
  }, []);

  useEffect(() => {
    if (!cartReady) return;
    writeStoredValue(STORAGE_KEYS.cart, JSON.stringify(cart)).catch(() => {});
  }, [cart, cartReady]);

  useEffect(() => {
    if (!cartReady) return;
    writeStoredValue(STORAGE_KEYS.favorites, JSON.stringify(favorites)).catch(() => {});
  }, [cartReady, favorites]);

  const cartItems = useMemo(() => Object.values(cart.items || {}), [cart]);

  const cartCount = useMemo(
    () => cartItems.reduce((sum, item) => sum + Number(item?.qty || 0), 0),
    [cartItems]
  );

  const toggleFavorite = useCallback((vendorId) => {
    if (vendorId == null) return;

    setFavorites((current) => ({
      ...current,
      [vendorId]: !current[vendorId],
    }));
  }, []);

  const addToCart = useCallback((vendor, product) => {
    if (!vendor?.id || !product?.id) return;

    setCart((current) => {
      const nextVendorId = Number(vendor.id);
      const isSameVendor = String(current.vendorId) === String(nextVendorId);
      const baseItems = isSameVendor ? current.items || {} : {};
      const currentItem = baseItems[product.id] || null;

      return {
        vendorId: nextVendorId,
        items: {
          ...baseItems,
          [product.id]: {
            id: product.id,
            name: product.name,
            price: Number(product.price || 0),
            image_url: product.image_url || '',
            qty: Number(currentItem?.qty || 0) + 1,
          },
        },
      };
    });
  }, []);

  const updateQty = useCallback((productId, qty) => {
    setCart((current) => {
      const nextItems = { ...(current.items || {}) };
      const nextQty = Number(qty || 0);

      if (nextQty <= 0) {
        delete nextItems[productId];
      } else if (nextItems[productId]) {
        nextItems[productId] = {
          ...nextItems[productId],
          qty: nextQty,
        };
      }

      return {
        vendorId: Object.keys(nextItems).length ? current.vendorId : null,
        items: nextItems,
      };
    });
  }, []);

  const clearCart = useCallback(() => {
    setCart({ vendorId: null, items: {} });
  }, []);

  return {
    cart,
    setCart,
    cartItems,
    cartCount,
    favorites,
    toggleFavorite,
    addToCart,
    updateQty,
    clearCart,
  };
}
