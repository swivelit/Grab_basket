import React from 'react';
import { Platform, StyleSheet, Text, View } from 'react-native';
import { Tabs } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';

const SHELL_THEME = {
  delivery: {
    page: '#F6F9FC',
    barBackground: '#FFFFFF',
    barBorder: '#DCE7F4',
    activeTint: '#1463FF',
    inactiveTint: '#7E8AA0',
    focusedSurface: '#EAF2FF',
    focusedBorder: '#C6D9FF',
    iconSurface: '#DDE9FF',
    shadowColor: '#0F172A',
  },
  partner: {
    page: '#FFF9F3',
    barBackground: '#FFFFFF',
    barBorder: '#F0DDCA',
    activeTint: '#D97651',
    inactiveTint: '#8A7766',
    focusedSurface: '#FFF3E8',
    focusedBorder: '#F3D6BF',
    iconSurface: '#FFEADB',
    shadowColor: '#7C4A2D',
  },
};

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
  const theme = SHELL_THEME[shell] || SHELL_THEME.delivery;

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
          height: 88,
          paddingTop: 10,
          paddingBottom: 14,
          backgroundColor: theme.barBackground,
          borderTopWidth: 1,
          borderTopColor: theme.barBorder,
          ...Platform.select({
            ios: {
              shadowColor: theme.shadowColor,
              shadowOpacity: 0.08,
              shadowRadius: 18,
              shadowOffset: { width: 0, height: -6 },
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
    minWidth: 74,
    borderRadius: 18,
    borderWidth: 1,
    borderColor: 'transparent',
    paddingHorizontal: 10,
    paddingVertical: 8,
    alignItems: 'center',
    justifyContent: 'center',
    gap: 4,
  },
  iconBubble: {
    width: 32,
    height: 32,
    borderRadius: 16,
    alignItems: 'center',
    justifyContent: 'center',
  },
  tabLabel: {
    fontSize: 11,
    fontWeight: '600',
  },
  tabLabelFocused: {
    fontWeight: '700',
  },
});