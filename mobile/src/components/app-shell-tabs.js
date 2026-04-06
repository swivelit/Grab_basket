import React from 'react';
import { Platform, StyleSheet, Text, View, useWindowDimensions } from 'react-native';
import { Tabs } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';

import { AppShellThemes, createShadow } from '@/constants/theme';

function TabBarIcon({ focused, color, icon, iconSize, iconWrapSize, theme }) {
  return (
    <View
      style={[
        styles.iconWrap,
        {
          width: iconWrapSize,
          height: iconWrapSize,
          borderRadius: iconWrapSize / 2,
        },
        { backgroundColor: focused ? theme.focusedSurface : theme.iconSurface, borderColor: focused ? theme.focusedBorder : 'transparent' },
      ]}>
      <Ionicons name={icon} size={iconSize} color={color} />
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

export default function AppShellTabs({ shell = 'delivery', screens = [] }) {
  const { width } = useWindowDimensions();
  const theme = AppShellThemes[shell] || AppShellThemes.delivery;

  const compactPhone = width <= 360;
  const veryCompactPhone = width <= 340;

  const iconSize = veryCompactPhone ? 18 : 20;
  const iconWrapSize = veryCompactPhone ? 34 : compactPhone ? 36 : 38;
  const labelFontSize = veryCompactPhone ? 9 : compactPhone ? 10 : 11;
  const tabBarHeight = Platform.OS === 'ios' ? (compactPhone ? 86 : 90) : compactPhone ? 72 : 76;
  const bottomPadding = Platform.OS === 'ios' ? 18 : compactPhone ? 8 : 10;
  const topPadding = compactPhone ? 6 : 8;

  return (
    <Tabs
      screenOptions={{
        headerShown: false,
        lazy: true,
        sceneStyle: { backgroundColor: theme.page },
        tabBarHideOnKeyboard: true,
        tabBarActiveTintColor: theme.activeTint,
        tabBarInactiveTintColor: theme.inactiveTint,
        tabBarShowLabel: true,
        tabBarStyle: {
          height: tabBarHeight,
          paddingTop: topPadding,
          paddingBottom: bottomPadding,
          backgroundColor: theme.barBackground,
          borderTopColor: theme.barBorder,
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
      {screens.map((screen) => (
        <Tabs.Screen
          key={screen.name}
          name={screen.name}
          options={{
            headerShown: false,
            title: screen.label,
            tabBarLabel: ({ color }) => (
              <TabBarLabel label={screen.label} color={color} fontSize={labelFontSize} />
            ),
            tabBarIcon: ({ focused, color }) => (
              <TabBarIcon
                focused={focused}
                color={color}
                icon={screen.icon}
                iconSize={iconSize}
                iconWrapSize={iconWrapSize}
                theme={theme}
              />
            ),
          }}
        />
      ))}
    </Tabs>
  );
}

const styles = StyleSheet.create({
  iconWrap: {
    alignItems: 'center',
    justifyContent: 'center',
    position: 'relative',
    borderWidth: 1,
  },
  tabLabel: {
    width: '100%',
    textAlign: 'center',
    fontWeight: '700',
    includeFontPadding: false,
    paddingHorizontal: 2,
  },
});
