import React, { useCallback, useEffect, useMemo, useState } from 'react';
import {
  ActivityIndicator,
  RefreshControl,
  ScrollView,
  StatusBar,
  StyleSheet,
  Switch,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useBottomTabBarHeight } from '@react-navigation/bottom-tabs';
import { Ionicons } from '@expo/vector-icons';
import { BrandPalette, createShadow } from '@/constants/theme';

import { useGrabBasket } from '../../App';
import InlineConfirmCard from './inline-confirm-card';
import InlineErrorCard from './inline-error-card';
import InlineNoticeCard from './inline-notice-card';
import { getErrorMessage, requestJson } from '../lib/api-client';

const COLORS = {
  ...BrandPalette,
  page: BrandPalette.page,
  surface: BrandPalette.surface,
  surfaceAlt: BrandPalette.surfaceAlt,
  line: BrandPalette.line,
  border: BrandPalette.border,
  text: BrandPalette.text,
  muted: BrandPalette.textMuted,
  subtle: BrandPalette.subtle,
  brand: BrandPalette.primary,
  brandSoft: BrandPalette.primarySoft,
  success: BrandPalette.success,
  successSoft: BrandPalette.successSoft,
  warning: BrandPalette.warning,
  warningSoft: BrandPalette.warningSoft,
  info: '#8E4430',
  infoSoft: BrandPalette.infoSoft,
  danger: BrandPalette.danger,
  dangerSoft: BrandPalette.dangerSoft,
  black: BrandPalette.ink,
};

const DELIVERY_ACTIVE_STATUSES = ['ASSIGNED_TO_PARTNER', 'READY_FOR_PICKUP', 'PICKED_UP'];
const SELLER_PENDING_STATUSES = ['CREATED'];
const SELLER_PREP_STATUSES = ['ACCEPTED_BY_SELLER', 'ASSIGNED_TO_PARTNER'];
const SELLER_READY_STATUSES = ['READY_FOR_PICKUP'];

function money(value) {
  return `₹${Number(value || 0).toFixed(0)}`;
}

function formatStatus(status = '') {
  return (
    String(status || '')
      .replace(/_/g, ' ')
      .toLowerCase()
      .replace(/\b\w/g, (char) => char.toUpperCase()) || 'Unknown'
  );
}

function formatDateTime(value) {
  if (!value) return '—';

  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return '—';

  return date.toLocaleString('en-IN', {
    day: '2-digit',
    month: 'short',
    hour: 'numeric',
    minute: '2-digit',
  });
}

function formatDate(value) {
  if (!value) return '—';

  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return '—';

  return date.toLocaleDateString('en-IN', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  });
}

function summarizeOrder(order) {
  const items = Array.isArray(order?.items) ? order.items : [];
  if (!items.length) return 'No items added yet';

  const first = items[0];
  const extraCount = Math.max(0, items.length - 1);
  const qty = Number(first?.qty || 1);

  return `${qty} x ${first?.name_snapshot || 'Item'}${extraCount ? ` +${extraCount} more` : ''}`;
}

function getLatestEvent(order) {
  const events = Array.isArray(order?.events) ? order.events : [];
  if (!events.length) return null;
  return events[events.length - 1] || null;
}

function getStatusTone(status = '') {
  const value = String(status || '').toUpperCase();

  if (value.includes('DELIVERED') || value.includes('AVAILABLE') || value.includes('OPEN')) {
    return { bg: COLORS.successSoft, text: COLORS.success, icon: 'checkmark-circle-outline' };
  }

  if (value.includes('REJECT') || value.includes('CANCEL') || value.includes('CLOSED')) {
    return { bg: COLORS.dangerSoft, text: COLORS.danger, icon: 'close-circle-outline' };
  }

  if (value.includes('READY') || value.includes('PICK') || value.includes('ASSIGNED')) {
    return { bg: COLORS.infoSoft, text: COLORS.info, icon: 'bicycle-outline' };
  }

  return { bg: COLORS.warningSoft, text: COLORS.warning, icon: 'time-outline' };
}

async function request(path, token, { method = 'GET', body, query } = {}) {
  return requestJson(path, {
    method,
    token,
    query,
    body: typeof body === 'string' ? JSON.parse(body) : body,
  });
}

function SectionCard({ title, subtitle, right, children }) {
  return (
    <View style={styles.card}>
      {title || subtitle || right ? (
        <View style={styles.cardHeader}>
          <View style={styles.cardHeaderCopy}>
            {title ? <Text style={styles.cardTitle}>{title}</Text> : null}
            {subtitle ? <Text style={styles.cardSubtitle}>{subtitle}</Text> : null}
          </View>
          {right ? <View>{right}</View> : null}
        </View>
      ) : null}
      {children}
    </View>
  );
}

function KpiTile({ icon, label, value, tone = 'brand' }) {
  const palette =
    tone === 'success'
      ? { bg: COLORS.successSoft, color: COLORS.success }
      : tone === 'info'
        ? { bg: COLORS.infoSoft, color: COLORS.info }
        : tone === 'warning'
          ? { bg: COLORS.warningSoft, color: COLORS.warning }
          : tone === 'danger'
            ? { bg: COLORS.dangerSoft, color: COLORS.danger }
            : { bg: COLORS.brandSoft, color: COLORS.brand };

  return (
    <View style={styles.kpiTile}>
      <View style={[styles.kpiIconWrap, { backgroundColor: palette.bg }]}>
        <Ionicons name={icon} size={18} color={palette.color} />
      </View>
      <Text style={styles.kpiValue}>{value}</Text>
      <Text style={styles.kpiLabel}>{label}</Text>
    </View>
  );
}

function Pill({ text, tone = getStatusTone('') }) {
  return (
    <View style={[styles.pill, { backgroundColor: tone.bg }]}>
      <Ionicons name={tone.icon} size={14} color={tone.text} />
      <Text style={[styles.pillText, { color: tone.text }]}>{text}</Text>
    </View>
  );
}

function PrimaryButton({ label, icon, onPress, disabled = false, tone = 'brand' }) {
  const palette =
    tone === 'success'
      ? { bg: COLORS.success, text: '#FFFFFF', border: COLORS.success }
      : tone === 'danger'
        ? { bg: '#FFFFFF', text: COLORS.danger, border: '#F3C6C6' }
        : tone === 'muted'
          ? { bg: '#FFFFFF', text: COLORS.text, border: COLORS.border }
          : { bg: COLORS.brand, text: '#FFFFFF', border: COLORS.brand };

  return (
    <TouchableOpacity
      activeOpacity={0.9}
      disabled={disabled}
      style={[
        styles.primaryButton,
        {
          backgroundColor: disabled ? '#E8DED5' : palette.bg,
          borderColor: disabled ? '#E8DED5' : palette.border,
        },
      ]}
      onPress={onPress}>
      {icon ? <Ionicons name={icon} size={16} color={disabled ? '#907E70' : palette.text} /> : null}
      <Text style={[styles.primaryButtonText, { color: disabled ? '#907E70' : palette.text }]}>
        {label}
      </Text>
    </TouchableOpacity>
  );
}

function TextField({
  label,
  value,
  onChangeText,
  placeholder,
  keyboardType = 'default',
  multiline = false,
}) {
  return (
    <View style={styles.fieldWrap}>
      <Text style={styles.fieldLabel}>{label}</Text>
      <TextInput
        value={value}
        onChangeText={onChangeText}
        placeholder={placeholder}
        placeholderTextColor={COLORS.subtle}
        keyboardType={keyboardType}
        multiline={multiline}
        style={[styles.input, multiline && styles.inputMultiline]}
      />
    </View>
  );
}

function MetaLine({ icon, label }) {
  return (
    <View style={styles.metaLine}>
      <Ionicons name={icon} size={15} color={COLORS.subtle} />
      <Text style={styles.metaLineText}>{label}</Text>
    </View>
  );
}

