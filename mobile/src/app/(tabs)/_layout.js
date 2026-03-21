import React, { useMemo } from 'react';
import { Platform, StyleSheet, Text, View } from 'react-native';
import { Tabs } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import { useGrabBasket } from '../../../App';

const PALETTE = {
  peach50: '#FFF8EF',
  peach100: '#FFF2DE',
  peach200: '#FFE5B4',
  peach300: '#FFD7A1',
  peach400: '#F3C38F',
  peach500: '#E6A777',
  peach600: '#D9895E',
  text: '#2F241C',
  muted: '#7B6858',
  subtle: '#A18B79',
  line: '#F0DDCA',
  white: '#FFFFFF',

  darkBg: '#15100C',
  darkSurface: '#1F1813',
  darkSurfaceAlt: '#2A211A',
  darkBorder: '#413226',
  darkText: '#FFF6ED',
  darkMuted: '#D7C2B0',
};

const TAB_THEMES = {
  food: {
    activeTint: PALETTE.peach600,
    inactiveTint: PALETTE.subtle,
    barBackground: 'rgba(255,255,255,0.98)',
    barBorder: PALETTE.line,
    shadowColor: '#9A6A48',
    focusedSurface: PALETTE.peach50,
    focusedBorder: '#F4D4B6',
    iconSurface: '#FFF4E7',
    titleMap: {
      index: 'Food',
      explore: 'Gourmet',
      reorder: 'Reorder',
      account: 'Profile',
    },
    iconMap: {
      index: 'fast-food-outline',
      explore: 'restaurant-outline',
      reorder: 'reload-outline',
      account: 'person-outline',
    },
  },
  warehouse: {
    activeTint: PALETTE.peach600,
    inactiveTint: PALETTE.subtle,
    barBackground: 'rgba(255,255,255,0.98)',
    barBorder: PALETTE.line,
    shadowColor: '#9A6A48',
    focusedSurface: '#FFF6EA',
    focusedBorder: '#F2D7BE',
    iconSurface: '#FFF1E1',
    titleMap: {
      index: 'Instamart',
      explore: 'Categories',
      reorder: 'Reorder',
      account: 'Profile',
    },
    iconMap: {
      index: 'basket-outline',
      explore: 'grid-outline',
      reorder: 'reload-outline',
      account: 'person-outline',
    },
  },
  eatout: {
    activeTint: PALETTE.peach600,
    inactiveTint: PALETTE.subtle,
    barBackground: 'rgba(255,255,255,0.98)',
    barBorder: PALETTE.line,
    shadowColor: '#9A6A48',
    focusedSurface: '#FFF5EA',
    focusedBorder: '#F2D8C0',
    iconSurface: '#FFF1E5',
    titleMap: {
      index: 'Dineout',
      explore: 'My corner',
      reorder: 'New & Hot',
      account: 'Profile',
    },
    iconMap: {
      index: 'restaurant-outline',
      explore: 'person-circle-outline',
      reorder: 'flame-outline',
      account: 'person-outline',
    },
  },
  scenes: {
    activeTint: PALETTE.darkText,
    inactiveTint: '#A79383',
    barBackground: 'rgba(22,17,13,0.98)',
    barBorder: PALETTE.darkBorder,
    shadowColor: '#000000',
    focusedSurface: PALETTE.darkSurfaceAlt,
    focusedBorder: '#5B4738',
    iconSurface: '#2A211A',
    titleMap: {
      index: 'Scenes',
      explore: 'Explore',
      reorder: 'Saved',
      account: 'Profile',
    },
    iconMap: {
      index: 'sparkles-outline',
      explore: 'compass-outline',
      reorder: 'bookmark-outline',
      account: 'person-outline',
    },
  },
};

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
    () => TAB_THEMES[activeService] || TAB_THEMES.food,
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
          backgroundColor: isDark ? PALETTE.darkBg : PALETTE.peach50,
        },
        tabBarStyle: {
          height: 88,
          paddingTop: 10,
          paddingBottom: 14,
          backgroundColor: theme.barBackground,
          borderTopWidth: 1,
          borderTopColor: theme.barBorder,
          ...Platform.select({
            ios: {
              shadowColor: theme.shadowColor,
              shadowOpacity: isDark ? 0.28 : 0.12,
              shadowRadius: 18,
              shadowOffset: { width: 0, height: -6 },
            },
            android: {
              elevation: 18,
            },
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
    minWidth: 80,
    maxWidth: 94,
    minHeight: 58,
    borderRadius: 20,
    borderWidth: 1,
    borderColor: 'transparent',
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 8,
    paddingVertical: 7,
  },
  iconBubble: {
    minWidth: 34,
    height: 34,
    borderRadius: 17,
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
    minWidth: 16,
    height: 16,
    borderRadius: 8,
    paddingHorizontal: 4,
    backgroundColor: PALETTE.peach600,
    alignItems: 'center',
    justifyContent: 'center',
  },
  badgeDark: {
    backgroundColor: PALETTE.peach300,
  },
  badgeText: {
    color: PALETTE.white,
    fontSize: 8,
    fontWeight: '900',
    lineHeight: 10,
  },
  badgeTextDark: {
    color: PALETTE.text,
  },
});