import React, { useMemo } from 'react';
import { Platform, StyleSheet, Text, View } from 'react-native';
import { Tabs } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import { useGrabBasket } from '../../../App';
import { BrandPalette, ConsumerTabThemes, createShadow } from '@/constants/theme';

function getOrderCount(orderHistory = []) {
  return Array.isArray(orderHistory) ? orderHistory.length : 0;
}

function getBadgeForRoute(routeName, activeService, cartCount, orderCount) {
  if (routeName !== 'reorder') return null;

  if (cartCount > 0) {
    return cartCount > 9 ? '9+' : String(cartCount);
  }

  if (activeService === 'scenes' && orderCount > 0) {
    return '•';
  }

  return null;
}

function TabItem({
  focused,
  color,
  icon,
  label,
  badge,
  dark,
  focusedSurface,
  focusedBorder,
  iconSurface,
}) {
  return (
    <View
      style={[
        styles.tabItem,
        focused && {
          backgroundColor: focusedSurface,
          borderColor: focusedBorder,
        },
      ]}>
      <View
        style={[
          styles.iconBubble,
          {
            backgroundColor: focused
              ? dark
                ? '#31261D'
                : iconSurface
              : 'transparent',
          },
        ]}>
        <Ionicons name={icon} size={19} color={color} />

        {badge ? (
          <View style={[styles.badge, dark && styles.badgeDark]}>
            <Text style={[styles.badgeText, dark && styles.badgeTextDark]} numberOfLines={1}>
              {badge}
            </Text>
          </View>
        ) : null}
      </View>

      <Text
        numberOfLines={1}
        style={[
          styles.tabLabel,
          { color },
          focused && styles.tabLabelFocused,
        ]}>
        {label}
      </Text>
    </View>
  );
}

export default function TabsLayout() {
  const { activeService, cartCount, orderHistory } = useGrabBasket();

  const theme = useMemo(
    () => ConsumerTabThemes[activeService] || ConsumerTabThemes.food,
    [activeService]
  );

  const isDark = activeService === 'scenes';
  const orderCount = getOrderCount(orderHistory);

  const makeOptions = (routeName) => ({
    title: theme.titleMap[routeName],
    headerShown: false,
    tabBarLabel: () => null,
    tabBarIcon: ({ focused, color }) => (
      <TabItem
        focused={focused}
        color={color}
        icon={theme.iconMap[routeName]}
        label={theme.titleMap[routeName]}
        badge={getBadgeForRoute(routeName, activeService, cartCount, orderCount)}
        dark={isDark}
        focusedSurface={theme.focusedSurface}
        focusedBorder={theme.focusedBorder}
        iconSurface={theme.iconSurface}
      />
    ),
  });

  return (
    <Tabs
      screenOptions={{
        headerShown: false,
        lazy: true,
        tabBarHideOnKeyboard: true,
        tabBarActiveTintColor: theme.activeTint,
        tabBarInactiveTintColor: theme.inactiveTint,
        sceneStyle: {
          backgroundColor: theme.sceneBackground,
        },
        tabBarStyle: {
          height: 98,
          paddingTop: 12,
          paddingBottom: 18,
          paddingHorizontal: 14,
          borderTopLeftRadius: 32,
          borderTopRightRadius: 32,
          backgroundColor: theme.barBackground,
          borderTopWidth: 1,
          borderTopColor: theme.barBorder,
          ...Platform.select({
            ios: { ...createShadow(isDark ? 0.26 : 0.12, 20, -6), shadowColor: theme.shadowColor },
            android: { elevation: 18 },
            default: {},
          }),
        },
        tabBarItemStyle: {
          paddingHorizontal: 2,
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
    minWidth: 84,
    maxWidth: 98,
    minHeight: 64,
    borderRadius: 24,
    borderWidth: 1,
    borderColor: 'transparent',
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 9,
    paddingVertical: 8,
  },
  iconBubble: {
    minWidth: 38,
    height: 38,
    borderRadius: 19,
    alignItems: 'center',
    justifyContent: 'center',
    position: 'relative',
    paddingHorizontal: 8,
  },
  tabLabel: {
    marginTop: 5,
    fontSize: 11,
    fontWeight: '700',
    textAlign: 'center',
  },
  tabLabelFocused: {
    fontWeight: '900',
  },
  badge: {
    position: 'absolute',
    top: -5,
    right: -8,
    minWidth: 17,
    height: 17,
    borderRadius: 8.5,
    paddingHorizontal: 4,
    backgroundColor: BrandPalette.primary,
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.55)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  badgeDark: {
    backgroundColor: BrandPalette.peach200,
  },
  badgeText: {
    color: BrandPalette.white,
    fontSize: 8,
    fontWeight: '900',
    lineHeight: 10,
  },
  badgeTextDark: {
    color: BrandPalette.text,
  },
});