function EmptyState({ icon = 'sparkles-outline', title, subtitle }) {
  return (
    <View style={styles.emptyState}>
      <View style={styles.emptyIconWrap}>
        <Ionicons name={icon} size={22} color={COLORS.brand} />
      </View>
      <Text style={styles.emptyTitle}>{title}</Text>
      <Text style={styles.emptySubtitle}>{subtitle}</Text>
    </View>
  );
}

function Timeline({ events = [] }) {
  if (!events.length) {
    return (
      <View style={styles.timelineEmpty}>
        <Text style={styles.timelineEmptyTitle}>No timeline events yet</Text>
        <Text style={styles.timelineEmptySubtitle}>
          This order will become easier to audit once more lifecycle updates are stored.
        </Text>
      </View>
    );
  }

  return (
    <View style={styles.timelineWrap}>
      {events.map((event, index) => {
        const tone = getStatusTone(event?.status);
        const isLast = index === events.length - 1;

        return (
          <View key={`${event?.status}-${event?.created_at || index}`} style={styles.timelineRow}>
            <View style={styles.timelineRail}>
              <View style={[styles.timelineDot, { backgroundColor: tone.text }]} />
              {!isLast ? <View style={styles.timelineLine} /> : null}
            </View>

            <View style={styles.timelineBody}>
              <Text style={styles.timelineTitle}>{formatStatus(event?.status)}</Text>
              <Text style={styles.timelineMeta}>{formatDateTime(event?.created_at)}</Text>
              {event?.note ? <Text style={styles.timelineNote}>{event.note}</Text> : null}
            </View>
          </View>
        );
      })}
    </View>
  );
}

function OrderCard({ order, actions = [], children, defaultExpanded = false }) {
  const [expanded, setExpanded] = useState(defaultExpanded);
  const tone = getStatusTone(order?.status);
  const latestEvent = getLatestEvent(order);
  const hasTimeline = Array.isArray(order?.events) && order.events.length > 0;

  return (
    <View style={styles.orderCard}>
      <View style={styles.orderTopRow}>
        <View style={styles.orderMetaWrap}>
          <Text style={styles.orderTitle}>Order #{order?.id}</Text>
          <Text style={styles.orderSubtitle}>{summarizeOrder(order)}</Text>
        </View>
        <Pill text={formatStatus(order?.status)} tone={tone} />
      </View>

      <View style={styles.metaList}>
        <MetaLine icon="bag-handle-outline" label={`Vendor #${order?.vendor_id || '—'}`} />
        <MetaLine icon="wallet-outline" label={`${money(order?.total_amount)} · ${String(order?.payment_method || '').toUpperCase()}`} />
        <MetaLine
          icon="card-outline"
          label={`Payment ${formatStatus(order?.payment_status || 'PENDING')}`}
        />
        <MetaLine
          icon="time-outline"
          label={
            latestEvent
              ? `${formatStatus(latestEvent.status)} · ${formatDateTime(latestEvent.created_at)}`
              : 'Timeline not available yet'
          }
        />
        {order?.partner_id ? <MetaLine icon="person-outline" label={`Partner #${order.partner_id}`} /> : null}
      </View>

      {actions.length ? <View style={styles.buttonRow}>{actions}</View> : null}

      {hasTimeline ? (
        <TouchableOpacity
          activeOpacity={0.9}
          style={styles.expandRow}
          onPress={() => setExpanded((current) => !current)}>
          <Ionicons
            name={expanded ? 'chevron-up-outline' : 'chevron-down-outline'}
            size={16}
            color={COLORS.muted}
          />
          <Text style={styles.expandRowText}>{expanded ? 'Hide timeline' : 'Show timeline'}</Text>
        </TouchableOpacity>
      ) : null}

      {expanded ? <Timeline events={order?.events || []} /> : null}
      {children}
    </View>
  );
}

function DeliveryIndexScreen({
  state,
  setAvailability,
  saveLocation,
  setLocationForm,
  refresh,
  loadingAction,
}) {
  const summary = state.partnerStatus?.summary || {};
  const latestLocation = state.partnerStatus?.latest_location || null;
  const isAvailable = Boolean(
    state.partnerStatus?.partner?.is_available ?? state.profile?.is_partner_available
  );

  const activeOrders = useMemo(
    () =>
      state.orders.filter((item) =>
        DELIVERY_ACTIVE_STATUSES.includes(String(item.status || '').toUpperCase())
      ),
    [state.orders]
  );

  const deliveredOrders = useMemo(
    () => state.orders.filter((item) => String(item.status || '').toUpperCase() === 'DELIVERED'),
    [state.orders]
  );

  return (
    <ScrollView
      showsVerticalScrollIndicator={false}
      refreshControl={<RefreshControl refreshing={state.refreshing} onRefresh={refresh} />}>
      <SectionCard
        title="Go online and manage your active trips"
        subtitle="This version upgrades the rider app from a shell into an operational dashboard with status, trip counts, and location sync."
        right={
          <View style={styles.switchWrap}>
            <Text style={styles.switchLabel}>{isAvailable ? 'Online' : 'Offline'}</Text>
            <Switch
              value={isAvailable}
              onValueChange={setAvailability}
              trackColor={{ false: '#DCCFC2', true: '#F0B99F' }}
              thumbColor={isAvailable ? COLORS.brand : '#FFFFFF'}
            />
          </View>
        }>
        <View style={styles.kpiGrid}>
          <KpiTile
            icon="navigate-outline"
            label="Active trips"
            value={String(summary.active_order_count ?? activeOrders.length)}
            tone="brand"
          />
          <KpiTile
            icon="checkmark-done-outline"
            label="Delivered"
            value={String(summary.delivered_order_count ?? deliveredOrders.length)}
            tone="success"
          />
          <KpiTile
            icon="cash-outline"
            label="COD collected"
            value={money(summary.cod_cash_collected)}
            tone="info"
          />
          <KpiTile
            icon="cube-outline"
            label="Assigned / ready"
            value={String(summary.assigned_order_count ?? 0)}
            tone="warning"
          />
        </View>
      </SectionCard>

      <SectionCard
        title="Location sync"
        subtitle="Use this to manually sync rider location until native background tracking is enabled.">
        {latestLocation ? (
          <View style={styles.inlineBanner}>
            <Ionicons name="locate-outline" size={16} color={COLORS.info} />
            <Text style={styles.inlineBannerText}>
              Last location update: {formatDateTime(latestLocation.created_at)}
            </Text>
          </View>
        ) : (
          <View style={styles.inlineBannerWarning}>
            <Ionicons name="alert-circle-outline" size={16} color={COLORS.warning} />
            <Text style={styles.inlineBannerText}>No rider location has been synced yet.</Text>
          </View>
        )}

        <View style={styles.twoColRow}>
          <View style={styles.flexOne}>
            <TextField
              label="Latitude"
              value={state.locationForm.lat}
              onChangeText={(value) => setLocationForm((current) => ({ ...current, lat: value }))}
              placeholder="12.9716"
              keyboardType="decimal-pad"
            />
          </View>
          <View style={styles.gapCol} />
          <View style={styles.flexOne}>
            <TextField
              label="Longitude"
              value={state.locationForm.lng}
              onChangeText={(value) => setLocationForm((current) => ({ ...current, lng: value }))}
              placeholder="77.5946"
              keyboardType="decimal-pad"
            />
          </View>
        </View>

        <View style={styles.twoColRow}>
          <View style={styles.flexOne}>
            <TextField
              label="Heading (optional)"
              value={state.locationForm.heading}
              onChangeText={(value) => setLocationForm((current) => ({ ...current, heading: value }))}
              placeholder="180"
              keyboardType="decimal-pad"
            />
          </View>
          <View style={styles.gapCol} />
          <View style={styles.flexOne}>
            <TextField
              label="Speed (optional)"
              value={state.locationForm.speed}
              onChangeText={(value) => setLocationForm((current) => ({ ...current, speed: value }))}
              placeholder="24"
              keyboardType="decimal-pad"
            />
          </View>
        </View>

        <PrimaryButton
          label="Sync rider location"
          icon="locate-outline"
          onPress={saveLocation}
          disabled={loadingAction}
          tone="brand"
        />
      </SectionCard>

      <SectionCard
        title="Live pickup / delivery queue"
        subtitle="These are the orders a rider should be able to act on without opening another screen.">
        {activeOrders.length ? (
          activeOrders.map((order) => {
            const isPickedUp = String(order.status || '').toUpperCase() === 'PICKED_UP';

            return (
              <OrderCard
                key={order.id}
                order={order}
                actions={[
                  <PrimaryButton
                    key={isPickedUp ? 'deliver' : 'pickup'}
                    label={isPickedUp ? 'Mark delivered' : 'Mark picked up'}
                    icon={isPickedUp ? 'checkmark-circle-outline' : 'bag-check-outline'}
                    onPress={() => (isPickedUp ? state.deliver(order.id) : state.pickup(order.id))}
                    disabled={loadingAction}
                    tone={isPickedUp ? 'success' : 'brand'}
                  />,
                ]}>
                {latestLocation ? (
                  <View style={styles.infoStrip}>
                    <Text style={styles.infoStripText}>
                      Latest rider ping at {formatDateTime(latestLocation.created_at)}
                    </Text>
                  </View>
                ) : null}
              </OrderCard>
            );
          })
        ) : (
          <EmptyState
            icon="bicycle-outline"
            title="No live trips"
            subtitle="Once a seller accepts and dispatches an order, it will show up here."
          />
        )}
      </SectionCard>
    </ScrollView>
  );
}

