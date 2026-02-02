import React from 'react';
import {NavigationContainer} from '@react-navigation/native';
import {Provider} from 'react-redux';
import {PersistGate} from 'redux-persist/integration/react';
import {Provider as PaperProvider} from 'react-native-paper';
import {SafeAreaProvider} from 'react-native-safe-area-context';
import {enableScreens} from 'react-native-screens';

import {store, persistor} from './store';
import AppNavigator from './navigation/AppNavigator';
import theme from './config/theme';
import SplashScreen from './screens/SplashScreen';
import LocationService from './services/LocationService';
import NotificationService from './services/NotificationService';

enableScreens();

const App = () => {
  React.useEffect(() => {
    // Initialize services
    LocationService.initialize();
    NotificationService.initialize();
  }, []);

  return (
    <Provider store={store}>
      <PersistGate loading={<SplashScreen />} persistor={persistor}>
        <PaperProvider theme={theme}>
          <SafeAreaProvider>
            <NavigationContainer>
              <AppNavigator />
            </NavigationContainer>
          </SafeAreaProvider>
        </PaperProvider>
      </PersistGate>
    </Provider>
  );
};

export default App;