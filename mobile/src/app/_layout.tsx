import React from 'react';
import { Stack } from 'expo-router';
import { GrabBasketProvider } from '../../App';
import PushNotificationBootstrap from '../components/push-notification-bootstrap';

export default function RootLayout() {
  return (
    <GrabBasketProvider>
      <PushNotificationBootstrap />
      <Stack screenOptions={{ headerShown: false }}>
        <Stack.Screen name="(tabs)" options={{ headerShown: false }} />
        <Stack.Screen name="(delivery)" options={{ headerShown: false }} />
        <Stack.Screen name="(partner)" options={{ headerShown: false }} />
        <Stack.Screen name="store/[vendorId]" options={{ headerShown: false }} />
        <Stack.Screen name="cart" options={{ headerShown: false }} />
      </Stack>
    </GrabBasketProvider>
  );
}