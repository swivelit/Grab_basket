import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { Stack } from 'expo-router';

import { GrabBasketProvider } from '../../App';

class StartupErrorBoundary extends React.Component<
  React.PropsWithChildren,
  { errorMessage: string | null }
> {
  constructor(props: React.PropsWithChildren) {
    super(props);
    this.state = { errorMessage: null };
  }

  static getDerivedStateFromError(error: Error) {
    return {
      errorMessage: error?.message || 'Unknown startup error',
    };
  }

  componentDidCatch() {
    // Keep this intentionally quiet for now while we stabilize startup.
  }

  render() {
    if (this.state.errorMessage) {
      return (
        <View style={styles.errorBoundaryScreen}>
          <Text style={styles.errorBoundaryTitle}>Grab Basket failed to start</Text>
          <Text style={styles.errorBoundaryBody}>
            Please rebuild the app after applying the startup fix.
          </Text>
          <Text style={styles.errorBoundaryDetails}>{this.state.errorMessage}</Text>
        </View>
      );
    }

    return this.props.children;
  }
}

function RootLayout() {
  return (
    <StartupErrorBoundary>
      <GrabBasketProvider>
        <Stack screenOptions={{ headerShown: false }}>
          <Stack.Screen name="index" options={{ headerShown: false }} />
          <Stack.Screen name="(tabs)" options={{ headerShown: false }} />
          <Stack.Screen name="store/[vendorId]" options={{ headerShown: false }} />
          <Stack.Screen name="cart" options={{ headerShown: false }} />
        </Stack>
      </GrabBasketProvider>
    </StartupErrorBoundary>
  );
}

const styles = StyleSheet.create({
  errorBoundaryBody: {
    color: '#5b4639',
    fontSize: 15,
    lineHeight: 22,
    textAlign: 'center',
  },
  errorBoundaryDetails: {
    color: '#8a5a44',
    fontSize: 13,
    lineHeight: 20,
    textAlign: 'center',
  },
  errorBoundaryScreen: {
    alignItems: 'center',
    backgroundColor: '#fff7f1',
    flex: 1,
    gap: 12,
    justifyContent: 'center',
    paddingHorizontal: 24,
  },
  errorBoundaryTitle: {
    color: '#24150d',
    fontSize: 22,
    fontWeight: '700',
    textAlign: 'center',
  },
});

export default RootLayout;