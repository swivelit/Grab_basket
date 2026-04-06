import React, { useEffect, useMemo, useRef, useState } from 'react';
import {
  ActivityIndicator,
  Image,
  Modal,
  RefreshControl,
  ScrollView,
  StatusBar,
  StyleSheet,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from 'react-native';
import * as Application from 'expo-application';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import { useBottomTabBarHeight } from '@react-navigation/bottom-tabs';
import { useRouter } from 'expo-router';

import { BrandPalette, createShadow } from '@/constants/theme';
import { mapLegacyService } from '@/domains/grab-basket-utils';
import { buildApiUrl } from '../../config';
import InlineErrorCard from '../../components/inline-error-card';
import InlineNoticeCard from '../../components/inline-notice-card';
import { useGrabBasket } from '../../../App';

const GUEST_LINKS = [
  { key: 'offers', label: 'Offers', icon: 'pricetag-outline' },
  { key: 'feedback', label: 'Feedback', icon: 'chatbox-ellipses-outline' },
  { key: 'privacy', label: 'Privacy & terms', icon: 'shield-checkmark-outline' },
];

const ACCOUNT_SHORTCUTS = [
  { key: 'saved_addresses', label: 'Saved Address', subtitle: 'Manage delivery places', icon: 'location-outline' },
  { key: 'payment_modes', label: 'Payment Modes', subtitle: 'Cards, UPI and more', icon: 'card-outline' },
  { key: 'refunds', label: 'My Refunds', subtitle: 'Refunds and updates', icon: 'refresh-circle-outline' },
  { key: 'wallet', label: 'My Wallet', subtitle: 'Wallet balance & credits', icon: 'wallet-outline' },
];

const ACCOUNT_LINKS = [
  { key: 'offers', label: 'Offers', icon: 'pricetag-outline' },
  { key: 'help', label: 'Help & support', icon: 'help-circle-outline' },
  { key: 'wishlist', label: 'My Wishlist', icon: 'bookmark-outline' },
  { key: 'favourites', label: 'Favourites', icon: 'heart-outline' },
  { key: 'statements', label: 'Account Statements', icon: 'document-text-outline' },
  { key: 'corporate_rewards', label: 'Corporate Rewards', icon: 'briefcase-outline' },
  { key: 'student_rewards', label: 'Student Rewards', icon: 'school-outline' },
  { key: 'partner_rewards', label: 'Partner Rewards', icon: 'ribbon-outline' },
  { key: 'contact_pref', label: 'Allow stores to contact you', icon: 'chatbubbles-outline' },
];

const TERMINAL_STATUSES = new Set(['DELIVERED', 'CANCELLED_BY_CUSTOMER', 'REJECTED_BY_SELLER', 'PAYMENT_FAILED']);
const LIVE_ORDER_STATUSES = new Set([
  'PAYMENT_PENDING',
  'CREATED',
  'PAYMENT_VERIFIED',
  'ACCEPTED_BY_SELLER',
  'ASSIGNED_TO_PARTNER',
  'READY_FOR_PICKUP',
  'PICKED_UP',
]);

const HANDOFF_STEPS = [
  { key: 'placed', title: 'Order placed', statuses: ['PAYMENT_PENDING', 'PAYMENT_VERIFIED', 'CREATED'] },
  { key: 'accepted', title: 'Seller accepted', statuses: ['ACCEPTED_BY_SELLER'] },
  { key: 'assigned', title: 'Rider assigned', statuses: ['ASSIGNED_TO_PARTNER'] },
  { key: 'ready', title: 'Packed & ready', statuses: ['READY_FOR_PICKUP'] },
  { key: 'picked_up', title: 'Picked up', statuses: ['PICKED_UP'] },
  { key: 'delivered', title: 'Delivered', statuses: ['DELIVERED'] },
];

function money(value) {
  return `₹${Number(value || 0).toFixed(0)}`;
}

function normalizeStatus(value) {
  return String(value || '').trim().toUpperCase();
}

function prettyStatus(value) {
  return (
    String(value || '')
      .replace(/_/g, ' ')
      .trim()
      .toLowerCase()
      .replace(/\b\w/g, (character) => character.toUpperCase()) || 'Processing'
  );
}

function formatDate(value) {
  if (!value) return '—';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return '—';
  return date.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
}

function formatDateTime(value) {
  if (!value) return 'Recently';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return 'Recently';
  return date.toLocaleString('en-IN', {
    day: 'numeric',
    month: 'short',
    hour: 'numeric',
    minute: '2-digit',
  });
}

function initials(value = '') {
  return String(value || '')
    .split(/[\s@._-]+/)
    .filter(Boolean)
    .map((part) => part[0])
    .join('')
    .slice(0, 2)
    .toUpperCase();
}

function getProfileTitle(profile, authEmail) {
  const name = String(profile?.full_name || '').trim();
  if (name) return name;
  const phone = String(profile?.phone || '').trim();
  if (phone) return phone;
  const email = String(profile?.email || authEmail || '').trim();
  if (!email) return 'GrabBasket Member';
  return email.split('@')[0].replace(/[._-]+/g, ' ').replace(/\b\w/g, (character) => character.toUpperCase());
}

function getProfileMeta(profile, authEmail) {
  return [...new Set([profile?.phone, profile?.email, authEmail].map((item) => String(item || '').trim()).filter(Boolean))];
}

function eventTime(event) {
  return Date.parse(event?.created_at || 0) || 0;
}

function mergeEvents(base = [], live = []) {
  const byKey = new Map();
  [...base, ...live].forEach((event, index) => {
    if (!event || typeof event !== 'object') return;
    const key = Number(event?.id) > 0 ? `id:${event.id}` : `${event?.status || 'status'}:${event?.created_at || index}`;
    byKey.set(key, event);
  });
  return [...byKey.values()].sort((left, right) => eventTime(right) - eventTime(left));
}

function isLiveOrder(order) {
  return LIVE_ORDER_STATUSES.has(normalizeStatus(order?.status));
}

function getOrderItemsLabel(order) {
  const items = Array.isArray(order?.items) ? order.items : [];
  const names = items.map((item) => String(item?.name || item?.name_snapshot || '').trim()).filter(Boolean);
  if (names.length > 1) return `${names[0]} +${names.length - 1} more`;
  if (names.length === 1) return names[0];
  const totalItems = Number(order?.item_count || items.length || 0);
  if (totalItems > 0) return `${totalItems} item${totalItems > 1 ? 's' : ''}`;
  return 'Order details available in history';
}

function getRefundState(order) {
  const refundStatus = normalizeStatus(order?.refund_status);
  const paymentStatus = normalizeStatus(order?.payment_status);
  const status = normalizeStatus(order?.status || order?.status_label);

  if (['COMPLETED', 'SUCCESS', 'PROCESSED', 'CREDITED', 'REFUNDED'].includes(refundStatus)) return 'completed';
  if (['PENDING', 'PROCESSING', 'REQUESTED', 'INITIATED', 'RETRYING'].includes(refundStatus)) return 'active';
  if (refundStatus && refundStatus !== 'NOT_APPLICABLE' && refundStatus !== 'FAILED') return 'active';
  if (status.includes('CANCELLED') && paymentStatus === 'PAID') return 'active';
  return 'none';
}

function hasRefundForOrder(order) {
  return getRefundState(order) !== 'none';
}

function getOrderTone(order) {
  const refundState = getRefundState(order);
  const status = normalizeStatus(order?.status || order?.status_label || order?.payment_status);

  if (refundState === 'completed') return { label: 'Refund completed', fg: BrandPalette.success, bg: BrandPalette.successSoft };
  if (refundState === 'active') return { label: 'Refund in progress', fg: BrandPalette.warning, bg: BrandPalette.warningSoft };
  if (status === 'DELIVERED') return { label: 'Delivered', fg: BrandPalette.success, bg: BrandPalette.successSoft };
  if (['CANCELLED_BY_CUSTOMER', 'REJECTED_BY_SELLER', 'PAYMENT_FAILED'].includes(status)) {
    return { label: prettyStatus(status), fg: BrandPalette.danger, bg: BrandPalette.dangerSoft };
  }
  return { label: prettyStatus(status || 'processing'), fg: BrandPalette.warning, bg: BrandPalette.warningSoft };
}

function getPrimaryOrderAction(order) {
  if (isLiveOrder(order)) return 'Track live';
  if (hasRefundForOrder(order)) return 'Refund Details';
  return 'Reorder';
}

function getStepIndex(order, events = [], tracking = null) {
  const status = normalizeStatus(order?.status);
  const statuses = new Set(events.map((event) => normalizeStatus(event?.status)));
  if (status === 'DELIVERED' || statuses.has('DELIVERED')) return 5;
  if (status === 'PICKED_UP' || statuses.has('PICKED_UP')) return 4;
  if (status === 'READY_FOR_PICKUP' || statuses.has('READY_FOR_PICKUP')) return 3;
  if (status === 'ASSIGNED_TO_PARTNER' || statuses.has('ASSIGNED_TO_PARTNER') || tracking?.assigned) return 2;
  if (status === 'ACCEPTED_BY_SELLER' || statuses.has('ACCEPTED_BY_SELLER')) return 1;
  return 0;
}

function buildSteps(order, events = [], tracking = null) {
  const current = getStepIndex(order, events, tracking);
  const closed = TERMINAL_STATUSES.has(normalizeStatus(order?.status)) && normalizeStatus(order?.status) !== 'DELIVERED';
  return HANDOFF_STEPS.map((step, index) => ({
    ...step,
    done: !closed && index <= current,
    current: !closed && index === current,
  }));
}

function getSellerSummary(order) {
  const status = normalizeStatus(order?.status);
  if (status === 'REJECTED_BY_SELLER') {
    return { title: 'Seller rejected the order', subtitle: 'The order closed before merchant handoff.', tone: 'danger', icon: 'close-circle-outline' };
  }
  if (status === 'READY_FOR_PICKUP') {
    return { title: 'Seller finished prep', subtitle: 'Packed and waiting at pickup.', tone: 'success', icon: 'bag-handle-outline' };
  }
  if (status === 'PICKED_UP' || status === 'DELIVERED') {
    return { title: 'Seller handoff completed', subtitle: 'The rider has already collected the order.', tone: 'success', icon: 'storefront-outline' };
  }
  if (status === 'ASSIGNED_TO_PARTNER') {
    return { title: 'Seller is preparing the order', subtitle: 'Dispatch has a rider while the store finishes prep.', tone: 'warning', icon: 'restaurant-outline' };
  }
  if (status === 'ACCEPTED_BY_SELLER') {
    return { title: 'Seller accepted the order', subtitle: 'The store is actively preparing your basket.', tone: 'warning', icon: 'checkmark-done-outline' };
  }
  return { title: 'Waiting for seller confirmation', subtitle: 'The store has not accepted the order yet.', tone: 'info', icon: 'time-outline' };
}

function getRiderSummary(order, tracking = null) {
  const status = normalizeStatus(order?.status);
  if (status === 'DELIVERED') {
    return { title: 'Delivery completed', subtitle: 'The final handoff is complete.', tone: 'success', icon: 'checkmark-circle-outline' };
  }
  if (status === 'PICKED_UP') {
    return {
      title: 'Rider is on the way',
      subtitle: tracking?.has_location
        ? `Latest rider ping ${formatDateTime(tracking?.ts || tracking?.created_at)}.`
        : 'The rider picked up the order and is heading to the drop.',
      tone: 'warning',
      icon: 'bicycle-outline',
    };
  }
  if (tracking?.assigned && tracking?.has_location) {
    return {
      title: 'Rider is live in the system',
      subtitle: `Latest rider ping ${formatDateTime(tracking?.ts || tracking?.created_at)}.`,
      tone: 'success',
      icon: 'navigate-outline',
    };
  }
  if (status === 'ASSIGNED_TO_PARTNER' || tracking?.assigned) {
    return {
      title: 'Rider assigned',
      subtitle: 'Dispatch assigned a rider, waiting for pickup or first live ping.',
      tone: 'warning',
      icon: 'bicycle-outline',
    };
  }
  if (['CANCELLED_BY_CUSTOMER', 'REJECTED_BY_SELLER', 'PAYMENT_FAILED'].includes(status)) {
    return { title: 'No rider handoff', subtitle: 'The order closed before delivery movement began.', tone: 'danger', icon: 'remove-circle-outline' };
  }
  return { title: 'Waiting for rider assignment', subtitle: 'Dispatch has not assigned a rider yet.', tone: 'info', icon: 'time-outline' };
}

function toneColors(tone) {
  if (tone === 'success') return { fg: BrandPalette.success, bg: BrandPalette.successSoft };
  if (tone === 'warning') return { fg: BrandPalette.warning, bg: BrandPalette.warningSoft };
  if (tone === 'danger') return { fg: BrandPalette.danger, bg: BrandPalette.dangerSoft };
  return { fg: BrandPalette.info, bg: BrandPalette.infoSoft };
}

function handleLinkNotice(key) {
  if (key === 'offers') return 'Offers can be connected to the promotions flow next.';
  if (key === 'help') return 'Help & support can be connected to support tickets or chat next.';
  if (key === 'wishlist') return 'My Wishlist is ready for saved products and stores.';
  if (key === 'favourites') return 'Favourites can show your saved stores and frequent picks.';
  if (key === 'statements') return 'Account Statements can list invoices, payments and credits.';
  if (key === 'corporate_rewards') return 'Corporate Rewards is ready for business perks and credits.';
  if (key === 'student_rewards') return 'Student Rewards is ready for student-only offers.';
  if (key === 'partner_rewards') return 'Partner Rewards is ready for brand and merchant collaborations.';
  if (key === 'contact_pref') return 'Allow stores to contact you can be connected to communication preferences.';
  return 'This section is ready for the next integration.';
}

function Field({ value, onChangeText, placeholder, secureTextEntry = false, keyboardType = 'default' }) {
  return (
    <TextInput
      value={value}
      onChangeText={onChangeText}
      placeholder={placeholder}
      placeholderTextColor={BrandPalette.subtle}
      secureTextEntry={secureTextEntry}
      keyboardType={keyboardType}
      autoCapitalize="none"
      style={styles.input}
    />
  );
}

function Surface({ children, style }) {
  return <View style={[styles.surface, style]}>{children}</View>;
}

function LinkRow({ icon, label, onPress, isLast = false }) {
  return (
    <TouchableOpacity activeOpacity={0.94} style={[styles.linkRow, !isLast && styles.linkBorder]} onPress={onPress}>
      <View style={styles.linkLeft}>
        <Ionicons name={icon} size={20} color={BrandPalette.text} />
        <Text style={styles.linkLabel}>{label}</Text>
      </View>
      <Ionicons name="chevron-forward" size={18} color={BrandPalette.subtle} />
    </TouchableOpacity>
  );
}

function ShortcutTile({ item, onPress }) {
  return (
    <TouchableOpacity activeOpacity={0.94} style={styles.shortcutTile} onPress={onPress}>
      <View style={styles.shortcutIconWrap}>
        <Ionicons name={item.icon} size={22} color={BrandPalette.text} />
      </View>
      <Text style={styles.shortcutLabel}>{item.label}</Text>
      <Text style={styles.shortcutSubtitle}>{item.subtitle}</Text>
    </TouchableOpacity>
  );
}

function StatusPill({ tone }) {
  return (
    <View style={[styles.statusPill, { backgroundColor: tone.bg }]}>
      <Text style={[styles.statusPillText, { color: tone.fg }]}>{tone.label}</Text>
    </View>
  );
}

function OrderCard({ order, onOpen, onPrimary }) {
  const tone = getOrderTone(order);
  const imageUri = String(order?.vendor_image_url || '').trim();

  return (
    <TouchableOpacity activeOpacity={0.95} style={styles.orderCard} onPress={onOpen}>
      <View style={styles.orderTopRow}>
        <View style={styles.orderIdentity}>
          {imageUri ? (
            <Image source={{ uri: imageUri }} style={styles.orderImage} />
          ) : (
            <View style={styles.orderFallback}>
              <Text style={styles.orderFallbackText}>{initials(order?.vendor_name || 'GB')}</Text>
            </View>
          )}
          <View style={{ flex: 1 }}>
            <Text numberOfLines={1} style={styles.orderVendor}>{order?.vendor_name || 'Store'}</Text>
            <Text numberOfLines={1} style={styles.orderMeta}>{getOrderItemsLabel(order)}</Text>
          </View>
        </View>
        <StatusPill tone={tone} />
      </View>

      {isLiveOrder(order) ? (
        <View style={styles.liveChip}>
          <Ionicons name="radio-outline" size={13} color={BrandPalette.success} />
          <Text style={styles.liveChipText}>Live seller / rider state</Text>
        </View>
      ) : null}

      <TouchableOpacity activeOpacity={0.92} style={styles.secondaryButton} onPress={onPrimary}>
        <Text style={styles.secondaryButtonText}>{getPrimaryOrderAction(order)}</Text>
      </TouchableOpacity>

      <Text style={styles.footnote}>
        Ordered {formatDateTime(order?.created_at || order?.updated_at)} • Total {money(order?.total_amount || 0)}
      </Text>
    </TouchableOpacity>
  );
}

function StepRail({ steps }) {
  return (
    <View style={{ gap: 10 }}>
      {steps.map((step, index) => {
        const dotColor = step.done ? BrandPalette.success : step.current ? BrandPalette.warning : BrandPalette.border;
        const textColor = step.done ? BrandPalette.success : step.current ? BrandPalette.warning : BrandPalette.textMuted;

        return (
          <View key={step.key} style={{ gap: 6 }}>
            <View style={styles.stepTop}>
              <View style={[styles.stepDot, { backgroundColor: dotColor }]} />
              {index < steps.length - 1 ? (
                <View style={[styles.stepLine, { backgroundColor: step.done ? BrandPalette.success : BrandPalette.border }]} />
              ) : null}
            </View>
            <Text style={[styles.stepTitle, { color: textColor }]}>{step.title}</Text>
          </View>
        );
      })}
    </View>
  );
}

function StateCard({ title, subtitle, tone, icon }) {
  const colors = toneColors(tone);
  return (
    <View style={styles.stateCard}>
      <View style={[styles.stateIcon, { backgroundColor: colors.bg }]}>
        <Ionicons name={icon} size={20} color={colors.fg} />
      </View>
      <View style={{ flex: 1 }}>
        <Text style={styles.stateTitle}>{title}</Text>
        <Text style={styles.stateSubtitle}>{subtitle}</Text>
      </View>
    </View>
  );
}

function MetaRow({ icon, label, value, muted = false }) {
  return (
    <View style={styles.metaRow}>
      <Ionicons name={icon} size={16} color={BrandPalette.primary} style={{ marginTop: 2 }} />
      <View style={{ flex: 1 }}>
        <Text style={styles.metaLabel}>{label}</Text>
        <Text style={[styles.metaValue, muted && styles.metaValueMuted]}>{value}</Text>
      </View>
    </View>
  );
}

function Timeline({ events }) {
  if (!events.length) {
    return (
      <View style={styles.emptyTimeline}>
        <Text style={styles.emptyTitle}>No timeline yet</Text>
        <Text style={styles.emptySubtitle}>Seller, dispatch, rider, and delivery checkpoints will appear here.</Text>
      </View>
    );
  }

  return (
    <View style={{ marginTop: 4 }}>
      {events.map((event, index) => {
        const status = normalizeStatus(event?.status);
        const tone = toneColors(
          status === 'DELIVERED' || status === 'READY_FOR_PICKUP'
            ? 'success'
            : TERMINAL_STATUSES.has(status)
              ? 'danger'
              : 'warning'
        );

        return (
          <View key={`${event?.id || event?.status}-${event?.created_at || index}`} style={styles.timelineRow}>
            <View style={styles.timelineRail}>
              <View style={[styles.timelineDot, { backgroundColor: tone.fg }]} />
              {index < events.length - 1 ? <View style={styles.timelineLine} /> : null}
            </View>
            <View style={{ flex: 1, paddingBottom: 14 }}>
              <Text style={styles.timelineTitle}>{prettyStatus(event?.status)}</Text>
              <Text style={styles.timelineMeta}>{formatDateTime(event?.created_at)}</Text>
              {event?.note ? <Text style={styles.timelineNote}>{event.note}</Text> : null}
            </View>
          </View>
        );
      })}
    </View>
  );
}

function OrderDetailsModal({
  visible,
  order,
  events,
  tracking,
  trackingLoading,
  refreshing,
  onRefresh,
  onClose,
  onPrimary,
}) {
  if (!visible || !order) return null;

  const tone = getOrderTone(order);
  const steps = buildSteps(order, events, tracking);
  const seller = getSellerSummary(order);
  const rider = getRiderSummary(order, tracking);
  const latestEvent = events[0];
  const dispatchSummary = trackingLoading
    ? 'Checking the latest rider state…'
    : tracking?.assigned
      ? tracking?.has_location
        ? `Live rider ping at ${formatDateTime(tracking?.ts || tracking?.created_at)}`
        : 'Rider assigned, waiting for first live location'
      : 'No rider assigned yet';

  return (
    <Modal visible={visible} transparent animationType="slide" onRequestClose={onClose}>
      <View style={styles.modalBackdrop}>
        <View style={styles.sheet}>
          <View style={styles.handle} />
          <View style={styles.sheetHeader}>
            <View style={{ flex: 1 }}>
              <Text style={styles.sheetTitle}>Order #{order.id}</Text>
              <Text style={styles.sheetSubtitle}>{order?.vendor_name || 'Store'}</Text>
            </View>
            <TouchableOpacity activeOpacity={0.92} style={styles.iconButton} onPress={onClose}>
              <Ionicons name="close" size={20} color={BrandPalette.text} />
            </TouchableOpacity>
          </View>

          <ScrollView
            showsVerticalScrollIndicator={false}
            contentContainerStyle={{ padding: 16, gap: 14, paddingBottom: 28 }}
            refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={BrandPalette.primary} />}>
            <Surface>
              <View style={styles.rowBetween}>
                <StatusPill tone={tone} />
                <TouchableOpacity activeOpacity={0.92} style={styles.refreshChip} onPress={onRefresh}>
                  <Ionicons name="refresh-outline" size={14} color={BrandPalette.primary} />
                  <Text style={styles.refreshChipText}>Refresh</Text>
                </TouchableOpacity>
              </View>

              <Text style={styles.blockTitle}>Seller → rider → customer handoff</Text>
              <Text style={styles.blockSubtitle}>
                {latestEvent?.note || 'Live handoff updates will continue to flow into this order details view.'}
              </Text>
              <StepRail steps={steps} />
            </Surface>

            <Surface>
              <Text style={styles.sectionTitle}>Live fulfilment state</Text>
              <StateCard {...seller} />
              <View style={{ height: 10 }} />
              <StateCard {...rider} />
            </Surface>

            <Surface>
              <Text style={styles.sectionTitle}>Dispatch visibility</Text>
              {trackingLoading ? (
                <View style={styles.inlineRow}>
                  <ActivityIndicator size="small" color={BrandPalette.primary} />
                  <Text style={styles.inlineText}>Loading rider state…</Text>
                </View>
              ) : null}

              <MetaRow icon="storefront-outline" label="Seller" value={seller.title} />
              <MetaRow icon="bicycle-outline" label="Rider" value={rider.title} />
              <MetaRow icon="radio-outline" label="Dispatch feed" value={dispatchSummary} muted={!tracking?.assigned} />
              <MetaRow icon="card-outline" label="Payment" value={`${order?.payment_method || 'COD'} • ${prettyStatus(order?.payment_status || 'pending')}`} />
              <MetaRow
                icon="location-outline"
                label="Delivery address"
                value={order?.delivery_address_label || 'Saved address not attached yet'}
                muted={!order?.delivery_address_label}
              />
              <MetaRow
                icon="time-outline"
                label="ETA / distance"
                value={[
                  order?.delivery_eta_minutes ? `${order.delivery_eta_minutes} mins ETA` : '',
                  order?.delivery_distance_km ? `${Number(order.delivery_distance_km).toFixed(1)} km` : '',
                ].filter(Boolean).join(' • ') || 'ETA becomes clearer as seller and rider events arrive'}
                muted={!order?.delivery_eta_minutes && !order?.delivery_distance_km}
              />
            </Surface>

            <Surface>
              <Text style={styles.sectionTitle}>Order summary</Text>
              <MetaRow icon="receipt-outline" label="Items" value={getOrderItemsLabel(order)} />
              <MetaRow icon="cash-outline" label="Bill total" value={money(order?.total_amount || 0)} />
              <MetaRow icon="calendar-outline" label="Placed" value={formatDateTime(order?.created_at || order?.updated_at)} />
            </Surface>

            <Surface>
              <View style={styles.rowBetween}>
                <Text style={styles.sectionTitle}>Timeline</Text>
                <Text style={styles.smallMuted}>Newest first</Text>
              </View>
              <Timeline events={events} />
            </Surface>

            <TouchableOpacity activeOpacity={0.95} style={styles.primaryButton} onPress={onPrimary}>
              <Text style={styles.primaryButtonText}>{getPrimaryOrderAction(order)}</Text>
            </TouchableOpacity>
          </ScrollView>
        </View>
      </View>
    </Modal>
  );
}

