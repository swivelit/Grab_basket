import React from 'react';
import { Platform, StyleSheet, Text, View } from 'react-native';
import { Tabs } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import { AppShellThemes, createShadow } from '@/constants/theme';

function TabItem({ focused, color, icon, label, focusedSurface, focusedBorder, iconSurface }) {
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
            backgroundColor: focused ? iconSurface : 'transparent',
          },
        ]}>
        <Ionicons name={icon} size={19} color={color} />
      </View>
      <Text numberOfLines={1} style={[styles.tabLabel, { color }, focused && styles.tabLabelFocused]}>
        {label}
      </Text>
    </View>
  );
}

export default function AppShellTabs({ shell = 'delivery', screens = [] }) {
  const theme = AppShellThemes[shell] || AppShellThemes.delivery;

  return (
    <Tabs
      screenOptions={{
        headerShown: false,
        lazy: true,
        tabBarHideOnKeyboard: true,
        tabBarActiveTintColor: theme.activeTint,
        tabBarInactiveTintColor: theme.inactiveTint,
        sceneStyle: {
          backgroundColor: theme.page,
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
            ios: {
              shadowColor: theme.shadowColor,
              ...createShadow(0.1, 18, -6),
            },
            android: {
              elevation: 16,
            },
            default: {},
          }),
        },
        tabBarItemStyle: {
          paddingHorizontal: 2,
        },
      }}>
      {screens.map((screen) => (
        <Tabs.Screen
          key={screen.name}
          name={screen.name}
          options={{
            title: screen.label,
            headerShown: false,
            tabBarLabel: () => null,
            tabBarIcon: ({ focused, color }) => (
              <TabItem
                focused={focused}
                color={color}
                icon={screen.icon}
                label={screen.label}
                focusedSurface={theme.focusedSurface}
                focusedBorder={theme.focusedBorder}
                iconSurface={theme.iconSurface}
              />
            ),
          }}
        />
      ))}
    </Tabs>
  );
}

const styles = StyleSheet.create({
  tabItem: {
    minWidth: 78,
    borderRadius: 24,
    borderWidth: 1,
    borderColor: 'transparent',
    paddingHorizontal: 10,
    paddingVertical: 11,
    alignItems: 'center',
    justifyContent: 'center',
    gap: 4,
  },
  iconBubble: {
    width: 38,
    height: 38,
    borderRadius: 19,
    alignItems: 'center',
    justifyContent: 'center',
  },
  tabLabel: {
    fontSize: 11,
    fontWeight: '700',
  },
  tabLabelFocused: {
    fontWeight: '700',
  },
});
