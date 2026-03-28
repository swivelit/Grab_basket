const ANALYTICS_TAXONOMY_VERSION = 'v2';

const ANALYTICS_EVENTS = Object.freeze({
  consumerStoreViewed: 'consumer_store_viewed',
  consumerStoreShareOpened: 'consumer_store_share_opened',
  consumerAccountSupportActionClicked: 'consumer_account_support_action_clicked',
  deliveryOrdersScreenViewed: 'delivery_orders_screen_viewed',
  deliveryOrderPickupTapped: 'delivery_order_pickup_tapped',
  deliveryOrderDeliverTapped: 'delivery_order_deliver_tapped',
});

export { ANALYTICS_EVENTS, ANALYTICS_TAXONOMY_VERSION };
