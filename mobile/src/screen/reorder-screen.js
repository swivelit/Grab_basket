import React, { useMemo, useState } from 'react';
import {
  ActivityIndicator,
  Image,
  Linking,
  RefreshControl,
  ScrollView,
  StatusBar,
  StyleSheet,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { useBottomTabBarHeight } from '@react-navigation/bottom-tabs';
import { useRouter } from 'expo-router';
import { SafeAreaView } from 'react-native-safe-area-context';

import InlineErrorCard from '../components/inline-error-card';
import { MAX_ORDERS } from '../domains/grab-basket-utils';
import { useGrabBasket } from '../providers/grab-basket-provider';

const COLORS = {
  bg: '#FFF9F3',
  card: '#FFFFFF',
  cardAlt: '#FFF6EC',
  cardMuted: '#FFF2E6',
  text: '#2F241C',
  muted: '#756354',
  subtle: '#A18C7B',
  border: '#F2DDC7',
  line: '#F4E6D7',
  peach50: '#FFF7EE',
  peach100: '#FFF1E1',
  peach300: '#F4BC92',
  peach600: '#D97651',
  success: '#2E8B57',
  successSoft: '#EAF7EF',
  yellow: '#D4A017',
  yellowSoft: '#FFF6D6',
  blue: '#2667FF',
  blueSoft: '#E9F0FF',
  danger: '#D45454',
  dangerSoft: '#FCE9E9',
  black: '#2B211A',
};

const TRACKING_STEPS = [
  { key: 'created', label: 'Placed' },
  { key: 'accepted', label: 'Accepted' },
  { key: 'partner', label: 'Rider' },
  { key: 'pickup', label: 'Picked up' },
  { key: 'delivered', label: 'Delivered' },
];

const REVIEW_TAGS = [
  'Well packed',
  'On time',
  'Fresh food',
  'Good value',
  'Polite rider',
  'Needs improvement',
];

function money(value) {
  return `₹${Number(value || 0).toFixed(0)}`;
}

function initials(name = '') {
  return String(name || '')
    .split(' ')
    .filter(Boolean)
    .map((part) => part[0])
    .join('')
    .slice(0, 2)
    .toUpperCase();
}

function normalizeStatus(status = '') {
  return String(status || '').trim().toUpperCase();
}

function prettifyStatus(status = '') {
  return String(status || '')
    .replace(/_/g, ' ')
    .trim()
    .toLowerCase()
    .replace(/\b\w/g, (character) => character.toUpperCase());
}

function isActiveOrder(order) {
  const status = normalizeStatus(order?.status);
  return Boolean(
    order?.id &&
      status &&
      ![
        'DELIVERED',
        'CANCELLED_BY_CUSTOMER',
        'CANCELLED_BY_SELLER',
        'REJECTED_BY_SELLER',
        'FAILED',
      ].includes(status)
  );
}

function isDeliveredOrder(order) {
  return normalizeStatus(order?.status) === 'DELIVERED';
}

function getStatusTone(status = '') {
  const normalized = normalizeStatus(status);

  if (normalized === 'DELIVERED') {
    return { bg: COLORS.successSoft, text: COLORS.success, icon: 'checkmark-circle' };
  }

  if (normalized.startsWith('CANCELLED') || normalized.startsWith('REJECTED') || normalized === 'FAILED') {
    return { bg: COLORS.dangerSoft, text: COLORS.danger, icon: 'close-circle' };
  }

  if (normalized === 'READY_FOR_PICKUP' || normalized === 'PICKED_UP') {
    return { bg: COLORS.blueSoft, text: COLORS.blue, icon: 'navigate-circle' };
  }

  return { bg: COLORS.yellowSoft, text: COLORS.yellow, icon: 'time' };
}

function getTrackingStepIndex(status = '') {
  const normalized = normalizeStatus(status);

  if (normalized === 'PAYMENT_PENDING') return 0;
  if (normalized === 'CREATED') return 0;
  if (normalized === 'ACCEPTED_BY_SELLER') return 1;
  if (normalized === 'ASSIGNED_TO_PARTNER') return 2;
  if (normalized === 'READY_FOR_PICKUP') return 2;
  if (normalized === 'PICKED_UP') return 3;
  if (normalized === 'DELIVERED') return 4;
  if (normalized.startsWith('CANCELLED') || normalized.startsWith('REJECTED') || normalized === 'FAILED') return 1;
  return 0;
}

function getTrackingLabel(order) {
  const status = normalizeStatus(order?.status);

  if (status === 'PAYMENT_PENDING') return 'Waiting for payment confirmation';
  if (status === 'CREATED') return 'Store received your order';
  if (status === 'ACCEPTED_BY_SELLER') return 'Merchant is preparing it';
  if (status === 'ASSIGNED_TO_PARTNER') return 'Rider has been assigned';
  if (status === 'READY_FOR_PICKUP') return 'Packed and ready for pickup';
  if (status === 'PICKED_UP') return 'On the way to you';
  if (status === 'DELIVERED') return 'Delivered successfully';
  if (status.startsWith('CANCELLED')) return 'Order was cancelled';
  if (status.startsWith('REJECTED')) return 'Order was rejected';
  return prettifyStatus(order?.status || 'Created');
}

function formatDateTime(value) {
  if (!value) return 'Just now';

  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return 'Just now';

  return new Intl.DateTimeFormat('en-IN', {
    day: 'numeric',
    month: 'short',
    hour: 'numeric',
    minute: '2-digit',
  }).format(date);
}

function getEtaCopy(order, vendor) {
  const status = normalizeStatus(order?.status);
  const eta = Number(order?.delivery_eta_minutes || vendor?.estimated_delivery_time_min || 0);

  if (status === 'DELIVERED') return 'Delivered';
  if (status.startsWith('CANCELLED') || status.startsWith('REJECTED')) return 'Closed';
  if (status === 'PAYMENT_PENDING') return 'Verifying payment';
  if (status === 'PICKED_UP' && eta > 0) return `${eta} mins left`;
  if (status === 'READY_FOR_PICKUP') return 'Pickup starting';
  if (status === 'ASSIGNED_TO_PARTNER') return 'Rider en route';
  if (eta > 0) return `${eta} mins ETA`;
  return 'Tracking live';
}

function getVendorImage(vendor, order) {
  return (
    vendor?.cover_image_url ||
    vendor?.banner_image_url ||
    vendor?.logo_image_url ||
    order?.vendor_image_url ||
    ''
  );
}

function getCuisineTags(vendor) {
  const raw = String(vendor?.cuisine_tags || '')
    .split(',')
    .map((item) => item.trim())
    .filter(Boolean);

  if (raw.length) return raw.slice(0, 3);

  const fallback = [];
  if (vendor?.price_bucket) fallback.push(vendor.price_bucket);
  if (vendor?.delivery_radius_km) fallback.push(`${Number(vendor.delivery_radius_km).toFixed(0)} km range`);
  if (vendor?.min_order_amount) fallback.push(`Min ${money(vendor.min_order_amount)}`);
  return fallback.slice(0, 3);
}

function getRatingLabel(vendor) {
  const rating = Number(vendor?.avg_rating || 0);
  const total = Number(vendor?.total_ratings || 0);

  if (rating > 0) {
    return `${rating.toFixed(1)}${total > 0 ? ` (${total})` : ''}`;
  }

  return 'New';
}

function getTrustCopy(vendor) {
  if (vendor?.is_busy) return 'High demand right now';
  if (vendor?.open_now === false) return 'Currently closed';
  if (vendor?.accepts_cod === false) return 'Online payments preferred';
  if (vendor?.can_deliver === false) return 'Outside delivery range';
  if (vendor?.support_phone || vendor?.support_email) return 'Support contacts available';
  return 'Reliable merchant profile';
}

function buildVendorLookup(vendors = []) {
  const byId = new Map();

  (Array.isArray(vendors) ? vendors : []).forEach((vendor) => {
    if (!vendor?.id) return;
    byId.set(String(vendor.id), vendor);
  });

  return byId;
}

function buildOfferCards({ activeOrders = [], deliveredOrders = [], recentVendors = [] } = {}) {
  const offers = [];

  if (activeOrders.length) {
    offers.push({
      id: 'trust-order',
      title: 'TRACKSAFE',
      subtitle: 'Keep your next order transparent',
      body: 'Push tracking, order updates, and post-order help in one place builds more confidence.',
      tone: 'blue',
    });
  }

  if (deliveredOrders.length >= 1) {
    offers.push({
      id: 'bounce-back',
      title: 'TRUST20',
      subtitle: 'Win-back for your next order',
      body: 'Give a rating after delivery and reward the customer with a clear bounce-back offer.',
      tone: 'peach',
    });
  }

  if (recentVendors.length >= 2) {
    offers.push({
      id: 'merchant-loyalty',
      title: 'SAVE60',
      subtitle: 'Merchant loyalty nudge',
      body: 'Use order history plus merchant quality signals to surface smarter, more trusted offers.',
      tone: 'yellow',
    });
  }

  if (!offers.length) {
    offers.push({
      id: 'welcome-back',
      title: 'WELCOME10',
      subtitle: 'First trust-building campaign',
      body: 'A simple coupon strip is an easy first step before you wire a full offers engine.',
      tone: 'blue',
    });
  }

  return offers.slice(0, 3);
}

function SummaryStat({ label, value, icon }) {
  return (
    <View style={styles.summaryStat}>
      <View style={styles.summaryStatIcon}>
        <Ionicons name={icon} size={18} color={COLORS.peach600} />
      </View>
      <Text style={styles.summaryStatValue}>{value}</Text>
      <Text style={styles.summaryStatLabel}>{label}</Text>
    </View>
  );
}

function TrackingSteps({ order }) {
  const currentIndex = getTrackingStepIndex(order?.status);
  const status = normalizeStatus(order?.status);
  const isClosed = status.startsWith('CANCELLED') || status.startsWith('REJECTED') || status === 'FAILED';

  return (
    <View style={styles.stepsWrap}>
      {TRACKING_STEPS.map((step, index) => {
        const complete = !isClosed && index <= currentIndex;
        const current = !isClosed && index === currentIndex;

        return (
          <React.Fragment key={step.key}>
            <View style={styles.stepItem}>
              <View
                style={[
                  styles.stepDot,
                  complete && styles.stepDotComplete,
                  current && styles.stepDotCurrent,
                  isClosed && index > 1 && styles.stepDotClosed,
                ]}
              />
              <Text style={[styles.stepLabel, complete && styles.stepLabelComplete]} numberOfLines={1}>
                {step.label}
              </Text>
            </View>
            {index < TRACKING_STEPS.length - 1 ? (
              <View style={[styles.stepLine, complete && styles.stepLineComplete]} />
            ) : null}
          </React.Fragment>
        );
      })}
    </View>
  );
}

function QuickAction({ label, icon, onPress, primary = false }) {
  return (
    <TouchableOpacity
      activeOpacity={0.92}
      style={[styles.quickAction, primary && styles.quickActionPrimary]}
      onPress={onPress}>
      <Ionicons name={icon} size={16} color={primary ? '#FFFFFF' : COLORS.peach600} />
      <Text style={[styles.quickActionText, primary && styles.quickActionTextPrimary]}>{label}</Text>
    </TouchableOpacity>
  );
}

function SpotlightOrderCard({ order, vendor, onOpenTracking, onOpenStore, onHelp }) {
  if (!order) return null;

  const tone = getStatusTone(order?.status);
  const imageUri = getVendorImage(vendor, order);

  return (
    <View style={styles.spotlightCard}>
      <View style={styles.spotlightHeader}>
        <View style={{ flex: 1 }}>
          <Text style={styles.spotlightEyebrow}>Live order</Text>
          <Text style={styles.spotlightTitle} numberOfLines={1}>
            {order?.vendor_name || vendor?.name || 'Merchant'}
          </Text>
          <Text style={styles.spotlightSubtitle}>
            #{order?.id} · {getTrackingLabel(order)}
          </Text>
        </View>

        <View style={[styles.statusPill, { backgroundColor: tone.bg }]}>
          <Ionicons name={tone.icon} size={14} color={tone.text} />
          <Text style={[styles.statusPillText, { color: tone.text }]}>{prettifyStatus(order?.status || 'Created')}</Text>
        </View>
      </View>

      <View style={styles.spotlightBody}>
        {imageUri ? (
          <Image source={{ uri: imageUri }} style={styles.spotlightImage} resizeMode="cover" />
        ) : (
          <View style={styles.spotlightMonogram}>
            <Text style={styles.spotlightMonogramText}>{initials(order?.vendor_name || vendor?.name)}</Text>
          </View>
        )}

        <View style={{ flex: 1 }}>
          <View style={styles.spotlightInfoRow}>
            <Ionicons name="time-outline" size={15} color={COLORS.peach600} />
            <Text style={styles.spotlightInfoText}>{getEtaCopy(order, vendor)}</Text>
          </View>
          <View style={styles.spotlightInfoRow}>
            <Ionicons name="receipt-outline" size={15} color={COLORS.peach600} />
            <Text style={styles.spotlightInfoText}>
              {Number(order?.item_count || 0)} items · {money(order?.total_amount || order?.total || 0)}
            </Text>
          </View>
          <View style={styles.spotlightInfoRow}>
            <Ionicons name="shield-checkmark-outline" size={15} color={COLORS.peach600} />
            <Text style={styles.spotlightInfoText}>{getTrustCopy(vendor)}</Text>
          </View>
        </View>
      </View>

      <TrackingSteps order={order} />

      <View style={styles.quickActionsRow}>
        <QuickAction label="Open tracking" icon="navigate-outline" onPress={onOpenTracking} primary />
        <QuickAction label="Need help" icon="help-circle-outline" onPress={onHelp} />
        <QuickAction label="Open store" icon="storefront-outline" onPress={onOpenStore} />
      </View>
    </View>
  );
}

function SupportCard({ order, vendor, onOpenTracking, onOpenHelp, onOpenPhone, onOpenEmail }) {
  const paymentStatus = String(order?.payment_status || '').trim().toUpperCase();
  const status = normalizeStatus(order?.status);

  let refundText = 'Support and refund help should be one tap away after checkout.';
  if (status.startsWith('CANCELLED') && paymentStatus === 'PAID') {
    refundText = 'Paid cancelled order — highlight refund status and reassure the customer proactively.';
  } else if (paymentStatus === 'FAILED') {
    refundText = 'Payment failed — reassure the customer that no money was captured.';
  } else if (status === 'DELIVERED') {
    refundText = 'Delivered order — give clear help, issue reporting, and review prompts.';
  }

  return (
    <View style={styles.sectionCard}>
      <View style={styles.sectionHeaderRow}>
        <View style={{ flex: 1 }}>
          <Text style={styles.sectionTitle}>Help, support & refund</Text>
          <Text style={styles.sectionSubtitle}>Trust is strongest when help feels immediate and transparent.</Text>
        </View>
        <View style={styles.sectionIconWrap}>
          <Ionicons name="shield-checkmark-outline" size={18} color={COLORS.peach600} />
        </View>
      </View>

      <View style={styles.supportBanner}>
        <Text style={styles.supportBannerTitle}>Order #{order?.id}</Text>
        <Text style={styles.supportBannerText}>{refundText}</Text>
      </View>

      <View style={styles.supportList}>
        <View style={styles.supportItem}>
          <Ionicons name="navigate-outline" size={16} color={COLORS.peach600} />
          <Text style={styles.supportItemText}>Live tracking and updates</Text>
          <TouchableOpacity activeOpacity={0.92} onPress={onOpenTracking}>
            <Text style={styles.supportLink}>Open</Text>
          </TouchableOpacity>
        </View>

        <View style={styles.supportItem}>
          <Ionicons name="chatbubble-ellipses-outline" size={16} color={COLORS.peach600} />
          <Text style={styles.supportItemText}>Order help and issue reporting</Text>
          <TouchableOpacity activeOpacity={0.92} onPress={onOpenHelp}>
            <Text style={styles.supportLink}>View</Text>
          </TouchableOpacity>
        </View>

        <View style={styles.supportItem}>
          <Ionicons name="call-outline" size={16} color={COLORS.peach600} />
          <Text style={styles.supportItemText}>{vendor?.support_phone || 'Merchant support phone'}</Text>
          <TouchableOpacity activeOpacity={0.92} onPress={onOpenPhone}>
            <Text style={styles.supportLink}>{vendor?.support_phone ? 'Call' : 'Help'}</Text>
          </TouchableOpacity>
        </View>

        <View style={styles.supportItem}>
          <Ionicons name="mail-outline" size={16} color={COLORS.peach600} />
          <Text style={styles.supportItemText}>{vendor?.support_email || 'Merchant support email'}</Text>
          <TouchableOpacity activeOpacity={0.92} onPress={onOpenEmail}>
            <Text style={styles.supportLink}>{vendor?.support_email ? 'Email' : 'Help'}</Text>
          </TouchableOpacity>
        </View>
      </View>
    </View>
  );
}

function ReviewComposer({ order, rating, note, tags, onSetRating, onSetNote, onToggleTag, onSubmit, submitted }) {
  return (
    <View style={styles.reviewCard}>
      <View style={styles.reviewHeader}>
        <View style={styles.reviewIcon}>
          <Ionicons name="star-outline" size={17} color={COLORS.peach600} />
        </View>
        <View style={{ flex: 1 }}>
          <Text style={styles.reviewTitle}>{order?.vendor_name || 'Recent order'}</Text>
          <Text style={styles.reviewSubtitle}>#{order?.id} · delivered {formatDateTime(order?.updated_at || order?.created_at)}</Text>
        </View>
      </View>

      <View style={styles.starsRow}>
        {[1, 2, 3, 4, 5].map((value) => {
          const active = Number(rating || 0) >= value;
          return (
            <TouchableOpacity key={value} activeOpacity={0.92} onPress={() => onSetRating(value)}>
              <Ionicons name={active ? 'star' : 'star-outline'} size={26} color={active ? COLORS.peach600 : COLORS.subtle} />
            </TouchableOpacity>
          );
        })}
      </View>

      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.reviewTagsRow}>
        {REVIEW_TAGS.map((tag) => {
          const active = tags.includes(tag);
          return (
            <TouchableOpacity
              key={tag}
              activeOpacity={0.92}
              style={[styles.reviewTag, active && styles.reviewTagActive]}
              onPress={() => onToggleTag(tag)}>
              <Text style={[styles.reviewTagText, active && styles.reviewTagTextActive]}>{tag}</Text>
            </TouchableOpacity>
          );
        })}
      </ScrollView>

      <TextInput
        value={note}
        onChangeText={onSetNote}
        placeholder="Tell us what built trust or what broke it"
        placeholderTextColor={COLORS.subtle}
        multiline
        style={styles.reviewInput}
      />

      <TouchableOpacity activeOpacity={0.92} style={styles.reviewButton} onPress={onSubmit}>
        <Ionicons name={submitted ? 'checkmark-circle' : 'paper-plane-outline'} size={16} color="#FFFFFF" />
        <Text style={styles.reviewButtonText}>{submitted ? 'Review saved' : 'Submit review'}</Text>
      </TouchableOpacity>
    </View>
  );
}

