import React from 'react';
import { Platform, StyleSheet, Text, View, useWindowDimensions } from 'react-native';
import { Tabs } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';

import { useGrabBasket } from '../../../App';
import { BrandPalette, createShadow } from '@/constants/theme';

const SERVICE_TAB_COPY = {
  food: {
    index: { label: 'Food', icon: 'fast-food-outline' },
    explore: { label: 'Store', icon: 'storefront-outline' },
    reorder: { label: 'Reorder', icon: 'repeat-outline' },
    account: { label: 'Account', icon: 'person-outline' },
  },
  warehouse: {
    index: { label: 'Warehouse', icon: 'basket-outline' },
    explore: { label: 'Categories', icon: 'grid-outline' },
    reorder: { label: 'Reorder', icon: 'repeat-outline' },
    account: { label: 'Account', icon: 'person-outline' },
  },
  eatout: {
    index: { label: 'Eatout', icon: 'restaurant-outline' },
    explore: { label: 'My Corner', icon: 'sparkles-outline' },
    reorder: { label: 'Reorder', icon: 'repeat-outline' },
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

function TabBarIcon({ focused, color, icon, badge, iconSize, iconWrapSize }) {
  return (
    <View
      style={[
        styles.iconWrap,
        {
          width: iconWrapSize,
          height: iconWrapSize,
          borderRadius: iconWrapSize / 2,
        },
        focused && styles.iconWrapActive,
      ]}>
      <Ionicons name={icon} size={iconSize} color={color} />

      {badge ? (
        <View style={styles.badge}>
          <Text allowFontScaling={false} maxFontSizeMultiplier={1} numberOfLines={1} style={styles.badgeText}>
            {badge}
          </Text>
        </View>
      ) : null}
    </View>
  );
}

function TabBarLabel({ label, color, fontSize }) {
  return (
    <Text
      allowFontScaling={false}
      maxFontSizeMultiplier={1}
      numberOfLines={1}
      adjustsFontSizeToFit
      minimumFontScale={0.75}
      style={[
        styles.tabLabel,
        {
          color,
          fontSize,
          lineHeight: fontSize + 2,
        },
      ]}>
      {label}
    </Text>
  );
}

export default function TabsLayout() {
  const { width } = useWindowDimensions();
  const { activeService, cartCount, orderHistory } = useGrabBasket();

  const copy = SERVICE_TAB_COPY[activeService] || SERVICE_TAB_COPY.food;
  const badge = getBadgeText(cartCount, orderHistory);

  const compactPhone = width <= 360;
  const veryCompactPhone = width <= 340;

  const iconSize = veryCompactPhone ? 18 : 20;
  const iconWrapSize = veryCompactPhone ? 34 : compactPhone ? 36 : 38;
  const labelFontSize = veryCompactPhone ? 9 : compactPhone ? 10 : 11;
  const tabBarHeight = Platform.OS === 'ios' ? (compactPhone ? 86 : 90) : compactPhone ? 72 : 76;
  const bottomPadding = Platform.OS === 'ios' ? 18 : compactPhone ? 8 : 10;
  const topPadding = compactPhone ? 6 : 8;

  const makeOptions = (name) => ({
    headerShown: false,
    title: copy[name]?.label || name,
    tabBarLabel: ({ color }) => (
      <TabBarLabel
        label={copy[name]?.label || name}
        color={color}
        fontSize={labelFontSize}
      />
    ),
    tabBarIcon: ({ focused, color }) => (
      <TabBarIcon
        focused={focused}
        color={color}
        icon={copy[name]?.icon || 'ellipse-outline'}
        badge={name === 'reorder' ? badge : ''}
        iconSize={iconSize}
        iconWrapSize={iconWrapSize}
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
        tabBarShowLabel: true,
        tabBarStyle: {
          height: tabBarHeight,
          paddingTop: topPadding,
          paddingBottom: bottomPadding,
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
          justifyContent: 'center',
          alignItems: 'center',
          paddingHorizontal: 2,
        },
        tabBarIconStyle: {
          marginBottom: 3,
        },
        tabBarLabelStyle: {
          marginTop: 0,
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
  iconWrap: {
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: 'transparent',
    position: 'relative',
  },
  iconWrapActive: {
    backgroundColor: BrandPalette.primarySoft,
  },
  tabLabel: {
    width: '100%',
    textAlign: 'center',
    fontWeight: '700',
    includeFontPadding: false,
    paddingHorizontal: 2,
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
    includeFontPadding: false,
  },
});