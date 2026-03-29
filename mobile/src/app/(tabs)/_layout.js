import React from 'react';
import { Platform, StyleSheet, Text, View } from 'react-native';
import { Tabs } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';

import { useGrabBasket } from '../../../App';
import { BrandPalette, createShadow } from '@/constants/theme';

const TAB_CONFIG = {
  food: {
    sceneBackground: '#F8F8F8',
    barBackground: 'rgba(255,255,255,0.98)',
    barBorder: '#ECECEC',
    activeTint: '#FF5A00',
    inactiveTint: '#A1A1AA',
    activeSurface: '#FFF1E5',
    activeBorder: '#FFD5BD',
    iconSurface: '#FFF8F2',
    shadowColor: 'rgba(17,17,17,0.14)',
    titleMap: {
      index: 'Food',
      explore: '99 store',
      reorder: 'Reorder',
      account: 'Profile',
    },
    iconMap: {
      index: 'fast-food-outline',
      explore: 'storefront-outline',
      reorder: 'refresh-outline',
      account: 'person-outline',
    },
  },
  warehouse: {
    sceneBackground: '#EEF4FF',
    barBackground: 'rgba(255,255,255,0.98)',
    barBorder: '#E6EBF5',
    activeTint: BrandPalette.primary,
    inactiveTint: '#9398A3',
    activeSurface: '#FFF1E8',
    activeBorder: '#F0D3C4',
    iconSurface: '#FFF8F3',
    shadowColor: 'rgba(17,17,17,0.14)',
    titleMap: {
      index: 'Mart',
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
    sceneBackground: '#EAF9F5',
    barBackground: 'rgba(255,255,255,0.98)',
    barBorder: '#E2EEE9',
    activeTint: BrandPalette.primary,
    inactiveTint: '#93A09B',
    activeSurface: '#FFF1E8',
    activeBorder: '#EDD4C7',
    iconSurface: '#FFF8F3',
    shadowColor: 'rgba(17,17,17,0.14)',
    titleMap: {
      index: 'Dineout',
      explore: 'My corner',
      reorder: 'New & Hot',
      account: 'Profile',
    },
    iconMap: {
      index: 'restaurant-outline',
      explore: 'sparkles-outline',
      reorder: 'flame-outline',
      account: 'person-outline',
    },
  },
  scenes: {
    sceneBackground: '#FFF4ED',
    barBackground: 'rgba(255,255,255,0.98)',
    barBorder: '#ECE2D7',
    activeTint: BrandPalette.primary,
    inactiveTint: '#A38F80',
    activeSurface: '#FFF1E8',
    activeBorder: '#F3D7C0',
    iconSurface: '#FFF8F3',
    shadowColor: 'rgba(17,17,17,0.14)',
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

function getReorderBadge(cartCount, orderHistory = []) {
  if (cartCount > 0) {
    return cartCount > 9 ? '9+' : String(cartCount);
  }

  if (Array.isArray(orderHistory) && orderHistory.length > 0) {
    return '•';
  }

  return '';
}

function TabBarItem({ focused, color, icon, label, badge, theme }) {
  return (
    <View
      style={[
        styles.tabItem,
        focused && {
          backgroundColor: theme.activeSurface,
          borderColor: theme.activeBorder,
        },
      ]}>
      <View
        style={[
          styles.iconBubble,
          focused && {
            backgroundColor: theme.iconSurface,
          },
        ]}>
        <Ionicons name={icon} size={20} color={color} />

        {badge ? (
          <View style={styles.badge}>
            <Text numberOfLines={1} style={styles.badgeText}>
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

  const theme = TAB_CONFIG[activeService] || TAB_CONFIG.food;
  const reorderBadge = getReorderBadge(cartCount, orderHistory);

  const makeOptions = (routeName) => ({
    headerShown: false,
    title: theme.titleMap[routeName],
    tabBarLabel: () => null,
    tabBarIcon: ({ focused, color }) => (
      <TabBarItem
        focused={focused}
        color={color}
        icon={theme.iconMap[routeName]}
        label={theme.titleMap[routeName]}
        badge={routeName === 'reorder' ? reorderBadge : ''}
        theme={theme}
      />
    ),
  });

  return (
    <Tabs
      screenOptions={{
        headerShown: false,
        lazy: true,
        tabBarHideOnKeyboard: true,
        sceneStyle: {
          backgroundColor: theme.sceneBackground,
        },
        tabBarActiveTintColor: theme.activeTint,
        tabBarInactiveTintColor: theme.inactiveTint,
        tabBarStyle: {
          height: 96,
          paddingTop: 10,
          paddingBottom: 14,
          paddingHorizontal: 14,
          backgroundColor: theme.barBackground,
          borderTopWidth: 1,
          borderTopColor: theme.barBorder,
          borderTopLeftRadius: 28,
          borderTopRightRadius: 28,
          ...Platform.select({
            ios: {
              ...createShadow(0.12, 18, -6),
              shadowColor: theme.shadowColor,
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
    minWidth: 78,
    minHeight: 58,
    borderRadius: 22,
    borderWidth: 1,
    borderColor: 'transparent',
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 8,
    paddingVertical: 7,
  },
  iconBubble: {
    width: 36,
    height: 36,
    borderRadius: 18,
    alignItems: 'center',
    justifyContent: 'center',
    position: 'relative',
  },
  tabLabel: {
    marginTop: 4,
    fontSize: 10,
    lineHeight: 12,
    fontWeight: '800',
    textAlign: 'center',
  },
  tabLabelFocused: {
    fontWeight: '900',
  },
  badge: {
    position: 'absolute',
    top: -4,
    right: -6,
    minWidth: 17,
    height: 17,
    borderRadius: 8.5,
    paddingHorizontal: 4,
    backgroundColor: BrandPalette.primary,
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.8)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  badgeText: {
    color: BrandPalette.white,
    fontSize: 8,
    lineHeight: 10,
    fontWeight: '900',
  },
});