function OfferCard({ item }) {
  const tone =
    item?.tone === 'yellow'
      ? { bg: COLORS.yellowSoft, iconBg: '#FFE59A' }
      : item?.tone === 'peach'
        ? { bg: COLORS.peach50, iconBg: '#FFD9BF' }
        : { bg: COLORS.blueSoft, iconBg: '#D7E4FF' };

  return (
    <View style={[styles.offerCard, { backgroundColor: tone.bg }]}>
      <View style={[styles.offerCodeBadge, { backgroundColor: tone.iconBg }]}>
        <Ionicons name="ticket-outline" size={17} color={COLORS.black} />
      </View>
      <Text style={styles.offerCode}>{item?.title}</Text>
      <Text style={styles.offerTitle}>{item?.subtitle}</Text>
      <Text style={styles.offerBody}>{item?.body}</Text>
    </View>
  );
}

function MerchantCard({ vendor, favorite, onToggleFavorite, onOpen }) {
  const imageUri = getVendorImage(vendor);
  const tags = getCuisineTags(vendor);

  return (
    <TouchableOpacity activeOpacity={0.94} style={styles.merchantCard} onPress={onOpen}>
      <View style={styles.merchantVisual}>
        {imageUri ? (
          <Image source={{ uri: imageUri }} style={styles.merchantImage} resizeMode="cover" />
        ) : (
          <View style={styles.merchantFallback}>
            <Text style={styles.merchantFallbackText}>{initials(vendor?.name)}</Text>
          </View>
        )}

        <TouchableOpacity activeOpacity={0.92} style={styles.favoriteButton} onPress={onToggleFavorite}>
          <Ionicons name={favorite ? 'heart' : 'heart-outline'} size={16} color={favorite ? COLORS.danger : '#FFFFFF'} />
        </TouchableOpacity>

        <View style={styles.merchantRatingPill}>
          <Ionicons name="star" size={12} color={COLORS.yellow} />
          <Text style={styles.merchantRatingPillText}>{getRatingLabel(vendor)}</Text>
        </View>
      </View>

      <View style={styles.merchantBody}>
        <Text style={styles.merchantName} numberOfLines={1}>{vendor?.name || 'Merchant'}</Text>
        <Text style={styles.merchantMeta} numberOfLines={1}>
          {vendor?.price_bucket || '₹₹'} · {vendor?.estimated_delivery_time_min ? `${vendor.estimated_delivery_time_min} mins` : 'Fast delivery'}
        </Text>
        <Text style={styles.merchantDescription} numberOfLines={2}>
          {vendor?.description || vendor?.address || 'A stronger merchant card with more metadata builds confidence faster.'}
        </Text>

        <View style={styles.merchantTagsWrap}>
          {tags.map((tag) => (
            <View key={`${vendor?.id}-${tag}`} style={styles.merchantTag}>
              <Text style={styles.merchantTagText}>{tag}</Text>
            </View>
          ))}
        </View>

        <View style={styles.merchantFooter}>
          <Text style={styles.merchantTrust}>{getTrustCopy(vendor)}</Text>
          <Text style={styles.merchantAction}>Open</Text>
        </View>
      </View>
    </TouchableOpacity>
  );
}

