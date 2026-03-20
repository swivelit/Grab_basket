const hasMetaCredentials = Boolean(
  process.env.EXPO_PUBLIC_META_APP_ID &&
    process.env.EXPO_PUBLIC_META_CLIENT_TOKEN
);

module.exports = () => ({
  expo: {
    name: 'Grab Basket',
    slug: 'grab-basket-mobile',
    scheme: 'grabbasket',
    version: '1.0.0',
    orientation: 'portrait',
    userInterfaceStyle: 'light',
    experiments: {
      typedRoutes: true,
    },
    assetBundlePatterns: ['**/*'],
    splash: {
      resizeMode: 'contain',
      backgroundColor: '#ffffff',
    },
    web: {
      bundler: 'metro',
    },
    android: {
      package: 'com.grabbasket.mobile',
      versionCode: 1,
    },
    ios: {
      bundleIdentifier: 'com.grabbasket.mobile',
      supportsTablet: true,
    },
    plugins: [
      'expo-router',
      ...(hasMetaCredentials
        ? [
            [
              'react-native-fbsdk-next',
              {
                appID: process.env.EXPO_PUBLIC_META_APP_ID,
                clientToken: process.env.EXPO_PUBLIC_META_CLIENT_TOKEN,
                displayName: 'Grab Basket',
                scheme: `fb${process.env.EXPO_PUBLIC_META_APP_ID}`,
                advertiserIDCollectionEnabled: true,
                autoLogAppEventsEnabled: true,
                isAutoInitEnabled: true,
                iosUserTrackingPermission:
                  'We use this identifier to improve attribution and personalize relevant sponsored content.',
              },
            ],
          ]
        : []),
    ],
  },
});