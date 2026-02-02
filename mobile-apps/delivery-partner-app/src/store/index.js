import {configureStore} from '@reduxjs/toolkit';
import {persistStore, persistReducer} from 'redux-persist';
import AsyncStorage from '@react-native-async-storage/async-storage';
import {combineReducers} from '@reduxjs/toolkit';

import authSlice from './slices/authSlice';
import deliverySlice from './slices/deliverySlice';
import orderSlice from './slices/orderSlice';
import earningsSlice from './slices/earningsSlice';
import locationSlice from './slices/locationSlice';

const persistConfig = {
  key: 'root',
  storage: AsyncStorage,
  whitelist: ['auth', 'location'],
};

const rootReducer = combineReducers({
  auth: authSlice,
  delivery: deliverySlice,
  orders: orderSlice,
  earnings: earningsSlice,
  location: locationSlice,
});

const persistedReducer = persistReducer(persistConfig, rootReducer);

export const store = configureStore({
  reducer: persistedReducer,
  middleware: getDefaultMiddleware =>
    getDefaultMiddleware({
      serializableCheck: {
        ignoredActions: ['persist/PERSIST'],
      },
    }),
});

export const persistor = persistStore(store);