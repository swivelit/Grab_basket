import React, { useMemo } from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { Tabs } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import { useGrabBasket } from '../../../App';

const TAB_CONFIG = {
  food: {
    activeTint: '#ff6d00',
    inactiveTint: '#8b8f9c',
    background: '#ffffff',
    border: '#eceef3',
    shadowOpacity: 0.06,
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
  },
  warehouse: {
    activeTint: '#0b3d91',
    inactiveTint: '#8b8f9c',
    background: '#ffffff',
    border: '#e7edf8',
    shadowOpacity: 0.06,
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
    activeTint: '#f97316',
    inactiveTint: '#8b8f9c',
    background: '#ffffff',
    border: '#f1e9ff',
    shadowOpacity: 0.06,
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
  },
  scenes: {
    activeTint: '#ffffff',
    inactiveTint: '#6b7280',
    background: '#050816',
    border: '#101828',
    shadowOpacity: 0.18,
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

function TabItem({ icon, label, color, focused }) {
  return (
    <View style={styles.tabItem}>
      <Ionicons name={icon} size={22} color={color} />
      <Text style={[styles.tabLabel, { color }, focused && styles.tabLabelFocused]} numberOfLines={1}>
        {label}
      </Text>
    </View>
  );
}

export default function TabsLayout() {
  const { activeService } = useGrabBasket();

  const config = useMemo(() => TAB_CONFIG[activeService] || TAB_CONFIG.food, [activeService]);

  const makeOptions = (routeName) => ({
    title: config.titleMap[routeName],
    tabBarLabel: () => null,
    tabBarIcon: ({ focused, color }) => (
      <TabItem
        focused={focused}
        color={color}
        icon={config.iconMap[routeName]}
        label={config.titleMap[routeName]}
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
          height: 72,
          backgroundColor: config.background,
          borderTopColor: config.border,
          borderTopWidth: 1,
          paddingTop: 8,
          paddingBottom: 8,
          shadowColor: '#0f172a',
          shadowOpacity: config.shadowOpacity,
          shadowRadius: 18,
          shadowOffset: { width: 0, height: -6 },
          elevation: 12,
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
    width: 82,
    alignItems: 'center',
    justifyContent: 'center',
  },
  tabLabel: {
    marginTop: 4,
    fontSize: 11,
    fontWeight: '700',
    textAlign: 'center',
  },
  tabLabelFocused: {
    fontWeight: '900',
  },
});