function OrderCard({ order, vendor, onOpenTracking, onOpenStore }) {
  const tone = getStatusTone(order?.status);
  const imageUri = getVendorImage(vendor, order);

  return (
    <View style={styles.orderCard}>
      <View style={styles.orderCardHeader}>
        {imageUri ? (
          <Image source={{ uri: imageUri }} style={styles.orderThumb} resizeMode="cover" />
        ) : (
          <View style={styles.orderThumbFallback}>
            <Text style={styles.orderThumbFallbackText}>{initials(order?.vendor_name || vendor?.name)}</Text>
          </View>
        )}

        <View style={{ flex: 1 }}>
          <Text style={styles.orderVendor}>{order?.vendor_name || vendor?.name || 'Store'}</Text>
          <Text style={styles.orderSubtitle}>
            #{order?.id} · {Number(order?.item_count || 0)} items · {money(order?.total_amount || order?.total || 0)}
          </Text>
          <Text style={styles.orderMetaLine}>{formatDateTime(order?.updated_at || order?.created_at)}</Text>
        </View>

        <View style={[styles.statusPill, { backgroundColor: tone.bg }]}>
          <Text style={[styles.statusPillText, { color: tone.text }]}>{prettifyStatus(order?.status || 'Created')}</Text>
        </View>
      </View>

      <View style={styles.orderTrustRow}>
        <Text style={styles.orderTrustText}>{getTrackingLabel(order)}</Text>
        <Text style={styles.orderTrustText}>{getEtaCopy(order, vendor)}</Text>
      </View>

      <TrackingSteps order={order} />

      <View style={styles.orderActionsRow}>
        <QuickAction label="Track" icon="navigate-outline" onPress={onOpenTracking} primary />
        <QuickAction label="Store" icon="storefront-outline" onPress={onOpenStore} />
      </View>
    </View>
  );
}