function DeliveryOrdersScreen({ state, refresh, loadingAction }) {
  const activeOrders = useMemo(
    () =>
      state.orders.filter((item) =>
        DELIVERY_ACTIVE_STATUSES.includes(String(item.status || '').toUpperCase())
      ),
    [state.orders]
  );

  const completedOrders = useMemo(
    () =>
      state.orders.filter(
        (item) => !DELIVERY_ACTIVE_STATUSES.includes(String(item.status || '').toUpperCase())
      ),
    [state.orders]
  );

  return (
    <ScrollView
      showsVerticalScrollIndicator={false}
      refreshControl={<RefreshControl refreshing={state.refreshing} onRefresh={refresh} />}>
      <SectionCard title="Assigned orders" subtitle="Fast rider actions matter more than decorative screens.">
        {activeOrders.length ? (
          activeOrders.map((order) => {
            const isPickedUp = String(order.status || '').toUpperCase() === 'PICKED_UP';

            return (
              <OrderCard
                key={order.id}
                order={order}
                actions={[
                  <PrimaryButton
                    key={isPickedUp ? 'deliver' : 'pickup'}
                    label={isPickedUp ? 'Complete delivery' : 'Confirm pickup'}
                    icon={isPickedUp ? 'checkmark-circle-outline' : 'bag-check-outline'}
                    onPress={() => (isPickedUp ? state.deliver(order.id) : state.pickup(order.id))}
                    disabled={loadingAction}
                    tone={isPickedUp ? 'success' : 'brand'}
                  />,
                ]}
              />
            );
          })
        ) : (
          <EmptyState
            icon="time-outline"
            title="Nothing assigned"
            subtitle="Your dispatch queue is empty right now."
          />
        )}
      </SectionCard>

      <SectionCard
        title="Recent completed / closed orders"
        subtitle="Delivered and cancelled orders stay visible here so the rider can verify history.">
        {completedOrders.length ? (
          completedOrders.slice(0, 12).map((order) => <OrderCard key={order.id} order={order} />)
        ) : (
          <EmptyState
            icon="checkmark-done-outline"
            title="No completed history yet"
            subtitle="Delivered or closed orders will appear here."
          />
        )}
      </SectionCard>
    </ScrollView>
  );
}

function DeliveryEarningsScreen({ state, refresh }) {
  const deliveredOrders = useMemo(
    () => state.orders.filter((item) => String(item.status || '').toUpperCase() === 'DELIVERED'),
    [state.orders]
  );

  const codCash = deliveredOrders
    .filter((item) => String(item.payment_method || '').toUpperCase() === 'COD')
    .reduce((sum, item) => sum + Number(item.total_amount || 0), 0);

  const upiCount = deliveredOrders.filter(
    (item) => String(item.payment_method || '').toUpperCase() === 'UPI'
  ).length;

  const avgBasket = deliveredOrders.length
    ? deliveredOrders.reduce((sum, item) => sum + Number(item.total_amount || 0), 0) /
      deliveredOrders.length
    : 0;

  return (
    <ScrollView
      showsVerticalScrollIndicator={false}
      refreshControl={<RefreshControl refreshing={state.refreshing} onRefresh={refresh} />}>
      <SectionCard
        title="Earnings & cash summary"
        subtitle="Delivered orders, COD collected and basket trends stay visible here while payout tooling matures.">
        <View style={styles.kpiGrid}>
          <KpiTile
            icon="checkmark-circle-outline"
            label="Delivered"
            value={String(deliveredOrders.length)}
            tone="success"
          />
          <KpiTile icon="cash-outline" label="COD in hand" value={money(codCash)} tone="warning" />
          <KpiTile icon="phone-portrait-outline" label="UPI orders" value={String(upiCount)} tone="info" />
          <KpiTile icon="stats-chart-outline" label="Avg basket" value={money(avgBasket)} tone="brand" />
        </View>
      </SectionCard>

      <SectionCard
        title="Delivered order log"
        subtitle="Use this history to validate completed deliveries and payout-related order trails.">
        {deliveredOrders.length ? (
          deliveredOrders.map((order) => <OrderCard key={order.id} order={order} />)
        ) : (
          <EmptyState
            icon="cash-outline"
            title="No delivered orders yet"
            subtitle="Once deliveries are completed, a usable payout trail will appear here."
          />
        )}
      </SectionCard>
    </ScrollView>
  );
}

function DeliveryAccountScreen({ state, setAvailability, refresh, logout, loadingAction }) {
  const latestLocation = state.partnerStatus?.latest_location || null;
  const isAvailable = Boolean(
    state.partnerStatus?.partner?.is_available ?? state.profile?.is_partner_available
  );

  return (
    <ScrollView
      showsVerticalScrollIndicator={false}
      refreshControl={<RefreshControl refreshing={state.refreshing} onRefresh={refresh} />}>
      <SectionCard title="Partner account" subtitle="Keep rider identity, availability and sync status in one operational view.">
        <View style={styles.profileRow}>
          <View style={styles.avatarWrap}>
            <Ionicons name="person-outline" size={26} color={COLORS.brand} />
          </View>

          <View style={styles.flexOne}>
            <Text style={styles.profileTitle}>{state.profile?.email || 'Delivery partner'}</Text>
            <Text style={styles.profileSubtitle}>Role: {formatStatus(state.profile?.role)}</Text>
          </View>
        </View>

        <View style={styles.preferenceRow}>
          <Text style={styles.preferenceLabel}>Availability</Text>
          <Switch
            value={isAvailable}
            onValueChange={setAvailability}
            trackColor={{ false: '#DCCFC2', true: '#F0B99F' }}
            thumbColor={isAvailable ? COLORS.brand : '#FFFFFF'}
          />
        </View>

        <View style={styles.metaList}>
          <MetaLine icon="mail-outline" label={state.profile?.email || '—'} />
          <MetaLine icon="calendar-outline" label={`Joined ${formatDate(state.profile?.created_at)}`} />
          <MetaLine
            icon="locate-outline"
            label={
              latestLocation
                ? `Last location ${formatDateTime(latestLocation.created_at)}`
                : 'No location synced yet'
            }
          />
        </View>

        <View style={styles.buttonRow}>
          <PrimaryButton
            label="Refresh account"
            icon="refresh-outline"
            onPress={refresh}
            disabled={loadingAction}
            tone="muted"
          />
          <PrimaryButton
            label="Logout"
            icon="log-out-outline"
            onPress={logout}
            disabled={loadingAction}
            tone="danger"
          />
        </View>
      </SectionCard>
    </ScrollView>
  );
}