export default function AccountScreen() {
  const router = useRouter();
  const tabBarHeight = useBottomTabBarHeight();
  const {
    appVariantName,
    sessionReady,
    isAuthenticated,
    authToken,
    authEmail,
    authLoading,
    login,
    register,
    logout,
    profile,
    deviceId,
    addresses,
    defaultAddress,
    orderHistory,
    loadOrders,
    ordersLoading,
    loadAddresses,
    inlineErrors,
    timelineEventsByOrder,
    subscribeOrderTimeline,
  } = useGrabBasket();

  const [authMode, setAuthMode] = useState('login');
  const [loginIdentifier, setLoginIdentifier] = useState(authEmail || '');
  const [email, setEmail] = useState('');
  const [phone, setPhone] = useState('');
  const [password, setPassword] = useState('password');
  const [notice, setNotice] = useState('');
  const [pastOrderTab, setPastOrderTab] = useState('food');
  const [selectedOrderId, setSelectedOrderId] = useState(null);
  const [trackingMap, setTrackingMap] = useState({});
  const [trackingLoading, setTrackingLoading] = useState(false);
  const [detailsRefreshing, setDetailsRefreshing] = useState(false);
  const hydratedRef = useRef(false);
  const loadOrdersRef = useRef(loadOrders);
  const loadAddressesRef = useRef(loadAddresses);

  useEffect(() => {
    loadOrdersRef.current = loadOrders;
  }, [loadOrders]);

  useEffect(() => {
    loadAddressesRef.current = loadAddresses;
  }, [loadAddresses]);

  useEffect(() => {
    if (authEmail) setLoginIdentifier(authEmail);
  }, [authEmail]);

  useEffect(() => {
    if (!sessionReady) return;

    if (!isAuthenticated) {
      hydratedRef.current = false;
      setSelectedOrderId(null);
      setTrackingMap({});
      return;
    }

    if (hydratedRef.current) return;
    hydratedRef.current = true;
    loadAddressesRef.current?.().catch(() => {});
    loadOrdersRef.current?.({ silent: true }).catch(() => {});
  }, [isAuthenticated, sessionReady]);

  const versionText = useMemo(() => {
    const version = Application.nativeApplicationVersion || '1.0.0';
    const build = Application.nativeBuildVersion || '1';
    return `App version ${version} (${build})`;
  }, []);

  const profileTitle = useMemo(() => getProfileTitle(profile, authEmail), [profile, authEmail]);
  const profileMeta = useMemo(() => getProfileMeta(profile, authEmail), [profile, authEmail]);
  const addressCount = Array.isArray(addresses) ? addresses.length : 0;
  const memberSince = formatDate(profile?.created_at);

  const filteredOrders = useMemo(() => {
    const orders = Array.isArray(orderHistory) ? orderHistory : [];
    return orders.filter((order) => mapLegacyService(order?.service) === pastOrderTab);
  }, [orderHistory, pastOrderTab]);

  const selectedOrder = useMemo(
    () => (Array.isArray(orderHistory) ? orderHistory.find((order) => String(order.id) === String(selectedOrderId)) || null : null),
    [orderHistory, selectedOrderId]
  );

  const selectedEvents = useMemo(
    () => mergeEvents(selectedOrder?.events || [], timelineEventsByOrder?.[selectedOrder?.id] || []),
    [selectedOrder, timelineEventsByOrder]
  );

  const selectedTracking = useMemo(
    () => (selectedOrder ? trackingMap[String(selectedOrder.id)] || null : null),
    [selectedOrder, trackingMap]
  );

  const highlightedAddress = useMemo(() => {
    if (defaultAddress?.label || defaultAddress?.line1) {
      return [defaultAddress?.label, defaultAddress?.line1].filter(Boolean).join(' · ');
    }
    return addressCount ? `${addressCount} saved addresses` : 'No saved addresses yet';
  }, [addressCount, defaultAddress]);

  const fetchTracking = async (orderId, { silent = false } = {}) => {
    const numericOrderId = Number(orderId || 0);
    if (!numericOrderId || !authToken) return null;

    if (!silent) setTrackingLoading(true);

    try {
      const response = await fetch(buildApiUrl(`/tracking/order/${numericOrderId}/partner_latest`), {
        method: 'GET',
        headers: {
          Accept: 'application/json',
          Authorization: `Bearer ${String(authToken || '').trim()}`,
        },
      });

      const payload = await response.json().catch(() => ({}));
      if (!response.ok) throw new Error(String(payload?.detail || `Tracking request failed (${response.status})`));

      setTrackingMap((current) => ({ ...current, [String(numericOrderId)]: payload }));
      return payload;
    } catch (error) {
      setNotice(error?.message || 'Could not load live rider state for this order.');
      return null;
    } finally {
      if (!silent) setTrackingLoading(false);
    }
  };

  useEffect(() => {
    if (!selectedOrder?.id || !authToken) return undefined;

    const subscription = subscribeOrderTimeline({ orderId: selectedOrder.id, sinceId: 0, onError: () => {} });
    fetchTracking(selectedOrder.id).catch(() => {});

    if (!isLiveOrder(selectedOrder)) {
      return () => subscription?.close?.();
    }

    const timer = setInterval(() => {
      fetchTracking(selectedOrder.id, { silent: true }).catch(() => {});
      loadOrders({ silent: true }).catch(() => {});
    }, 12000);

    return () => {
      clearInterval(timer);
      subscription?.close?.();
    };
  }, [authToken, loadOrders, selectedOrder?.id, selectedOrder?.status, subscribeOrderTimeline]);

  const handleAuth = async () => {
    if (authMode === 'login') {
      if (!loginIdentifier.trim() || !password.trim()) {
        setNotice('Enter your email or phone number and password.');
        return;
      }

      const ok = await login({ identifier: loginIdentifier, password });
      if (ok) setNotice('You are now signed in.');
      return;
    }

    if (!email.trim() || !phone.trim() || !password.trim()) {
      setNotice('Enter your email, phone number and password.');
      return;
    }

    const ok = await register({ email, phone, password });
    if (ok) {
      setLoginIdentifier(phone.trim() || email.trim());
      setNotice('Your account is ready.');
    }
  };

  const handleShortcut = async (key) => {
    if (key === 'saved_addresses') {
      await loadAddresses().catch(() => {});
      router.push('/saved-addresses');
      return;
    }

    if (key === 'refunds') {
      router.push('/refunds');
      return;
    }

    if (key === 'payment_modes') {
      setNotice('Payment Modes is ready for UPI, cards and wallet integration.');
      return;
    }

    if (key === 'wallet') {
      setNotice('My Wallet is ready for balance, credits and cashback integration.');
    }
  };

  const runOrderAction = (order) => {
    if (!order) return;

    if (isLiveOrder(order)) {
      setSelectedOrderId(order.id);
      return;
    }

    if (hasRefundForOrder(order)) {
      setSelectedOrderId(null);
      router.push('/refunds');
      return;
    }

    setSelectedOrderId(null);
    router.push('/(tabs)/reorder');
  };

  const refreshSelectedOrder = async () => {
    if (!selectedOrder?.id) return;

    try {
      setDetailsRefreshing(true);
      await loadOrders({ silent: true });
      await fetchTracking(selectedOrder.id, { silent: true });
    } finally {
      setDetailsRefreshing(false);
    }
  };

  if (!sessionReady) {
    return (
      <SafeAreaView style={styles.safeArea} edges={['top']}>
        <StatusBar barStyle="dark-content" />
        <View style={styles.center}>
          <ActivityIndicator color={BrandPalette.primary} />
          <Text style={styles.centerText}>Preparing your account...</Text>
        </View>
      </SafeAreaView>
    );
  }

  return (
    <SafeAreaView style={styles.safeArea} edges={['top']}>
      <StatusBar barStyle="dark-content" />
      <ScrollView
        showsVerticalScrollIndicator={false}
        contentContainerStyle={{ paddingBottom: tabBarHeight + 28 }}
        refreshControl={
          isAuthenticated ? <RefreshControl refreshing={ordersLoading} onRefresh={() => loadOrders()} tintColor={BrandPalette.primary} /> : undefined
        }>
        {!isAuthenticated ? (
          <>
            <View style={styles.hero}>
              <Text style={styles.eyebrow}>{appVariantName}</Text>
              <Text style={styles.heroTitle}>One app for food, grocery, dining and more in minutes.</Text>
              <Text style={styles.heroCopy}>
                Match the clean, conversion-first flow from leading consumer apps while keeping the GrabBasket brand language.
              </Text>
            </View>

            <Surface style={{ marginHorizontal: 16, marginTop: 8 }}>
              <View style={styles.authTabs}>
                <TouchableOpacity
                  activeOpacity={0.94}
                  style={[styles.authTab, authMode === 'login' && styles.authTabActive]}
                  onPress={() => setAuthMode('login')}>
                  <Text style={[styles.authTabText, authMode === 'login' && styles.authTabTextActive]}>Login</Text>
                </TouchableOpacity>
                <TouchableOpacity
                  activeOpacity={0.94}
                  style={[styles.authTab, authMode === 'register' && styles.authTabActive]}
                  onPress={() => setAuthMode('register')}>
                  <Text style={[styles.authTabText, authMode === 'register' && styles.authTabTextActive]}>Sign up</Text>
                </TouchableOpacity>
              </View>

              {inlineErrors.auth ? <InlineErrorCard title="Authentication issue" message={inlineErrors.auth} /> : null}
              {notice ? <InlineNoticeCard title="Ready" message={notice} onDismiss={() => setNotice('')} /> : null}

              {authMode === 'login' ? (
                <>
                  <Field value={loginIdentifier} onChangeText={setLoginIdentifier} placeholder="Email or phone number" />
                  <Field value={password} onChangeText={setPassword} placeholder="Password" secureTextEntry />
                  <Text style={styles.helper}>Use your email address or mobile number to sign in.</Text>
                </>
              ) : (
                <>
                  <Field value={email} onChangeText={setEmail} placeholder="Email address" keyboardType="email-address" />
                  <Field value={phone} onChangeText={setPhone} placeholder="Phone number" keyboardType="phone-pad" />
                  <Field value={password} onChangeText={setPassword} placeholder="Password" secureTextEntry />
                  <Text style={styles.helper}>Sign up now collects both email and mobile number.</Text>
                </>
              )}

              <TouchableOpacity activeOpacity={0.95} style={styles.primaryButton} onPress={handleAuth} disabled={authLoading}>
                {authLoading ? (
                  <ActivityIndicator color={BrandPalette.white} size="small" />
                ) : (
                  <Text style={styles.primaryButtonText}>{authMode === 'login' ? 'Login' : 'Create account'}</Text>
                )}
              </TouchableOpacity>

              <Text style={styles.smallMuted}>By tapping in, you agree to our terms of service and privacy policy.</Text>

              <View style={{ gap: 10 }}>
                {GUEST_LINKS.map((item) => (
                  <LinkRow key={item.key} icon={item.icon} label={item.label} onPress={() => setNotice(`${item.label} can be expanded next.`)} isLast />
                ))}
              </View>

              <Text style={styles.smallMutedCenter}>{versionText}</Text>
            </Surface>
          </>
        ) : (
          <View style={styles.page}>
            <Surface>
              <View style={styles.rowBetween}>
                <View style={styles.avatar}>
                  <Text style={styles.avatarText}>{initials(profileTitle || authEmail || 'GB')}</Text>
                </View>
                <TouchableOpacity activeOpacity={0.94} style={styles.helpChip} onPress={() => setNotice(handleLinkNotice('help'))}>
                  <Text style={styles.helpChipText}>Help</Text>
                </TouchableOpacity>
              </View>

              <Text style={styles.profileTitle}>{profileTitle}</Text>
              {profileMeta.map((item) => (
                <Text key={item} style={styles.profileMeta}>{item}</Text>
              ))}

              <View style={styles.memberRow}>
                <Ionicons name="sparkles-outline" size={14} color={BrandPalette.primary} />
                <Text style={styles.memberText}>{memberSince !== '—' ? `Member since ${memberSince}` : 'GrabBasket member'}</Text>
              </View>
            </Surface>

            <Surface>
              <View style={styles.summaryRow}>
                <View style={styles.summaryItem}>
                  <Text style={styles.summaryValue}>{Array.isArray(orderHistory) ? orderHistory.length : 0}</Text>
                  <Text style={styles.summaryLabel}>Orders</Text>
                </View>
                <View style={styles.summaryDivider} />
                <View style={styles.summaryItem}>
                  <Text style={styles.summaryValue}>{addressCount}</Text>
                  <Text style={styles.summaryLabel}>Saved addresses</Text>
                </View>
              </View>
              <Text style={styles.smallMutedCenter}>{highlightedAddress}</Text>
            </Surface>

            {notice ? <InlineNoticeCard title="Updated" message={notice} onDismiss={() => setNotice('')} /> : null}
            {inlineErrors.orders ? <InlineErrorCard title="Orders issue" message={inlineErrors.orders} /> : null}
            {inlineErrors.addresses ? <InlineErrorCard title="Address issue" message={inlineErrors.addresses} /> : null}

            <View style={styles.grid}>
              {ACCOUNT_SHORTCUTS.map((item) => (
                <ShortcutTile key={item.key} item={item} onPress={() => handleShortcut(item.key)} />
              ))}
            </View>

            <Surface style={{ padding: 0, overflow: 'hidden' }}>
              {ACCOUNT_LINKS.map((item, index) => (
                <LinkRow
                  key={item.key}
                  icon={item.icon}
                  label={item.label}
                  onPress={() => setNotice(handleLinkNotice(item.key))}
                  isLast={index === ACCOUNT_LINKS.length - 1}
                />
              ))}
            </Surface>

            <View style={{ gap: 12 }}>
              <View style={styles.rowBetween}>
                <Text style={styles.listTitle}>Past orders</Text>
                <Text style={styles.smallMuted}>Tap any order for live handoff details</Text>
              </View>

              <View style={styles.segmentRow}>
                {['food', 'warehouse'].map((service) => {
                  const active = pastOrderTab === service;
                  return (
                    <TouchableOpacity
                      key={service}
                      activeOpacity={0.94}
                      style={[styles.segment, active && styles.segmentActive]}
                      onPress={() => setPastOrderTab(service)}>
                      <Text style={[styles.segmentText, active && styles.segmentTextActive]}>
                        {service === 'food' ? 'Food' : 'Warehouse'}
                      </Text>
                    </TouchableOpacity>
                  );
                })}
              </View>

              {ordersLoading ? (
                <Surface style={styles.centerCard}>
                  <ActivityIndicator color={BrandPalette.primary} />
                </Surface>
              ) : filteredOrders.length ? (
                <View style={{ gap: 12 }}>
                  {filteredOrders.slice(0, 8).map((order) => (
                    <OrderCard key={order.id} order={order} onOpen={() => setSelectedOrderId(order.id)} onPrimary={() => runOrderAction(order)} />
                  ))}
                </View>
              ) : (
                <Surface style={styles.centerCard}>
                  <Text style={styles.helper}>
                    {pastOrderTab === 'warehouse'
                      ? 'Your warehouse order history will appear here.'
                      : 'Your food order history will appear here.'}
                  </Text>
                </Surface>
              )}
            </View>

            <Surface>
              <Text style={styles.smallMuted}>Device ID: {deviceId || 'Unavailable'}</Text>
              <Text style={styles.smallMuted}>{versionText}</Text>
            </Surface>

            <TouchableOpacity
              activeOpacity={0.95}
              style={styles.primaryButton}
              onPress={async () => {
                await logout();
                setNotice('You have been signed out.');
              }}>
              <Text style={styles.primaryButtonText}>Logout</Text>
            </TouchableOpacity>
          </View>
        )}
      </ScrollView>

      <OrderDetailsModal
        visible={Boolean(selectedOrder)}
        order={selectedOrder}
        events={selectedEvents}
        tracking={selectedTracking}
        trackingLoading={trackingLoading}
        refreshing={detailsRefreshing}
        onRefresh={refreshSelectedOrder}
        onClose={() => setSelectedOrderId(null)}
        onPrimary={() => runOrderAction(selectedOrder)}
      />
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: { flex: 1, backgroundColor: BrandPalette.background },
  page: { paddingHorizontal: 16, paddingTop: 14, gap: 16 },
  hero: { paddingHorizontal: 20, paddingTop: 18, paddingBottom: 14 },
  eyebrow: { fontSize: 12, letterSpacing: 1.4, fontWeight: '800', color: BrandPalette.primary, textTransform: 'uppercase' },
  heroTitle: { marginTop: 12, fontSize: 30, lineHeight: 37, fontWeight: '800', color: BrandPalette.text },
  heroCopy: { marginTop: 10, fontSize: 15, lineHeight: 22, color: BrandPalette.textMuted },
  surface: {
    marginHorizontal: 0,
    borderRadius: 24,
    padding: 16,
    backgroundColor: BrandPalette.surface,
    borderWidth: 1,
    borderColor: BrandPalette.border,
    gap: 12,
    ...createShadow(0.08, 12, 6),
  },
  center: { flex: 1, alignItems: 'center', justifyContent: 'center', gap: 10 },
  centerText: { color: BrandPalette.textMuted, fontSize: 14 },
  centerCard: { alignItems: 'center', justifyContent: 'center', paddingVertical: 24 },
  input: {
    height: 52,
    borderRadius: 16,
    borderWidth: 1,
    borderColor: BrandPalette.border,
    backgroundColor: BrandPalette.white,
    paddingHorizontal: 14,
    fontSize: 15,
    color: BrandPalette.text,
  },
  authTabs: { flexDirection: 'row', backgroundColor: BrandPalette.backgroundAlt, borderRadius: 16, padding: 4 },
  authTab: { flex: 1, borderRadius: 12, paddingVertical: 12, alignItems: 'center' },
  authTabActive: { backgroundColor: BrandPalette.white, ...createShadow(0.08, 8, 2) },
  authTabText: { fontSize: 14, fontWeight: '700', color: BrandPalette.textMuted },
  authTabTextActive: { color: BrandPalette.text },
  helper: { fontSize: 12, lineHeight: 18, color: BrandPalette.textMuted },
  smallMuted: { fontSize: 12, lineHeight: 18, color: BrandPalette.textSubtle },
  smallMutedCenter: { fontSize: 12, lineHeight: 18, color: BrandPalette.textSubtle, textAlign: 'center' },
  primaryButton: { height: 52, borderRadius: 18, backgroundColor: BrandPalette.primary, alignItems: 'center', justifyContent: 'center' },
  primaryButtonText: { color: BrandPalette.white, fontSize: 15, fontWeight: '800' },
  rowBetween: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: 12 },
  avatar: { width: 58, height: 58, borderRadius: 29, alignItems: 'center', justifyContent: 'center', backgroundColor: BrandPalette.primarySoft },
  avatarText: { fontSize: 20, fontWeight: '800', color: BrandPalette.primary },
  helpChip: {
    paddingHorizontal: 14,
    paddingVertical: 9,
    borderRadius: 999,
    backgroundColor: BrandPalette.backgroundAlt,
    borderWidth: 1,
    borderColor: BrandPalette.border,
  },
  helpChipText: { fontSize: 13, fontWeight: '700', color: BrandPalette.text },
  profileTitle: { marginTop: 4, fontSize: 26, fontWeight: '800', color: BrandPalette.text },
  profileMeta: { marginTop: 2, fontSize: 14, color: BrandPalette.textMuted },
  memberRow: { marginTop: 8, flexDirection: 'row', alignItems: 'center', gap: 8 },
  memberText: { fontSize: 13, fontWeight: '600', color: BrandPalette.textMuted },
  summaryRow: { flexDirection: 'row', alignItems: 'center' },
  summaryItem: { flex: 1, alignItems: 'center' },
  summaryDivider: { width: 1, height: 34, backgroundColor: BrandPalette.border },
  summaryValue: { fontSize: 24, fontWeight: '800', color: BrandPalette.text },
  summaryLabel: { marginTop: 4, fontSize: 12, color: BrandPalette.textMuted },
  grid: { flexDirection: 'row', flexWrap: 'wrap', gap: 12 },
  shortcutTile: {
    width: '48.2%',
    borderRadius: 20,
    padding: 16,
    backgroundColor: BrandPalette.surface,
    borderWidth: 1,
    borderColor: BrandPalette.border,
    minHeight: 132,
    ...createShadow(0.06, 10, 5),
  },
  shortcutIconWrap: {
    width: 42,
    height: 42,
    borderRadius: 21,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: BrandPalette.backgroundAlt,
  },
  shortcutLabel: { marginTop: 14, fontSize: 15, fontWeight: '800', color: BrandPalette.text },
  shortcutSubtitle: { marginTop: 6, fontSize: 13, lineHeight: 19, color: BrandPalette.textMuted },
  linkRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', paddingHorizontal: 16, paddingVertical: 15 },
  linkLeft: { flexDirection: 'row', alignItems: 'center', gap: 12, flex: 1 },
  linkLabel: { fontSize: 14, fontWeight: '700', color: BrandPalette.text },
  linkBorder: { borderBottomWidth: StyleSheet.hairlineWidth, borderBottomColor: BrandPalette.border },
  listTitle: { fontSize: 20, fontWeight: '800', color: BrandPalette.text },
  segmentRow: { flexDirection: 'row', gap: 10 },
  segment: {
    flex: 1,
    height: 42,
    borderRadius: 14,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: BrandPalette.surface,
    borderWidth: 1,
    borderColor: BrandPalette.border,
  },
  segmentActive: { backgroundColor: BrandPalette.primarySoft, borderColor: BrandPalette.primary },
  segmentText: { fontSize: 13, fontWeight: '700', color: BrandPalette.textMuted },
  segmentTextActive: { color: BrandPalette.primary },
  orderCard: {
    borderRadius: 22,
    padding: 16,
    backgroundColor: BrandPalette.surface,
    borderWidth: 1,
    borderColor: BrandPalette.border,
    gap: 12,
    ...createShadow(0.06, 10, 5),
  },
  orderTopRow: { flexDirection: 'row', justifyContent: 'space-between', gap: 12 },
  orderIdentity: { flexDirection: 'row', gap: 12, flex: 1 },
  orderImage: { width: 54, height: 54, borderRadius: 16, backgroundColor: BrandPalette.backgroundAlt },
  orderFallback: {
    width: 54,
    height: 54,
    borderRadius: 16,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: BrandPalette.primarySoft,
  },
  orderFallbackText: { fontSize: 18, fontWeight: '800', color: BrandPalette.primary },
  orderVendor: { fontSize: 16, fontWeight: '800', color: BrandPalette.text },
  orderMeta: { marginTop: 5, fontSize: 13, lineHeight: 18, color: BrandPalette.textMuted },
  statusPill: { alignSelf: 'flex-start', paddingHorizontal: 12, paddingVertical: 8, borderRadius: 999 },
  statusPillText: { fontSize: 12, fontWeight: '800' },
  liveChip: {
    alignSelf: 'flex-start',
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    borderRadius: 999,
    backgroundColor: BrandPalette.successSoft,
    paddingHorizontal: 10,
    paddingVertical: 6,
  },
  liveChipText: { fontSize: 12, fontWeight: '700', color: BrandPalette.success },
  secondaryButton: {
    height: 44,
    borderRadius: 14,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: BrandPalette.backgroundAlt,
    borderWidth: 1,
    borderColor: BrandPalette.border,
  },
  secondaryButtonText: { fontSize: 13, fontWeight: '800', color: BrandPalette.text },
  footnote: { fontSize: 12, lineHeight: 18, color: BrandPalette.textSubtle },
  modalBackdrop: { flex: 1, backgroundColor: 'rgba(20,18,16,0.34)', justifyContent: 'flex-end' },
  sheet: { maxHeight: '92%', backgroundColor: BrandPalette.background, borderTopLeftRadius: 28, borderTopRightRadius: 28, paddingTop: 10 },
  handle: { alignSelf: 'center', width: 52, height: 5, borderRadius: 999, backgroundColor: BrandPalette.border, marginBottom: 12 },
  sheetHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 12,
    paddingHorizontal: 16,
    paddingBottom: 12,
  },
  sheetTitle: { fontSize: 21, fontWeight: '800', color: BrandPalette.text },
  sheetSubtitle: { marginTop: 4, fontSize: 13, color: BrandPalette.textMuted },
  iconButton: {
    width: 38,
    height: 38,
    borderRadius: 19,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: BrandPalette.surface,
    borderWidth: 1,
    borderColor: BrandPalette.border,
  },
  refreshChip: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    paddingHorizontal: 12,
    paddingVertical: 8,
    borderRadius: 999,
    backgroundColor: BrandPalette.backgroundAlt,
    borderWidth: 1,
    borderColor: BrandPalette.border,
  },
  refreshChipText: { fontSize: 12, fontWeight: '700', color: BrandPalette.primary },
  blockTitle: { fontSize: 20, fontWeight: '800', color: BrandPalette.text },
  blockSubtitle: { fontSize: 13, lineHeight: 19, color: BrandPalette.textMuted },
  stepTop: { flexDirection: 'row', alignItems: 'center' },
  stepDot: { width: 12, height: 12, borderRadius: 6 },
  stepLine: { flex: 1, height: 2, marginLeft: 8, borderRadius: 999 },
  stepTitle: { fontSize: 13, fontWeight: '800' },
  sectionTitle: { fontSize: 16, fontWeight: '800', color: BrandPalette.text },
  stateCard: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: 12,
    borderRadius: 18,
    padding: 14,
    backgroundColor: BrandPalette.backgroundAlt,
    borderWidth: 1,
    borderColor: BrandPalette.border,
  },
  stateIcon: { width: 40, height: 40, borderRadius: 20, alignItems: 'center', justifyContent: 'center' },
  stateTitle: { fontSize: 14, fontWeight: '800', color: BrandPalette.text },
  stateSubtitle: { marginTop: 4, fontSize: 13, lineHeight: 19, color: BrandPalette.textMuted },
  inlineRow: { flexDirection: 'row', alignItems: 'center', gap: 10 },
  inlineText: { fontSize: 13, color: BrandPalette.textMuted },
  metaRow: { flexDirection: 'row', alignItems: 'flex-start', gap: 12, paddingVertical: 8 },
  metaLabel: {
    fontSize: 12,
    fontWeight: '700',
    color: BrandPalette.textSubtle,
    textTransform: 'uppercase',
    letterSpacing: 0.4,
  },
  metaValue: { marginTop: 3, fontSize: 14, lineHeight: 20, color: BrandPalette.text },
  metaValueMuted: { color: BrandPalette.textMuted },
  emptyTimeline: {
    borderRadius: 16,
    padding: 14,
    backgroundColor: BrandPalette.backgroundAlt,
    borderWidth: 1,
    borderColor: BrandPalette.border,
  },
  emptyTitle: { fontSize: 13, fontWeight: '800', color: BrandPalette.text },
  emptySubtitle: { marginTop: 4, fontSize: 12, lineHeight: 18, color: BrandPalette.textMuted },
  timelineRow: { flexDirection: 'row', alignItems: 'flex-start', gap: 12, minHeight: 62 },
  timelineRail: { width: 18, alignItems: 'center' },
  timelineDot: { width: 10, height: 10, borderRadius: 5, marginTop: 5 },
  timelineLine: { width: 2, flex: 1, marginTop: 6, backgroundColor: BrandPalette.border },
  timelineTitle: { fontSize: 13, fontWeight: '800', color: BrandPalette.text },
  timelineMeta: { marginTop: 2, fontSize: 12, color: BrandPalette.textMuted },
  timelineNote: { marginTop: 4, fontSize: 12, lineHeight: 18, color: BrandPalette.text },
});