function EmptyState({ title, subtitle, buttonLabel, onPress }) {
  return (
    <View style={styles.emptyCard}>
      <Ionicons name="sparkles-outline" size={24} color={COLORS.peach600} />
      <Text style={styles.emptyTitle}>{title}</Text>
      <Text style={styles.emptySubtitle}>{subtitle}</Text>
      <TouchableOpacity activeOpacity={0.92} style={styles.emptyButton} onPress={onPress}>
        <Text style={styles.emptyButtonText}>{buttonLabel}</Text>
      </TouchableOpacity>
    </View>
  );
}

export function ReorderScreen() {
  const router = useRouter();
  const tabBarHeight = useBottomTabBarHeight();
  const {
    pastOrders,
    recentVendors,
    vendors,
    ordersLoading,
    inlineErrors,
    loadOrders,
    rememberStore,
    favorites,
    toggleFavorite,
  } = useGrabBasket();

  const [ratingsByOrder, setRatingsByOrder] = useState({});
  const [reviewDrafts, setReviewDrafts] = useState({});
  const [reviewTagsByOrder, setReviewTagsByOrder] = useState({});
  const [submittedReviews, setSubmittedReviews] = useState({});

  const visibleOrders = useMemo(() => (pastOrders || []).slice(0, MAX_ORDERS), [pastOrders]);
  const vendorLookup = useMemo(
    () => buildVendorLookup([...(Array.isArray(recentVendors) ? recentVendors : []), ...(Array.isArray(vendors) ? vendors : [])]),
    [recentVendors, vendors]
  );

  const activeOrders = useMemo(() => visibleOrders.filter(isActiveOrder), [visibleOrders]);
  const deliveredOrders = useMemo(() => visibleOrders.filter(isDeliveredOrder), [visibleOrders]);
  const spotlightOrder = activeOrders[0] || visibleOrders[0] || null;
  const spotlightVendor = spotlightOrder ? vendorLookup.get(String(spotlightOrder.vendor_id)) || null : null;

  const averageRating = useMemo(() => {
    const pool = (recentVendors || []).filter((vendor) => Number(vendor?.avg_rating || 0) > 0);
    if (!pool.length) return 'New';
    const value = pool.reduce((sum, vendor) => sum + Number(vendor?.avg_rating || 0), 0) / pool.length;
    return value.toFixed(1);
  }, [recentVendors]);

  const offers = useMemo(
    () => buildOfferCards({ activeOrders, deliveredOrders, recentVendors }),
    [activeOrders, deliveredOrders, recentVendors]
  );

  const merchantsToShow = useMemo(() => (recentVendors || []).slice(0, 6), [recentVendors]);
  const reviewableOrders = useMemo(() => deliveredOrders.slice(0, 2), [deliveredOrders]);

  const openTracking = () => {
    router.push('/(tabs)/account');
  };

  const openStore = (vendorId) => {
    if (!vendorId) {
      router.replace('/');
      return;
    }

    rememberStore(vendorId);
    router.push({
      pathname: '/store/[vendorId]',
      params: { vendorId: String(vendorId) },
    });
  };

  const openPhone = async (phone) => {
    const cleaned = String(phone || '').trim();
    if (!cleaned) {
      router.push('/(tabs)/account');
      return;
    }

    try {
      await Linking.openURL(`tel:${cleaned}`);
    } catch {
      router.push('/(tabs)/account');
    }
  };

  const openEmail = async (email) => {
    const cleaned = String(email || '').trim();
    if (!cleaned) {
      router.push('/(tabs)/account');
      return;
    }

    try {
      await Linking.openURL(`mailto:${cleaned}`);
    } catch {
      router.push('/(tabs)/account');
    }
  };

  const toggleReviewTag = (orderId, tag) => {
    setReviewTagsByOrder((current) => {
      const nextTags = Array.isArray(current[orderId]) ? current[orderId] : [];
      const hasTag = nextTags.includes(tag);
      return {
        ...current,
        [orderId]: hasTag ? nextTags.filter((item) => item !== tag) : [...nextTags, tag],
      };
    });
  };

  const submitReview = (orderId) => {
    setSubmittedReviews((current) => ({ ...current, [orderId]: true }));
  };

  return (
    <SafeAreaView style={styles.screen} edges={['top']}>
      <StatusBar barStyle="dark-content" />

      <ScrollView
        showsVerticalScrollIndicator={false}
        refreshControl={<RefreshControl refreshing={Boolean(ordersLoading)} onRefresh={() => loadOrders()} />}
        contentContainerStyle={{ padding: 20, paddingBottom: 28 + tabBarHeight }}>
        <Text style={styles.eyebrow}>Customer trust</Text>
        <Text style={styles.pageTitle}>Post-order confidence center</Text>
        <Text style={styles.pageSubtitle}>
          This one screen upgrades tracking, ratings, help, refunds, offers, and merchant credibility without waiting for a full redesign of the whole app.
        </Text>

        {inlineErrors?.orders ? (
          <View style={styles.sectionGap}>
            <InlineErrorCard title="Orders are stale" message={inlineErrors.orders} onRetry={() => loadOrders()} />
          </View>
        ) : null}

        <View style={styles.heroCard}>
          <View style={styles.heroHeader}>
            <View style={{ flex: 1 }}>
              <Text style={styles.heroEyebrow}>Trust snapshot</Text>
              <Text style={styles.heroTitle}>Make every order feel safer and clearer</Text>
            </View>
            <View style={styles.heroShield}>
              <Ionicons name="shield-checkmark" size={20} color={COLORS.peach600} />
            </View>
          </View>

          <View style={styles.summaryGrid}>
            <SummaryStat label="Live orders" value={String(activeOrders.length)} icon="pulse-outline" />
            <SummaryStat label="Delivered" value={String(deliveredOrders.length)} icon="checkmark-done-outline" />
            <SummaryStat label="Avg merchant rating" value={averageRating} icon="star-outline" />
          </View>
        </View>

        <View style={styles.sectionGap}>
          <Text style={styles.sectionTitle}>Order tracking spotlight</Text>
          <Text style={styles.sectionSubtitle}>Customers trust what they can see clearly.</Text>

          {spotlightOrder ? (
            <SpotlightOrderCard
              order={spotlightOrder}
              vendor={spotlightVendor}
              onOpenTracking={openTracking}
              onOpenStore={() => openStore(spotlightOrder?.vendor_id)}
              onHelp={() => router.push('/(tabs)/account')}
            />
          ) : (
            <EmptyState
              title="No recent orders yet"
              subtitle="After checkout, this area becomes the trust layer for tracking, help, and reassurance."
              buttonLabel="Browse stores"
              onPress={() => router.replace('/')}
            />
          )}
        </View>

        {spotlightOrder ? (
          <View style={styles.sectionGap}>
            <SupportCard
              order={spotlightOrder}
              vendor={spotlightVendor}
              onOpenTracking={openTracking}
              onOpenHelp={() => router.push('/(tabs)/account')}
              onOpenPhone={() => openPhone(spotlightVendor?.support_phone)}
              onOpenEmail={() => openEmail(spotlightVendor?.support_email)}
            />
          </View>
        ) : null}

        <View style={styles.sectionGap}>
          <Text style={styles.sectionTitle}>Ratings & reviews</Text>
          <Text style={styles.sectionSubtitle}>A lightweight review flow keeps the customer voice close to the order.</Text>

          {reviewableOrders.length ? (
            reviewableOrders.map((order) => {
              const orderId = String(order?.id);
              return (
                <ReviewComposer
                  key={`review-${orderId}`}
                  order={order}
                  rating={ratingsByOrder[orderId] || 0}
                  note={reviewDrafts[orderId] || ''}
                  tags={reviewTagsByOrder[orderId] || []}
                  submitted={Boolean(submittedReviews[orderId])}
                  onSetRating={(value) =>
                    setRatingsByOrder((current) => ({
                      ...current,
                      [orderId]: value,
                    }))
                  }
                  onSetNote={(value) =>
                    setReviewDrafts((current) => ({
                      ...current,
                      [orderId]: value,
                    }))
                  }
                  onToggleTag={(tag) => toggleReviewTag(orderId, tag)}
                  onSubmit={() => submitReview(orderId)}
                />
              );
            })
          ) : (
            <EmptyState
              title="No delivered orders to review"
              subtitle="As soon as an order is marked delivered, prompt for a fast review while context is still fresh."
              buttonLabel="View orders"
              onPress={openTracking}
            />
          )}
        </View>

        <View style={styles.sectionGap}>
          <Text style={styles.sectionTitle}>Offers & bounce-back</Text>
          <Text style={styles.sectionSubtitle}>Coupons work better when they feel contextual instead of random.</Text>

          <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.offersRow}>
            {offers.map((item) => (
              <OfferCard key={item.id} item={item} />
            ))}
          </ScrollView>
        </View>

        <View style={styles.sectionGap}>
          <Text style={styles.sectionTitle}>Recent merchants</Text>
          <Text style={styles.sectionSubtitle}>Real imagery, richer metadata, and visible support details improve trust immediately.</Text>

          {merchantsToShow.length ? (
            <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.merchantsRow}>
              {merchantsToShow.map((vendor) => (
                <MerchantCard
                  key={`vendor-${vendor?.id}`}
                  vendor={vendor}
                  favorite={Boolean(favorites[vendor?.id])}
                  onToggleFavorite={() => toggleFavorite(vendor?.id)}
                  onOpen={() => openStore(vendor?.id)}
                />
              ))}
            </ScrollView>
          ) : (
            <EmptyState
              title="No recent merchants yet"
              subtitle="Once customers browse and order more, use this rail for visually richer and more trustworthy merchant cards."
              buttonLabel="Browse home"
              onPress={() => router.replace('/')}
            />
          )}
        </View>

        <View style={styles.sectionGap}>
          <Text style={styles.sectionTitle}>Recent orders</Text>
          <Text style={styles.sectionSubtitle}>A clear post-order list helps customers feel in control.</Text>

          {ordersLoading && !visibleOrders.length ? (
            <View style={styles.loadingWrap}>
              <ActivityIndicator color={COLORS.peach600} />
            </View>
          ) : visibleOrders.length ? (
            visibleOrders.slice(0, 6).map((order) => {
              const vendor = vendorLookup.get(String(order?.vendor_id)) || null;
              return (
                <OrderCard
                  key={`order-${order?.id}`}
                  order={order}
                  vendor={vendor}
                  onOpenTracking={openTracking}
                  onOpenStore={() => openStore(order?.vendor_id)}
                />
              );
            })
          ) : (
            <EmptyState
              title="No recent orders yet"
              subtitle="Your recent orders will appear here for tracking, help, and repeat confidence."
              buttonLabel="Start exploring"
              onPress={() => router.replace('/')}
            />
          )}
        </View>
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  screen: {
    flex: 1,
    backgroundColor: COLORS.bg,
  },
  eyebrow: {
    color: COLORS.peach600,
    fontSize: 12,
    fontWeight: '800',
    letterSpacing: 1,
    textTransform: 'uppercase',
  },
  pageTitle: {
    marginTop: 8,
    color: COLORS.text,
    fontSize: 28,
    fontWeight: '900',
  },
  pageSubtitle: {
    marginTop: 8,
    color: COLORS.muted,
    fontSize: 14,
    lineHeight: 22,
  },
  sectionGap: {
    marginTop: 24,
    gap: 12,
  },
  heroCard: {
    marginTop: 24,
    backgroundColor: COLORS.card,
    borderRadius: 24,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 18,
    gap: 16,
  },
  heroHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  heroEyebrow: {
    color: COLORS.peach600,
    fontSize: 12,
    fontWeight: '800',
    letterSpacing: 0.9,
    textTransform: 'uppercase',
  },
  heroTitle: {
    marginTop: 4,
    color: COLORS.text,
    fontSize: 20,
    fontWeight: '900',
    lineHeight: 26,
  },
  heroShield: {
    width: 44,
    height: 44,
    borderRadius: 22,
    backgroundColor: COLORS.peach50,
    alignItems: 'center',
    justifyContent: 'center',
  },
  summaryGrid: {
    flexDirection: 'row',
    gap: 10,
  },
  summaryStat: {
    flex: 1,
    backgroundColor: COLORS.cardAlt,
    borderRadius: 20,
    borderWidth: 1,
    borderColor: COLORS.border,
    paddingVertical: 14,
    paddingHorizontal: 12,
    alignItems: 'center',
  },
  summaryStatIcon: {
    width: 34,
    height: 34,
    borderRadius: 17,
    backgroundColor: COLORS.peach50,
    alignItems: 'center',
    justifyContent: 'center',
  },
  summaryStatValue: {
    marginTop: 10,
    color: COLORS.text,
    fontSize: 18,
    fontWeight: '900',
  },
  summaryStatLabel: {
    marginTop: 4,
    color: COLORS.muted,
    fontSize: 12,
    textAlign: 'center',
  },
  sectionTitle: {
    color: COLORS.text,
    fontSize: 18,
    fontWeight: '900',
  },
  sectionSubtitle: {
    color: COLORS.muted,
    fontSize: 13,
    lineHeight: 20,
  },
  spotlightCard: {
    backgroundColor: COLORS.card,
    borderRadius: 24,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 16,
    gap: 16,
  },
  spotlightHeader: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: 12,
  },
  spotlightEyebrow: {
    color: COLORS.peach600,
    fontSize: 12,
    fontWeight: '800',
    letterSpacing: 0.8,
    textTransform: 'uppercase',
  },
  spotlightTitle: {
    marginTop: 4,
    color: COLORS.text,
    fontSize: 20,
    fontWeight: '900',
  },
  spotlightSubtitle: {
    marginTop: 4,
    color: COLORS.muted,
    fontSize: 13,
  },
  statusPill: {
    paddingHorizontal: 10,
    paddingVertical: 7,
    borderRadius: 999,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
  },
  statusPillText: {
    fontSize: 12,
    fontWeight: '800',
  },
  spotlightBody: {
    flexDirection: 'row',
    gap: 14,
  },
  spotlightImage: {
    width: 86,
    height: 86,
    borderRadius: 22,
    backgroundColor: COLORS.peach100,
  },
  spotlightMonogram: {
    width: 86,
    height: 86,
    borderRadius: 22,
    backgroundColor: COLORS.peach100,
    alignItems: 'center',
    justifyContent: 'center',
  },
  spotlightMonogramText: {
    color: COLORS.peach600,
    fontSize: 24,
    fontWeight: '900',
  },
  spotlightInfoRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    marginBottom: 10,
  },
  spotlightInfoText: {
    flex: 1,
    color: COLORS.text,
    fontSize: 13,
    lineHeight: 18,
  },
  stepsWrap: {
    flexDirection: 'row',
    alignItems: 'flex-start',
  },
  stepItem: {
    width: 58,
    alignItems: 'center',
    gap: 8,
  },
  stepDot: {
    width: 12,
    height: 12,
    borderRadius: 6,
    backgroundColor: '#E5D8CB',
  },
  stepDotComplete: {
    backgroundColor: COLORS.peach600,
  },
  stepDotCurrent: {
    width: 16,
    height: 16,
    borderRadius: 8,
    backgroundColor: COLORS.peach600,
  },
  stepDotClosed: {
    backgroundColor: '#E5D8CB',
  },
  stepLabel: {
    color: COLORS.subtle,
    fontSize: 11,
    textAlign: 'center',
  },
  stepLabelComplete: {
    color: COLORS.text,
    fontWeight: '700',
  },
  stepLine: {
    flex: 1,
    height: 2,
    backgroundColor: '#E9DDD1',
    marginTop: 5,
  },
  stepLineComplete: {
    backgroundColor: COLORS.peach600,
  },
  quickActionsRow: {
    flexDirection: 'row',
    gap: 10,
    flexWrap: 'wrap',
  },
  quickAction: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    backgroundColor: COLORS.peach50,
    borderRadius: 14,
    paddingHorizontal: 14,
    paddingVertical: 12,
  },
  quickActionPrimary: {
    backgroundColor: COLORS.black,
  },
  quickActionText: {
    color: COLORS.text,
    fontSize: 13,
    fontWeight: '800',
  },
  quickActionTextPrimary: {
    color: '#FFFFFF',
  },
  sectionCard: {
    backgroundColor: COLORS.card,
    borderRadius: 24,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 16,
    gap: 14,
  },
  sectionHeaderRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: 12,
  },
  sectionIconWrap: {
    width: 38,
    height: 38,
    borderRadius: 19,
    backgroundColor: COLORS.peach50,
    alignItems: 'center',
    justifyContent: 'center',
  },
  supportBanner: {
    backgroundColor: COLORS.cardAlt,
    borderRadius: 18,
    padding: 14,
    gap: 6,
  },
  supportBannerTitle: {
    color: COLORS.text,
    fontSize: 15,
    fontWeight: '800',
  },
  supportBannerText: {
    color: COLORS.muted,
    fontSize: 13,
    lineHeight: 20,
  },
  supportList: {
    gap: 12,
  },
  supportItem: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    backgroundColor: COLORS.peach50,
    borderRadius: 16,
    paddingHorizontal: 14,
    paddingVertical: 12,
  },
  supportItemText: {
    flex: 1,
    color: COLORS.text,
    fontSize: 13,
    lineHeight: 18,
  },
  supportLink: {
    color: COLORS.peach600,
    fontSize: 13,
    fontWeight: '800',
  },
  reviewCard: {
    backgroundColor: COLORS.card,
    borderRadius: 24,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 16,
    gap: 14,
  },
  reviewHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  reviewIcon: {
    width: 38,
    height: 38,
    borderRadius: 19,
    backgroundColor: COLORS.peach50,
    alignItems: 'center',
    justifyContent: 'center',
  },
  reviewTitle: {
    color: COLORS.text,
    fontSize: 15,
    fontWeight: '800',
  },
  reviewSubtitle: {
    marginTop: 2,
    color: COLORS.muted,
    fontSize: 12,
  },
  starsRow: {
    flexDirection: 'row',
    gap: 8,
  },
  reviewTagsRow: {
    gap: 8,
  },
  reviewTag: {
    backgroundColor: COLORS.peach50,
    borderRadius: 999,
    paddingHorizontal: 12,
    paddingVertical: 9,
  },
  reviewTagActive: {
    backgroundColor: COLORS.peach600,
  },
  reviewTagText: {
    color: COLORS.text,
    fontSize: 12,
    fontWeight: '700',
  },
  reviewTagTextActive: {
    color: '#FFFFFF',
  },
  reviewInput: {
    minHeight: 90,
    borderRadius: 18,
    borderWidth: 1,
    borderColor: COLORS.border,
    backgroundColor: COLORS.cardAlt,
    paddingHorizontal: 14,
    paddingVertical: 14,
    color: COLORS.text,
    fontSize: 14,
    textAlignVertical: 'top',
  },
  reviewButton: {
    alignSelf: 'flex-start',
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    backgroundColor: COLORS.black,
    borderRadius: 14,
    paddingHorizontal: 16,
    paddingVertical: 12,
  },
  reviewButtonText: {
    color: '#FFFFFF',
    fontSize: 13,
    fontWeight: '800',
  },
  offersRow: {
    gap: 12,
  },
  offerCard: {
    width: 280,
    borderRadius: 22,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 16,
  },
  offerCodeBadge: {
    width: 38,
    height: 38,
    borderRadius: 19,
    alignItems: 'center',
    justifyContent: 'center',
  },
  offerCode: {
    marginTop: 14,
    color: COLORS.peach600,
    fontSize: 12,
    fontWeight: '900',
    letterSpacing: 1,
  },
  offerTitle: {
    marginTop: 8,
    color: COLORS.text,
    fontSize: 18,
    fontWeight: '900',
    lineHeight: 24,
  },
  offerBody: {
    marginTop: 8,
    color: COLORS.muted,
    fontSize: 13,
    lineHeight: 20,
  },
  merchantsRow: {
    gap: 14,
  },
  merchantCard: {
    width: 286,
    backgroundColor: COLORS.card,
    borderRadius: 24,
    borderWidth: 1,
    borderColor: COLORS.border,
    overflow: 'hidden',
  },
  merchantVisual: {
    height: 150,
    backgroundColor: COLORS.peach100,
    position: 'relative',
  },
  merchantImage: {
    width: '100%',
    height: '100%',
  },
  merchantFallback: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  merchantFallbackText: {
    color: COLORS.peach600,
    fontSize: 34,
    fontWeight: '900',
  },
  favoriteButton: {
    position: 'absolute',
    right: 12,
    top: 12,
    width: 34,
    height: 34,
    borderRadius: 17,
    backgroundColor: 'rgba(43,33,26,0.45)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  merchantRatingPill: {
    position: 'absolute',
    left: 12,
    bottom: 12,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    backgroundColor: 'rgba(255,255,255,0.92)',
    borderRadius: 999,
    paddingHorizontal: 10,
    paddingVertical: 7,
  },
  merchantRatingPillText: {
    color: COLORS.black,
    fontSize: 12,
    fontWeight: '800',
  },
  merchantBody: {
    padding: 16,
    gap: 8,
  },
  merchantName: {
    color: COLORS.text,
    fontSize: 18,
    fontWeight: '900',
  },
  merchantMeta: {
    color: COLORS.muted,
    fontSize: 13,
    fontWeight: '700',
  },
  merchantDescription: {
    color: COLORS.muted,
    fontSize: 13,
    lineHeight: 20,
    minHeight: 40,
  },
  merchantTagsWrap: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
  },
  merchantTag: {
    backgroundColor: COLORS.peach50,
    borderRadius: 999,
    paddingHorizontal: 10,
    paddingVertical: 7,
  },
  merchantTagText: {
    color: COLORS.text,
    fontSize: 11,
    fontWeight: '700',
  },
  merchantFooter: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginTop: 6,
  },
  merchantTrust: {
    flex: 1,
    color: COLORS.peach600,
    fontSize: 12,
    fontWeight: '700',
    paddingRight: 12,
  },
  merchantAction: {
    color: COLORS.text,
    fontSize: 13,
    fontWeight: '800',
  },
  orderCard: {
    backgroundColor: COLORS.card,
    borderRadius: 22,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 16,
    gap: 14,
  },
  orderCardHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  orderThumb: {
    width: 58,
    height: 58,
    borderRadius: 16,
    backgroundColor: COLORS.peach100,
  },
  orderThumbFallback: {
    width: 58,
    height: 58,
    borderRadius: 16,
    backgroundColor: COLORS.peach100,
    alignItems: 'center',
    justifyContent: 'center',
  },
  orderThumbFallbackText: {
    color: COLORS.peach600,
    fontSize: 18,
    fontWeight: '900',
  },
  orderVendor: {
    color: COLORS.text,
    fontSize: 15,
    fontWeight: '800',
  },
  orderSubtitle: {
    marginTop: 2,
    color: COLORS.muted,
    fontSize: 12,
  },
  orderMetaLine: {
    marginTop: 4,
    color: COLORS.subtle,
    fontSize: 12,
  },
  orderTrustRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    gap: 12,
  },
  orderTrustText: {
    flex: 1,
    color: COLORS.text,
    fontSize: 12,
    fontWeight: '700',
  },
  orderActionsRow: {
    flexDirection: 'row',
    gap: 10,
    flexWrap: 'wrap',
  },
  emptyCard: {
    backgroundColor: COLORS.card,
    borderRadius: 22,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 22,
    alignItems: 'flex-start',
    gap: 10,
  },
  emptyTitle: {
    color: COLORS.text,
    fontSize: 16,
    fontWeight: '800',
  },
  emptySubtitle: {
    color: COLORS.muted,
    fontSize: 13,
    lineHeight: 20,
  },
  emptyButton: {
    marginTop: 2,
    backgroundColor: COLORS.black,
    borderRadius: 14,
    paddingHorizontal: 16,
    paddingVertical: 12,
  },
  emptyButtonText: {
    color: '#FFFFFF',
    fontSize: 13,
    fontWeight: '800',
  },
  loadingWrap: {
    paddingVertical: 24,
    alignItems: 'center',
    justifyContent: 'center',
  },
});