function PartnerIndexScreen({ state, refresh, toggleStoreOpen }) {
  const created = state.orders.filter((item) => SELLER_PENDING_STATUSES.includes(String(item.status || '').toUpperCase()));
  const prep = state.orders.filter((item) => SELLER_PREP_STATUSES.includes(String(item.status || '').toUpperCase()));
  const ready = state.orders.filter((item) => SELLER_READY_STATUSES.includes(String(item.status || '').toUpperCase()));
  const delivered = state.orders.filter((item) => String(item.status || '').toUpperCase() === 'DELIVERED');
  const activeProducts = state.products.filter((item) => item.is_available).length;

  return (
    <ScrollView
      showsVerticalScrollIndicator={false}
      refreshControl={<RefreshControl refreshing={state.refreshing} onRefresh={refresh} />}>
      <SectionCard
        title={state.vendor?.name || 'Create your outlet profile'}
        subtitle={
          state.vendor?.description ||
          'This screen now behaves like a real seller dashboard instead of a static shell.'
        }
        right={
          state.vendor ? (
            <View style={styles.switchWrap}>
              <Text style={styles.switchLabel}>{state.vendor?.is_open ? 'Store open' : 'Store closed'}</Text>
              <Switch
                value={Boolean(state.vendor?.is_open)}
                onValueChange={toggleStoreOpen}
                trackColor={{ false: '#DCCFC2', true: '#F0B99F' }}
                thumbColor={state.vendor?.is_open ? COLORS.brand : '#FFFFFF'}
              />
            </View>
          ) : null
        }>
        <View style={styles.kpiGrid}>
          <KpiTile icon="receipt-outline" label="New orders" value={String(created.length)} tone="brand" />
          <KpiTile icon="flame-outline" label="Preparing" value={String(prep.length)} tone="warning" />
          <KpiTile icon="cube-outline" label="Ready" value={String(ready.length)} tone="info" />
          <KpiTile icon="restaurant-outline" label="Live products" value={String(activeProducts)} tone="success" />
        </View>
      </SectionCard>

      <SectionCard
        title="Store health"
        subtitle="Operational status, coverage and fulfillment health stay visible here for quick decisions.">
        {state.vendor ? (
          <View style={styles.metaList}>
            <MetaLine icon="location-outline" label={state.vendor.address || 'Add a business address in Account.'} />
            <MetaLine
              icon="navigate-outline"
              label={`Delivery radius ${Number(state.vendor.delivery_radius_km || 0).toFixed(1)} km`}
            />
            <MetaLine
              icon="time-outline"
              label={state.vendor.is_open ? 'Currently accepting orders' : 'Temporarily paused'}
            />
            <MetaLine
              icon="checkmark-done-outline"
              label={`${delivered.length} delivered orders recorded`}
            />
          </View>
        ) : (
          <EmptyState
            icon="storefront-outline"
            title="No store attached yet"
            subtitle="Open the Account tab, save your outlet details, then return here."
          />
        )}
      </SectionCard>

      <SectionCard
        title="Latest queue"
        subtitle="Sellers need quick visibility into new, preparing, and ready orders.">
        {state.orders.slice(0, 5).length ? (
          state.orders.slice(0, 5).map((order) => <OrderCard key={order.id} order={order} />)
        ) : (
          <EmptyState
            icon="receipt-outline"
            title="No orders yet"
            subtitle="Create a customer order and it will show up here."
          />
        )}
      </SectionCard>
    </ScrollView>
  );
}

function PartnerCatalogScreen({
  state,
  setProductForm,
  saveProduct,
  toggleProductAvailability,
  startEditProduct,
  deleteProduct,
  refresh,
  loadingAction,
}) {
  const { productForm, products } = state;

  return (
    <ScrollView
      showsVerticalScrollIndicator={false}
      refreshControl={<RefreshControl refreshing={state.refreshing} onRefresh={refresh} />}>
      <SectionCard
        title={productForm.id ? 'Edit menu item' : 'Add menu item'}
        subtitle="Manage catalog details, availability and pricing without leaving the operations flow.">
        <TextField
          label="Item name"
          value={productForm.name}
          onChangeText={(value) => setProductForm((current) => ({ ...current, name: value }))}
          placeholder="Paneer Tikka Wrap"
        />
        <TextField
          label="Description"
          value={productForm.description}
          onChangeText={(value) => setProductForm((current) => ({ ...current, description: value }))}
          placeholder="Short item description"
          multiline
        />
        <TextField
          label="Price"
          value={productForm.price}
          onChangeText={(value) => setProductForm((current) => ({ ...current, price: value }))}
          placeholder="249"
          keyboardType="decimal-pad"
        />

        <View style={styles.preferenceRow}>
          <Text style={styles.preferenceLabel}>Available for ordering</Text>
          <Switch
            value={Boolean(productForm.is_available)}
            onValueChange={(value) => setProductForm((current) => ({ ...current, is_available: value }))}
            trackColor={{ false: '#DCCFC2', true: '#F0B99F' }}
            thumbColor={productForm.is_available ? COLORS.brand : '#FFFFFF'}
          />
        </View>

        <View style={styles.buttonRow}>
          <PrimaryButton
            label={productForm.id ? 'Update item' : 'Add item'}
            icon="save-outline"
            onPress={saveProduct}
            disabled={loadingAction}
            tone="brand"
          />
          {productForm.id ? (
            <PrimaryButton
              label="Cancel edit"
              icon="close-outline"
              onPress={() => setProductForm({ id: null, name: '', description: '', price: '', is_available: true })}
              disabled={loadingAction}
              tone="muted"
            />
          ) : null}
        </View>
      </SectionCard>

      <SectionCard title="Catalog" subtitle="You now have working CRUD plus fast availability toggles.">
        {products.length ? (
          products.map((product) => (
            <View key={product.id} style={styles.catalogCard}>
              <View style={styles.catalogTopRow}>
                <View style={styles.flexOne}>
                  <Text style={styles.catalogName}>{product.name}</Text>
                  <Text style={styles.catalogDescription}>
                    {product.description || 'No description added'}
                  </Text>
                </View>
                <Pill
                  text={product.is_available ? 'Available' : 'Unavailable'}
                  tone={product.is_available ? getStatusTone('DELIVERED') : getStatusTone('REJECTED')}
                />
              </View>

              <View style={styles.catalogFooter}>
                <Text style={styles.catalogPrice}>{money(product.price)}</Text>
                <View style={styles.buttonRow}>
                  <PrimaryButton
                    label="Edit"
                    icon="create-outline"
                    onPress={() => startEditProduct(product)}
                    disabled={loadingAction}
                    tone="muted"
                  />
                  <PrimaryButton
                    label={product.is_available ? 'Pause' : 'Enable'}
                    icon={product.is_available ? 'pause-outline' : 'play-outline'}
                    onPress={() => toggleProductAvailability(product)}
                    disabled={loadingAction}
                    tone="brand"
                  />
                  <PrimaryButton
                    label="Delete"
                    icon="trash-outline"
                    onPress={() => deleteProduct(product.id)}
                    disabled={loadingAction}
                    tone="danger"
                  />
                </View>
              </View>
            </View>
          ))
        ) : (
          <EmptyState
            icon="restaurant-outline"
            title="No menu yet"
            subtitle="Add your first product above and it will appear here."
          />
        )}
      </SectionCard>
    </ScrollView>
  );
}

