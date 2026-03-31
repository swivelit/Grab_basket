import React from 'react';
import { Platform, StyleSheet, Text, View } from 'react-native';
import { Tabs } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';

import { useGrabBasket } from '../../../App';
import { BrandPalette, createShadow } from '@/constants/theme';

const SERVICE_TAB_COPY = {
  food: {
    index: { label: 'Food', icon: 'fast-food-outline' },
    explore: { label: '99 Store', icon: 'storefront-outline' },
    reorder: { label: 'Reorder', icon: 'repeat-outline' },
    account: { label: 'Account', icon: 'person-outline' },
  },
  warehouse: {
    index: { label: 'Instamart', icon: 'basket-outline' },
    explore: { label: 'Categories', icon: 'grid-outline' },
    reorder: { label: 'Reorder', icon: 'repeat-outline' },
    account: { label: 'Account', icon: 'person-outline' },
  },
  eatout: {
    index: { label: 'Dineout', icon: 'restaurant-outline' },
    explore: { label: 'My Corner', icon: 'sparkles-outline' },
    reorder: { label: 'New & Hot', icon: 'flame-outline' },
    account: { label: 'Account', icon: 'person-outline' },
  },
  scenes: {
    index: { label: 'Scenes', icon: 'sparkles-outline' },
    explore: { label: 'Explore', icon: 'compass-outline' },
    reorder: { label: 'Saved', icon: 'bookmark-outline' },
    account: { label: 'Account', icon: 'person-outline' },
  },
};

function getBadgeText(cartCount, orderHistory) {
  if (cartCount > 0) {
    return cartCount > 9 ? '9+' : String(cartCount);
  }

  if (Array.isArray(orderHistory) && orderHistory.length > 0) {
    return '•';
  }

  return '';
}

function TabIcon({ focused, color, icon, label, badge }) {
  return (
    <View style={styles.tabItem}>
      <View style={[styles.iconWrap, focused && styles.iconWrapActive]}>
        <Ionicons name={icon} size={20} color={color} />
        {badge ? (
          <View style={styles.badge}>
            <Text numberOfLines={1} style={styles.badgeText}>
              {badge}
            </Text>
          </View>
        ) : null}
      </View>
      <Text style={[styles.tabLabel, { color }, focused && styles.tabLabelActive]}>{label}</Text>
    </View>
  );
}

export default function TabsLayout() {
  const { activeService, cartCount, orderHistory } = useGrabBasket();

  const copy = SERVICE_TAB_COPY[activeService] || SERVICE_TAB_COPY.food;
  const badge = getBadgeText(cartCount, orderHistory);

  const makeOptions = (name) => ({
    headerShown: false,
    title: copy[name]?.label || name,
    tabBarLabel: () => null,
    tabBarIcon: ({ focused, color }) => (
      <TabIcon
        focused={focused}
        color={color}
        icon={copy[name]?.icon || 'ellipse-outline'}
        label={copy[name]?.label || name}
        badge={name === 'reorder' ? badge : ''}
      />
    ),
  });

  return (
    <Tabs
      screenOptions={{
        headerShown: false,
        sceneStyle: { backgroundColor: BrandPalette.page },
        tabBarHideOnKeyboard: true,
        tabBarActiveTintColor: BrandPalette.primary,
        tabBarInactiveTintColor: '#A08F83',
        tabBarStyle: {
          height: 82,
          paddingTop: 8,
          paddingBottom: Platform.OS === 'ios' ? 20 : 12,
          backgroundColor: BrandPalette.tabBar,
          borderTopColor: BrandPalette.line,
          borderTopWidth: 1,
          ...Platform.select({
            ios: createShadow(0.08, 12, -6),
            android: { elevation: 14 },
            default: {},
          }),
        },
        tabBarItemStyle: {
          paddingVertical: 0,
        },
      }}>
      <Tabs.Screen name="index" options={makeOptions('index')} />
      <Tabs.Screen name="explore" options={makeOptions('explore')} />
      <Tabs.Screen name="reorder" options={makeOptions('reorder')} />
      <Tabs.Screen name="account" options={makeOptions('account')} />
    </Tabs>
  );
}

const styles = StyleSheet.create({
  tabItem: {
    alignItems: 'center',
    justifyContent: 'center',
    gap: 4,
  },
  iconWrap: {
    width: 38,
    height: 38,
    borderRadius: 19,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: 'transparent',
  },
  iconWrapActive: {
    backgroundColor: BrandPalette.primarySoft,
  },
  tabLabel: {
    fontSize: 11,
    lineHeight: 13,
    fontWeight: '700',
  },
  tabLabelActive: {
    fontWeight: '800',
  },
  badge: {
    position: 'absolute',
    right: -3,
    top: -2,
    minWidth: 16,
    height: 16,
    borderRadius: 8,
    paddingHorizontal: 3,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: BrandPalette.primary,
    borderWidth: 1,
    borderColor: BrandPalette.white,
  },
  badgeText: {
    color: BrandPalette.white,
    fontSize: 8,
    lineHeight: 10,
    fontWeight: '900',
  },
});