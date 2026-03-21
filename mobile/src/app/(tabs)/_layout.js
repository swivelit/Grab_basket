import React, { useMemo } from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { Tabs } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import { useGrabBasket } from '../../../App';

const TAB_THEMES = {
  food: {
    activeTint: '#ff6d00',
    inactiveTint: '#7f8798',
    background: '#ffffff',
    border: '#eceff5',
    shadowOpacity: 0.08,
    focusedSurface: '#fff1e7',
    focusedBorder: '#ffd9bf',
    titleMap: {
      index: 'Food',
      explore: 'Gourmet',
      reorder: 'Reorder',
      account: 'Account',
    },
    iconMap: {
      index: 'fast-food-outline',
      explore: 'restaurant-outline',
      reorder: 'reload-outline',
      account: 'person-outline',
    },
  },
  warehouse: {
    activeTint: '#0b57d0',
    inactiveTint: '#7f8798',
    background: '#ffffff',
    border: '#e4ecf9',
    shadowOpacity: 0.08,
    focusedSurface: '#edf4ff',
    focusedBorder: '#cfe0ff',
    titleMap: {
      index: 'Instamart',
      explore: 'Categories',
      reorder: 'Reorder',
      account: 'Account',
    },
    iconMap: {
      index: 'basket-outline',
      explore: 'grid-outline',
      reorder: 'reload-outline',
      account: 'person-outline',
    },
  },
  eatout: {
    activeTint: '#ff7a00',
    inactiveTint: '#7f8798',
    background: '#ffffff',
    border: '#efe7fb',
    shadowOpacity: 0.08,
    focusedSurface: '#fff3e8',
    focusedBorder: '#ffd6b3',
    titleMap: {
      index: 'Dineout',
      explore: 'My corner',
      reorder: 'New & Hot',
      account: 'Account',
    },
    iconMap: {
      index: 'restaurant-outline',
      explore: 'person-circle-outline',
      reorder: 'flame-outline',
      account: 'person-outline',
    },
  },
  scenes: {
    activeTint: '#ffffff',
    inactiveTint: '#7f8aa3',
    background: '#050816',
    border: '#10182b',
    shadowOpacity: 0.2,
    focusedSurface: 'rgba(255,255,255,0.10)',
    focusedBorder: 'rgba(255,255,255,0.14)',
    titleMap: {
      index: 'Scenes',
      explore: 'Explore',
      reorder: 'Saved',
      account: 'Account',
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

function TabPill({
  icon,
  label,
  color,
  focused,
  badge,
  focusedSurface,
  focusedBorder,
  dark,
}) {
  return (
    <View
      style={[
        styles.tabPill,
        focused && {
          backgroundColor: focusedSurface,
          borderColor: focusedBorder,
        },
      ]}>
      <View style={styles.iconWrap}>
        <Ionicons name={icon} size={20} color={color} />
        {badge ? (
          <View style={[styles.badge, dark && styles.badgeDark]}>
            <Text style={styles.badgeText} numberOfLines={1}>
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

  const orderCount = getOrderCount(orderHistory);
  const isDark = activeService === 'scenes';

  const getBadgeForRoute = (routeName) => {
    if (routeName === 'reorder') {
      if (cartCount > 0) return String(Math.min(cartCount, 9));
      if (orderCount > 0 && activeService === 'scenes') return '•';
    }
    return null;
  };

  const makeOptions = (routeName) => ({
    title: theme.titleMap[routeName],
    tabBarLabel: () => null,
    tabBarIcon: ({ focused, color }) => (
      <TabPill
        focused={focused}
        color={color}
        icon={theme.iconMap[routeName]}
        label={theme.titleMap[routeName]}
        badge={getBadgeForRoute(routeName)}
        focusedSurface={theme.focusedSurface}
        focusedBorder={theme.focusedBorder}
        dark={isDark}
      />
    ),
  });

  return (
    <Tabs
      screenOptions={{
        headerShown: false,
        tabBarHideOnKeyboard: true,
        tabBarActiveTintColor: theme.activeTint,
        tabBarInactiveTintColor: theme.inactiveTint,
        tabBarStyle: {
          height: 82,
          backgroundColor: theme.background,
          borderTopColor: theme.border,
          borderTopWidth: 1,
          paddingTop: 10,
          paddingBottom: 12,
          shadowColor: '#0f172a',
          shadowOpacity: theme.shadowOpacity,
          shadowRadius: 18,
          shadowOffset: { width: 0, height: -6 },
          elevation: 16,
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
  tabPill: {
    minWidth: 82,
    maxWidth: 92,
    minHeight: 54,
    borderRadius: 18,
    borderWidth: 1,
    borderColor: 'transparent',
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 10,
    paddingVertical: 8,
  },
  iconWrap: {
    position: 'relative',
    minHeight: 22,
    justifyContent: 'center',
    alignItems: 'center',
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
    top: -8,
    right: -14,
    minWidth: 16,
    height: 16,
    paddingHorizontal: 4,
    borderRadius: 8,
    backgroundColor: '#ff4d4f',
    alignItems: 'center',
    justifyContent: 'center',
  },
  badgeDark: {
    backgroundColor: '#ffffff',
  },
  badgeText: {
    color: '#ffffff',
    fontSize: 9,
    fontWeight: '900',
    lineHeight: 10,
  },
});