function PartnerOrdersScreen({ state, refresh, acceptOrder, readyOrder, rejectOrder, loadingAction }) {
  const created = state.orders.filter((item) => SELLER_PENDING_STATUSES.includes(String(item.status || '').toUpperCase()));
  const preparing = state.orders.filter((item) => SELLER_PREP_STATUSES.includes(String(item.status || '').toUpperCase()));
  const ready = state.orders.filter((item) => SELLER_READY_STATUSES.includes(String(item.status || '').toUpperCase()));
  const closed = state.orders.filter(
    (item) =>
      ![...SELLER_PENDING_STATUSES, ...SELLER_PREP_STATUSES, ...SELLER_READY_STATUSES].includes(
        String(item.status || '').toUpperCase()
      )
  );

  return (
    <ScrollView
      showsVerticalScrollIndicator={false}
      refreshControl={<RefreshControl refreshing={state.refreshing} onRefresh={refresh} />}>
      <SectionCard title="New orders" subtitle="Accept or reject the queue quickly.">
        {created.length ? (
          created.map((order) => (
            <OrderCard
              key={order.id}
              order={order}
              actions={[
                <PrimaryButton
                  key="accept"
                  label="Accept"
                  icon="checkmark-outline"
                  onPress={() => acceptOrder(order.id)}
                  disabled={loadingAction}
                  tone="success"
                />,
                <PrimaryButton
                  key="reject"
                  label="Reject"
                  icon="close-outline"
                  onPress={() => rejectOrder(order.id)}
                  disabled={loadingAction}
                  tone="danger"
                />,
              ]}
            />
          ))
        ) : (
          <EmptyState
            icon="receipt-outline"
            title="No new orders"
            subtitle="Fresh customer orders will appear here."
          />
        )}
      </SectionCard>

      <SectionCard title="Preparing / assigned" subtitle="Mark orders ready without opening another app shell.">
        {preparing.length ? (
          preparing.map((order) => (
            <OrderCard
              key={order.id}
              order={order}
              actions={[
                <PrimaryButton
                  key="ready"
                  label="Ready for pickup"
                  icon="cube-outline"
                  onPress={() => readyOrder(order.id)}
                  disabled={loadingAction}
                  tone="brand"
                />,
              ]}
            />
          ))
        ) : (
          <EmptyState
            icon="restaurant-outline"
            title="Nothing in preparation"
            subtitle="Accepted orders will move here."
          />
        )}
      </SectionCard>

      <SectionCard title="Ready / closed orders" subtitle="Useful for kitchen visibility and dispatch verification.">
        {[...ready, ...closed].length ? (
          [...ready, ...closed].slice(0, 12).map((order) => <OrderCard key={order.id} order={order} />)
        ) : (
          <EmptyState
            icon="checkmark-done-outline"
            title="No history yet"
            subtitle="Once orders move forward, they will show here."
          />
        )}
      </SectionCard>
    </ScrollView>
  );
}

function PartnerAccountScreen({ state, setVendorForm, saveVendor, refresh, logout, loadingAction }) {
  const { vendorForm } = state;

  return (
    <ScrollView
      showsVerticalScrollIndicator={false}
      refreshControl={<RefreshControl refreshing={state.refreshing} onRefresh={refresh} />}>
      <SectionCard title="Store settings" subtitle="Sellers need editable business information and operational controls.">
        <TextField
          label="Store name"
          value={vendorForm.name}
          onChangeText={(value) => setVendorForm((current) => ({ ...current, name: value }))}
          placeholder="Grab Basket Kitchen"
        />
        <TextField
          label="Description"
          value={vendorForm.description}
          onChangeText={(value) => setVendorForm((current) => ({ ...current, description: value }))}
          placeholder="What should customers know?"
          multiline
        />
        <TextField
          label="Address"
          value={vendorForm.address}
          onChangeText={(value) => setVendorForm((current) => ({ ...current, address: value }))}
          placeholder="Street, area, city"
          multiline
        />
        <TextField
          label="Latitude"
          value={vendorForm.lat}
          onChangeText={(value) => setVendorForm((current) => ({ ...current, lat: value }))}
          placeholder="12.9716"
          keyboardType="decimal-pad"
        />
        <TextField
          label="Longitude"
          value={vendorForm.lng}
          onChangeText={(value) => setVendorForm((current) => ({ ...current, lng: value }))}
          placeholder="77.5946"
          keyboardType="decimal-pad"
        />
        <TextField
          label="Delivery radius (km)"
          value={vendorForm.delivery_radius_km}
          onChangeText={(value) => setVendorForm((current) => ({ ...current, delivery_radius_km: value }))}
          placeholder="5"
          keyboardType="decimal-pad"
        />

        <View style={styles.preferenceRow}>
          <Text style={styles.preferenceLabel}>Store open</Text>
          <Switch
            value={Boolean(vendorForm.is_open)}
            onValueChange={(value) => setVendorForm((current) => ({ ...current, is_open: value }))}
            trackColor={{ false: '#DCCFC2', true: '#F0B99F' }}
            thumbColor={vendorForm.is_open ? COLORS.brand : '#FFFFFF'}
          />
        </View>

        <View style={styles.buttonRow}>
          <PrimaryButton
            label="Save settings"
            icon="save-outline"
            onPress={saveVendor}
            disabled={loadingAction}
            tone="brand"
          />
          <PrimaryButton
            label="Logout"
            icon="log-out-outline"
            onPress={logout}
            disabled={loadingAction}
            tone="danger"
          />
        </View>
      </SectionCard>

      <SectionCard title="Logged-in business account" subtitle="Keep seller identity separate from rider and customer roles.">
        <View style={styles.metaList}>
          <MetaLine icon="mail-outline" label={state.profile?.email || '—'} />
          <MetaLine icon="shield-checkmark-outline" label={`Role: ${formatStatus(state.profile?.role)}`} />
          <MetaLine icon="calendar-outline" label={`Joined ${formatDate(state.profile?.created_at)}`} />
        </View>
      </SectionCard>
    </ScrollView>
  );
}

