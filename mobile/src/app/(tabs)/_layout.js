import React, { useMemo } from 'react';
import { View, Text, StyleSheet } from 'react-native';
import { Tabs } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import { useGrabBasket } from '../../../App';

const SERVICE_TAB_CONFIG = {
  food: {
    activeTint: '#ff6b00',
    inactiveTint: '#8b8f9c',
    barBg: '#ffffff',
    barBorder: '#eceef3',
    titleMap: {
      index: 'Food',
      explore: 'Gourmet',
      reorder: 'Reorder',
      account: 'EatRight',
    },
    iconMap: {
      index: 'fast-food-outline',
      explore: 'restaurant-outline',
      reorder: 'reload-outline',
      account: 'leaf-outline',
    },
    accentBg: '#fff1e7',
  },
  warehouse: {
    activeTint: '#0b3d91',
    inactiveTint: '#8b8f9c',
    barBg: '#ffffff',
    barBorder: '#e7edf8',
    titleMap: {
      index: 'QuickMart',
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
    accentBg: '#eaf2ff',
  },
  eatout: {
    activeTint: '#f97316',
    inactiveTint: '#8b8f9c',
    barBg: '#ffffff',
    barBorder: '#f1e9ff',
    titleMap: {
      index: 'Dineout',
      explore: 'My corner',
      reorder: 'New & Hot',
      account: 'Profile',
    },
    iconMap: {
      index: 'wine-outline',
      explore: 'sparkles-outline',
      reorder: 'flame-outline',
      account: 'person-outline',
    },
    accentBg: '#fff3ea',
  },
  scenes: {
    activeTint: '#ffffff',
    inactiveTint: '#7b8497',
    barBg: '#0f172a',
    barBorder: '#1e293b',
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
    accentBg: '#1f2937',
  },
};

function TabItemIcon({ focused, color, size, icon, label, accentBg }) {
  return (
    <View style={styles.iconWrap}>
      <View style={[styles.iconBubble, focused && { backgroundColor: accentBg }]}>
        <Ionicons name={icon} color={color} size={size} />
      </View>
      <Text style={[styles.iconLabel, { color }]} numberOfLines={1}>
        {label}
      </Text>
    </View>
  );
}

export default function TabsLayout() {
  const { activeService } = useGrabBasket();

  const config = useMemo(
    () => SERVICE_TAB_CONFIG[activeService] || SERVICE_TAB_CONFIG.food,
    [activeService]
  );

  const makeOptions = (routeName) => ({
    title: config.titleMap[routeName],
    tabBarLabel: () => null,
    tabBarIcon: ({ focused, color, size }) => (
      <TabItemIcon
        focused={focused}
        color={color}
        size={size}
        icon={config.iconMap[routeName]}
        label={config.titleMap[routeName]}
        accentBg={config.accentBg}
      />
    ),
  });

  return (
    <Tabs
      screenOptions={{
        headerShown: false,
        tabBarActiveTintColor: config.activeTint,
        tabBarInactiveTintColor: config.inactiveTint,
        tabBarHideOnKeyboard: true,
        tabBarStyle: {
          position: 'absolute',
          left: 14,
          right: 14,
          bottom: 10,
          height: 76,
          borderRadius: 26,
          backgroundColor: config.barBg,
          borderTopWidth: 1,
          borderTopColor: config.barBorder,
          paddingTop: 10,
          paddingBottom: 8,
          paddingHorizontal: 6,
          elevation: 10,
          shadowColor: '#0f172a',
          shadowOpacity: activeService === 'scenes' ? 0.18 : 0.08,
          shadowRadius: 18,
          shadowOffset: { width: 0, height: 8 },
        },
        tabBarItemStyle: {
          justifyContent: 'center',
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
    width: 88,
    alignItems: 'center',
    justifyContent: 'center',
  },
  iconBubble: {
    width: 36,
    height: 36,
    borderRadius: 18,
    alignItems: 'center',
    justifyContent: 'center',
  },
  iconLabel: {
    marginTop: 4,
    fontSize: 11,
    fontWeight: '800',
    letterSpacing: 0.1,
    textAlign: 'center',
  },
});