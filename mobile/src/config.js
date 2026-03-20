export const API_BASE_URL = (process.env.EXPO_PUBLIC_API_BASE_URL || 'http://10.0.2.2:8000').replace(/\/+$/, '');

export const META_CONFIG = {
  adAccountId: process.env.EXPO_PUBLIC_META_AD_ACCOUNT_ID || '1246069857593252',
  appId: process.env.EXPO_PUBLIC_META_APP_ID || '',
  clientToken: process.env.EXPO_PUBLIC_META_CLIENT_TOKEN || '',
  scheme: process.env.EXPO_PUBLIC_META_APP_ID ? `fb${process.env.EXPO_PUBLIC_META_APP_ID}` : '',
};

export const ADS_CONFIG = {
  enabled: true,
  placements: {
    home_inline: process.env.EXPO_PUBLIC_META_HOME_INLINE_PLACEMENT_ID || '',
    food_inline: process.env.EXPO_PUBLIC_META_FOOD_INLINE_PLACEMENT_ID || '',
    dineout_inline: process.env.EXPO_PUBLIC_META_DINEOUT_INLINE_PLACEMENT_ID || '',
    events_top: process.env.EXPO_PUBLIC_META_EVENTS_TOP_PLACEMENT_ID || '',
    events_mid: process.env.EXPO_PUBLIC_META_EVENTS_MID_PLACEMENT_ID || '',
    explore_inline: process.env.EXPO_PUBLIC_META_EXPLORE_INLINE_PLACEMENT_ID || '',
    reorder_inline: process.env.EXPO_PUBLIC_META_REORDER_INLINE_PLACEMENT_ID || '',
    account_inline: process.env.EXPO_PUBLIC_META_ACCOUNT_INLINE_PLACEMENT_ID || '',
    store_inline: process.env.EXPO_PUBLIC_META_STORE_INLINE_PLACEMENT_ID || '',
    cart_inline: process.env.EXPO_PUBLIC_META_CART_INLINE_PLACEMENT_ID || '',
  },
};