export default function OperationsScreen({ variant, screen }) {
  const { authToken, sessionReady, isAuthenticated, profile: contextProfile, logout, appVariantName } =
    useGrabBasket();
  const tabBarHeight = useBottomTabBarHeight();

  const [refreshing, setRefreshing] = useState(false);
  const [loadingAction, setLoadingAction] = useState(false);
  const [profile, setProfile] = useState(contextProfile || null);
  const [partnerStatus, setPartnerStatus] = useState(null);
  const [orders, setOrders] = useState([]);
  const [vendor, setVendor] = useState(null);
  const [products, setProducts] = useState([]);
  const [locationForm, setLocationForm] = useState({
    lat: '',
    lng: '',
    heading: '',
    speed: '',
  });
  const [productForm, setProductForm] = useState({
    id: null,
    name: '',
    description: '',
    price: '',
    is_available: true,
  });
  const [vendorForm, setVendorForm] = useState({
    name: '',
    description: '',
    address: '',
    lat: '',
    lng: '',
    delivery_radius_km: '5',
    is_open: true,
  });
  const [inlineError, setInlineError] = useState('');
  const [inlineNotice, setInlineNotice] = useState(null);
  const [pendingConfirm, setPendingConfirm] = useState(null);

  useEffect(() => {
    setProfile(contextProfile || null);
  }, [contextProfile]);

  const showNotice = useCallback((title, message, tone = 'success') => {
    setInlineNotice({ title, message, tone });
  }, []);

  const clearNotice = useCallback(() => {
    setInlineNotice(null);
  }, []);

  const showError = useCallback((message, fallback = 'Please try again.') => {
    setInlineError(getErrorMessage(message, fallback));
  }, []);

  const clearError = useCallback(() => {
    setInlineError('');
  }, []);

  const loadData = useCallback(
    async ({ silent = false } = {}) => {
      if (!authToken) return;

      try {
        if (!silent) setRefreshing(true);

        if (variant === 'delivery') {
          const [profileResponse, statusResponse, orderResponse] = await Promise.all([
            request('/me/profile', authToken),
            request('/partner/status', authToken).catch((error) => {
              if (error?.status === 404) return null;
              throw error;
            }),
            request('/partner/orders', authToken, { query: { limit: 100 } }),
          ]);

          setProfile(profileResponse || null);
          setPartnerStatus(statusResponse || null);
          setOrders(Array.isArray(orderResponse) ? orderResponse : []);

          if (statusResponse?.latest_location) {
            setLocationForm({
              lat:
                statusResponse.latest_location.lat != null
                  ? String(statusResponse.latest_location.lat)
                  : '',
              lng:
                statusResponse.latest_location.lng != null
                  ? String(statusResponse.latest_location.lng)
                  : '',
              heading:
                statusResponse.latest_location.heading != null
                  ? String(statusResponse.latest_location.heading)
                  : '',
              speed:
                statusResponse.latest_location.speed != null
                  ? String(statusResponse.latest_location.speed)
                  : '',
            });
          }

          return;
        }

        const [profileResponse, vendorResponse, productResponse, orderResponse] = await Promise.all([
          request('/me/profile', authToken),
          request('/seller/vendor', authToken).catch((error) => {
            if (error?.status === 404) return null;
            throw error;
          }),
          request('/seller/products', authToken).catch((error) => {
            if (error?.status === 404) return [];
            throw error;
          }),
          request('/seller/orders', authToken).catch((error) => {
            if (error?.status === 404) return [];
            throw error;
          }),
        ]);

        setProfile(profileResponse || null);
        setPartnerStatus(null);
        setVendor(vendorResponse || null);
        setProducts(Array.isArray(productResponse) ? productResponse : []);
        setOrders(Array.isArray(orderResponse) ? orderResponse : []);

        const nextVendor = vendorResponse || null;
        setVendorForm({
          name: nextVendor?.name || '',
          description: nextVendor?.description || '',
          address: nextVendor?.address || '',
          lat: nextVendor?.lat != null ? String(nextVendor.lat) : '',
          lng: nextVendor?.lng != null ? String(nextVendor.lng) : '',
          delivery_radius_km:
            nextVendor?.delivery_radius_km != null ? String(nextVendor.delivery_radius_km) : '5',
          is_open: nextVendor?.is_open !== false,
        });
      } catch (error) {
        showError(error, `${appVariantName} could not load this screen.`);
        if (error?.status === 401) {
          logout().catch(() => {});
        }
      } finally {
        if (!silent) setRefreshing(false);
      }
    },
    [appVariantName, authToken, logout, showError, variant]
  );

  useEffect(() => {
    if (!sessionReady || !isAuthenticated || !authToken) return;
    loadData({ silent: false }).catch(() => {});
  }, [authToken, isAuthenticated, loadData, sessionReady]);

  const refresh = useCallback(() => loadData({ silent: false }), [loadData]);

  const runAction = useCallback(
    async (work, successMessage) => {
      try {
        setLoadingAction(true);
        await work();
        clearError();
        if (successMessage) {
          showNotice('Done', successMessage, 'success');
        }
        await loadData({ silent: true });
      } catch (error) {
        showError(error, 'Please try again.');
        if (error?.status === 401) {
          logout().catch(() => {});
        }
      } finally {
        setLoadingAction(false);
      }
    },
    [clearError, loadData, logout, showError, showNotice]
  );

  const setAvailability = useCallback(
    (value) => {
      runAction(
        async () => {
          await request('/partner/availability', authToken, {
            method: 'POST',
            query: { is_available: value },
          });
        },
        value
          ? 'Availability updated. The app will keep you offline if you already have an active trip.'
          : 'You are offline now.'
      );
    },
    [authToken, runAction]
  );

  const saveLocation = useCallback(() => {
    const lat = Number(locationForm.lat);
    const lng = Number(locationForm.lng);

    if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
      showError('Enter valid latitude and longitude values first.', 'Enter valid latitude and longitude values first.');
      return;
    }

    const payload = { lat, lng };
    const heading = Number(locationForm.heading);
    const speed = Number(locationForm.speed);

    if (locationForm.heading.trim() && Number.isFinite(heading)) {
      payload.heading = heading;
    }

    if (locationForm.speed.trim() && Number.isFinite(speed)) {
      payload.speed = speed;
    }

    runAction(async () => {
      await request('/partner/location', authToken, {
        method: 'POST',
        body: JSON.stringify(payload),
      });
    }, 'Rider location synced.');
  }, [authToken, locationForm, runAction, showError]);

  const pickup = useCallback(
    (orderId) => {
      runAction(async () => {
        await request(`/partner/orders/${orderId}/pickup`, authToken, { method: 'POST' });
      }, `Order #${orderId} marked as picked up.`);
    },
    [authToken, runAction]
  );

  const deliver = useCallback(
    (orderId) => {
      runAction(async () => {
        await request(`/partner/orders/${orderId}/deliver`, authToken, { method: 'POST' });
      }, `Order #${orderId} marked as delivered.`);
    },
    [authToken, runAction]
  );

  const acceptOrder = useCallback(
    (orderId) => {
      runAction(async () => {
        await request(`/seller/orders/${orderId}/accept`, authToken, { method: 'POST' });
      }, `Order #${orderId} accepted.`);
    },
    [authToken, runAction]
  );

  const rejectOrder = useCallback((orderId) => {
    setPendingConfirm({
      title: 'Reject order',
      message: `Reject order #${orderId}?`,
      confirmLabel: 'Reject',
      cancelLabel: 'Keep order',
      tone: 'danger',
      execute: () =>
        runAction(async () => {
          await request(`/seller/orders/${orderId}/reject`, authToken, {
            method: 'POST',
            query: { reason: 'Rejected from seller app' },
          });
          setPendingConfirm(null);
        }, `Order #${orderId} rejected.`),
    });
  }, [authToken, runAction]);

  const readyOrder = useCallback(
    (orderId) => {
      runAction(async () => {
        await request(`/seller/orders/${orderId}/ready`, authToken, { method: 'POST' });
      }, `Order #${orderId} is ready for pickup.`);
    },
    [authToken, runAction]
  );

  const toggleStoreOpen = useCallback(
    (value) => {
      runAction(async () => {
        await request('/seller/vendor', authToken, {
          method: 'PATCH',
          body: JSON.stringify({ is_open: value }),
        });
        setVendor((current) => ({ ...(current || {}), is_open: value }));
        setVendorForm((current) => ({ ...current, is_open: value }));
      }, value ? 'Your store is open for new orders.' : 'Your store is paused.');
    },
    [authToken, runAction]
  );

  const saveProduct = useCallback(() => {
    if (!productForm.name.trim()) {
      showError('Enter a menu item name first.', 'Enter a menu item name first.');
      return;
    }

    const price = Number(productForm.price);
    if (!Number.isFinite(price) || price <= 0) {
      showError('Enter a valid product price.', 'Enter a valid product price.');
      return;
    }

    const payload = {
      name: productForm.name.trim(),
      description: productForm.description.trim(),
      price,
      is_available: Boolean(productForm.is_available),
    };

    runAction(async () => {
      if (productForm.id) {
        await request(`/seller/products/${productForm.id}`, authToken, {
          method: 'PATCH',
          body: JSON.stringify(payload),
        });
      } else {
        await request('/seller/products', authToken, {
          method: 'POST',
          body: JSON.stringify(payload),
        });
      }

      setProductForm({ id: null, name: '', description: '', price: '', is_available: true });
    }, productForm.id ? 'Menu item updated.' : 'Menu item added.');
  }, [authToken, productForm, runAction, showError]);

  const toggleProductAvailability = useCallback(
    (product) => {
      runAction(async () => {
        await request(`/seller/products/${product.id}`, authToken, {
          method: 'PATCH',
          body: JSON.stringify({ is_available: !product.is_available }),
        });
      }, `${product.name} is now ${product.is_available ? 'paused' : 'available'}.`);
    },
    [authToken, runAction]
  );

  const startEditProduct = useCallback((product) => {
    setProductForm({
      id: product.id,
      name: product.name || '',
      description: product.description || '',
      price: product.price != null ? String(product.price) : '',
      is_available: product.is_available !== false,
    });
  }, []);

  const deleteProduct = useCallback((productId) => {
    setPendingConfirm({
      title: 'Delete item',
      message: 'This will remove the product from your catalog.',
      confirmLabel: 'Delete',
      cancelLabel: 'Keep item',
      tone: 'danger',
      execute: () =>
        runAction(async () => {
          await request(`/seller/products/${productId}`, authToken, { method: 'DELETE' });
          setPendingConfirm(null);
        }, 'Product removed.'),
    });
  }, [authToken, runAction]);

  const saveVendor = useCallback(() => {
    const name = vendorForm.name.trim();
    if (!name) {
      showError('Enter a store name first.', 'Enter a store name first.');
      return;
    }

    const payload = {
      name,
      description: vendorForm.description.trim(),
      address: vendorForm.address.trim(),
      is_open: Boolean(vendorForm.is_open),
    };

    const lat = Number(vendorForm.lat);
    const lng = Number(vendorForm.lng);
    const radius = Number(vendorForm.delivery_radius_km);

    if (vendorForm.lat.trim() && Number.isFinite(lat)) payload.lat = lat;
    if (vendorForm.lng.trim() && Number.isFinite(lng)) payload.lng = lng;
    if (vendorForm.delivery_radius_km.trim() && Number.isFinite(radius) && radius > 0) {
      payload.delivery_radius_km = radius;
    }

    runAction(async () => {
      if (vendor) {
        await request('/seller/vendor', authToken, {
          method: 'PATCH',
          body: JSON.stringify(payload),
        });
      } else {
        await request('/seller/vendor', authToken, {
          method: 'POST',
          query: {
            name: payload.name,
            description: payload.description,
            address: payload.address,
          },
        });

        await request('/seller/vendor', authToken, {
          method: 'PATCH',
          body: JSON.stringify(payload),
        });
      }
    }, vendor ? 'Store settings updated.' : 'Store profile created.');
  }, [authToken, runAction, showError, vendor, vendorForm]);

  const state = {
    refreshing,
    profile,
    partnerStatus,
    orders,
    vendor,
    products,
    productForm,
    vendorForm,
    locationForm,
    pickup,
    deliver,
  };

  if (!sessionReady) {
    return (
      <SafeAreaView style={styles.safeArea}>
        <StatusBar barStyle="dark-content" backgroundColor={COLORS.page} />
        <View style={styles.centerState}>
          <ActivityIndicator color={COLORS.brand} />
          <Text style={styles.centerTitle}>Preparing {appVariantName}</Text>
          <Text style={styles.centerSubtitle}>Loading the latest authenticated state.</Text>
        </View>
      </SafeAreaView>
    );
  }

  if (!isAuthenticated) {
    return (
      <SafeAreaView style={styles.safeArea}>
        <StatusBar barStyle="dark-content" backgroundColor={COLORS.page} />
        <View style={styles.centerState}>
          <Ionicons name="lock-closed-outline" size={28} color={COLORS.brand} />
          <Text style={styles.centerTitle}>Sign in required</Text>
          <Text style={styles.centerSubtitle}>
            Use the account flow for this app variant before opening operations screens.
          </Text>
        </View>
      </SafeAreaView>
    );
  }

  return (
    <SafeAreaView style={styles.safeArea}>
      <StatusBar barStyle="dark-content" backgroundColor={COLORS.page} />

      <View style={styles.wrapper}>
        <View style={styles.feedbackStack}>
          <InlineErrorCard
            title={`${appVariantName} sync issue`}
            message={inlineError}
            onRetry={refresh}
            onDismiss={clearError}
          />
          <InlineNoticeCard
            title={inlineNotice?.title || 'Updated'}
            message={inlineNotice?.message || ''}
            tone={inlineNotice?.tone || 'success'}
            onDismiss={clearNotice}
          />
          <InlineConfirmCard
            title={pendingConfirm?.title || 'Please confirm'}
            message={pendingConfirm?.message || ''}
            confirmLabel={pendingConfirm?.confirmLabel || 'Confirm'}
            cancelLabel={pendingConfirm?.cancelLabel || 'Cancel'}
            tone={pendingConfirm?.tone || 'danger'}
            onConfirm={() => pendingConfirm?.execute?.()}
            onCancel={() => setPendingConfirm(null)}
          />
        </View>
        {variant === 'delivery' && screen === 'index' ? (
          <DeliveryIndexScreen
            state={state}
            setAvailability={setAvailability}
            saveLocation={saveLocation}
            setLocationForm={setLocationForm}
            refresh={refresh}
            loadingAction={loadingAction}
          />
        ) : null}

        {variant === 'delivery' && screen === 'orders' ? (
          <DeliveryOrdersScreen
            state={state}
            refresh={refresh}
            loadingAction={loadingAction}
          />
        ) : null}

        {variant === 'delivery' && screen === 'earnings' ? (
          <DeliveryEarningsScreen state={state} refresh={refresh} />
        ) : null}

        {variant === 'delivery' && screen === 'account' ? (
          <DeliveryAccountScreen
            state={state}
            setAvailability={setAvailability}
            refresh={refresh}
            logout={logout}
            loadingAction={loadingAction}
          />
        ) : null}

        {variant === 'partner' && screen === 'index' ? (
          <PartnerIndexScreen
            state={state}
            refresh={refresh}
            toggleStoreOpen={toggleStoreOpen}
          />
        ) : null}

        {variant === 'partner' && screen === 'catalog' ? (
          <PartnerCatalogScreen
            state={state}
            setProductForm={setProductForm}
            saveProduct={saveProduct}
            toggleProductAvailability={toggleProductAvailability}
            startEditProduct={startEditProduct}
            deleteProduct={deleteProduct}
            refresh={refresh}
            loadingAction={loadingAction}
          />
        ) : null}

        {variant === 'partner' && screen === 'orders' ? (
          <PartnerOrdersScreen
            state={state}
            refresh={refresh}
            acceptOrder={acceptOrder}
            readyOrder={readyOrder}
            rejectOrder={rejectOrder}
            loadingAction={loadingAction}
          />
        ) : null}

        {variant === 'partner' && screen === 'account' ? (
          <PartnerAccountScreen
            state={state}
            setVendorForm={setVendorForm}
            saveVendor={saveVendor}
            refresh={refresh}
            logout={logout}
            loadingAction={loadingAction}
          />
        ) : null}
      </View>

      <View style={{ height: tabBarHeight + 12 }} />
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: {
    flex: 1,
    backgroundColor: COLORS.page,
  },
  wrapper: {
    flex: 1,
    paddingHorizontal: 16,
    paddingTop: 14,
  },
  feedbackStack: {
    gap: 12,
    marginBottom: 14,
  },
  centerState: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 28,
    gap: 8,
  },
  centerTitle: {
    fontSize: 18,
    fontWeight: '800',
    color: COLORS.text,
  },
  centerSubtitle: {
    fontSize: 13,
    lineHeight: 19,
    textAlign: 'center',
    color: COLORS.muted,
  },
  card: {
    backgroundColor: COLORS.surface,
    borderRadius: 28,
    padding: 18,
    borderWidth: 1,
    borderColor: COLORS.border,
    marginBottom: 14,
    ...createShadow(0.08, 16, 8),
  },
  cardHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    gap: 12,
    marginBottom: 14,
  },
  cardHeaderCopy: {
    flex: 1,
    gap: 4,
  },
  cardTitle: {
    fontSize: 17,
    fontWeight: '800',
    color: COLORS.text,
  },
  cardSubtitle: {
    fontSize: 13,
    lineHeight: 19,
    color: COLORS.muted,
  },
  switchWrap: {
    alignItems: 'flex-end',
    gap: 8,
  },
  switchLabel: {
    fontSize: 12,
    fontWeight: '700',
    color: COLORS.muted,
  },
  kpiGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 10,
  },
  kpiTile: {
    width: '48%',
    backgroundColor: COLORS.surfaceAlt,
    borderRadius: 22,
    padding: 15,
    borderWidth: 1,
    borderColor: COLORS.line,
    gap: 8,
  },
  kpiIconWrap: {
    width: 34,
    height: 34,
    borderRadius: 17,
    alignItems: 'center',
    justifyContent: 'center',
  },
  kpiValue: {
    fontSize: 18,
    fontWeight: '800',
    color: COLORS.text,
  },
  kpiLabel: {
    fontSize: 12,
    lineHeight: 18,
    color: COLORS.muted,
  },
  pill: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 5,
    paddingHorizontal: 10,
    paddingVertical: 6,
    borderRadius: 999,
  },
  pillText: {
    fontSize: 12,
    fontWeight: '700',
  },
  primaryButton: {
    minHeight: 44,
    borderRadius: 22,
    paddingHorizontal: 16,
    borderWidth: 1,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
  },
  primaryButtonText: {
    fontSize: 13,
    fontWeight: '800',
  },
  fieldWrap: {
    marginBottom: 12,
  },
  fieldLabel: {
    fontSize: 12,
    fontWeight: '700',
    color: COLORS.muted,
    marginBottom: 6,
  },
  input: {
    minHeight: 48,
    borderRadius: 18,
    borderWidth: 1,
    borderColor: COLORS.border,
    backgroundColor: COLORS.surfaceAlt,
    paddingHorizontal: 14,
    color: COLORS.text,
    fontSize: 14,
  },
  inputMultiline: {
    minHeight: 88,
    textAlignVertical: 'top',
    paddingTop: 12,
  },
  orderCard: {
    borderRadius: 22,
    padding: 15,
    backgroundColor: COLORS.surfaceAlt,
    borderWidth: 1,
    borderColor: COLORS.line,
    marginBottom: 10,
  },
  orderTopRow: {
    flexDirection: 'row',
    gap: 10,
    justifyContent: 'space-between',
    alignItems: 'flex-start',
  },
  orderMetaWrap: {
    flex: 1,
    gap: 4,
    paddingRight: 8,
  },
  orderTitle: {
    fontSize: 15,
    fontWeight: '800',
    color: COLORS.text,
  },
  orderSubtitle: {
    fontSize: 13,
    lineHeight: 19,
    color: COLORS.muted,
  },
  metaList: {
    gap: 9,
    marginTop: 12,
  },
  metaLine: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  metaLineText: {
    flex: 1,
    fontSize: 13,
    lineHeight: 19,
    color: COLORS.muted,
  },
  buttonRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 10,
    marginTop: 14,
  },
  expandRow: {
    marginTop: 12,
    alignSelf: 'flex-start',
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
  },
  expandRowText: {
    fontSize: 12,
    fontWeight: '700',
    color: COLORS.muted,
  },
  timelineWrap: {
    marginTop: 14,
    paddingTop: 12,
    borderTopWidth: 1,
    borderTopColor: COLORS.line,
  },
  timelineRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: 12,
    minHeight: 58,
  },
  timelineRail: {
    width: 18,
    alignItems: 'center',
  },
  timelineDot: {
    width: 10,
    height: 10,
    borderRadius: 5,
    marginTop: 5,
  },
  timelineLine: {
    width: 2,
    flex: 1,
    marginTop: 6,
    backgroundColor: COLORS.line,
  },
  timelineBody: {
    flex: 1,
    paddingBottom: 12,
  },
  timelineTitle: {
    fontSize: 13,
    fontWeight: '800',
    color: COLORS.text,
  },
  timelineMeta: {
    marginTop: 2,
    fontSize: 12,
    color: COLORS.muted,
  },
  timelineNote: {
    marginTop: 4,
    fontSize: 12,
    lineHeight: 18,
    color: COLORS.text,
  },
  timelineEmpty: {
    marginTop: 14,
    borderRadius: 16,
    backgroundColor: COLORS.surface,
    borderWidth: 1,
    borderColor: COLORS.line,
    padding: 14,
  },
  timelineEmptyTitle: {
    fontSize: 13,
    fontWeight: '800',
    color: COLORS.text,
  },
  timelineEmptySubtitle: {
    marginTop: 4,
    fontSize: 12,
    lineHeight: 18,
    color: COLORS.muted,
  },
  inlineBanner: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    paddingHorizontal: 12,
    paddingVertical: 10,
    borderRadius: 14,
    backgroundColor: COLORS.infoSoft,
    marginBottom: 14,
  },
  inlineBannerWarning: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    paddingHorizontal: 12,
    paddingVertical: 10,
    borderRadius: 14,
    backgroundColor: COLORS.warningSoft,
    marginBottom: 14,
  },
  inlineBannerText: {
    flex: 1,
    fontSize: 12,
    lineHeight: 18,
    color: COLORS.text,
  },
  infoStrip: {
    marginTop: 12,
    borderRadius: 14,
    paddingHorizontal: 12,
    paddingVertical: 10,
    backgroundColor: COLORS.surface,
    borderWidth: 1,
    borderColor: COLORS.line,
  },
  infoStripText: {
    fontSize: 12,
    lineHeight: 18,
    color: COLORS.muted,
  },
  twoColRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
  },
  flexOne: {
    flex: 1,
  },
  gapCol: {
    width: 10,
  },
  emptyState: {
    alignItems: 'center',
    gap: 8,
    paddingVertical: 22,
  },
  emptyIconWrap: {
    width: 42,
    height: 42,
    borderRadius: 21,
    backgroundColor: COLORS.brandSoft,
    alignItems: 'center',
    justifyContent: 'center',
  },
  emptyTitle: {
    fontSize: 15,
    fontWeight: '800',
    color: COLORS.text,
  },
  emptySubtitle: {
    fontSize: 13,
    lineHeight: 19,
    textAlign: 'center',
    color: COLORS.muted,
  },
  profileRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  avatarWrap: {
    width: 52,
    height: 52,
    borderRadius: 26,
    backgroundColor: COLORS.brandSoft,
    alignItems: 'center',
    justifyContent: 'center',
  },
  profileTitle: {
    fontSize: 16,
    fontWeight: '800',
    color: COLORS.text,
  },
  profileSubtitle: {
    fontSize: 13,
    marginTop: 2,
    color: COLORS.muted,
  },
  preferenceRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginTop: 14,
    paddingVertical: 4,
  },
  preferenceLabel: {
    fontSize: 14,
    fontWeight: '700',
    color: COLORS.text,
  },
  catalogCard: {
    borderRadius: 20,
    padding: 15,
    backgroundColor: COLORS.surfaceAlt,
    borderWidth: 1,
    borderColor: COLORS.line,
    marginBottom: 10,
    gap: 12,
  },
  catalogTopRow: {
    flexDirection: 'row',
    gap: 10,
    alignItems: 'flex-start',
    justifyContent: 'space-between',
  },
  catalogName: {
    fontSize: 15,
    fontWeight: '800',
    color: COLORS.text,
  },
  catalogDescription: {
    fontSize: 13,
    lineHeight: 19,
    color: COLORS.muted,
    marginTop: 4,
  },
  catalogFooter: {
    gap: 12,
  },
  catalogPrice: {
    fontSize: 16,
    fontWeight: '800',
    color: COLORS.text,
  },
});
