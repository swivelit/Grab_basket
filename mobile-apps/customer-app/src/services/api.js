import axios from 'axios';
import AsyncStorage from '@react-native-async-storage/async-storage';
import {store} from '../store';
import {logout, refreshToken} from '../store/slices/authSlice';

// Base API configuration
const BASE_URL = __DEV__ 
  ? 'http://10.0.2.2:8000/api' // Android emulator
  : 'https://your-production-domain.com/api';

// Create axios instance
const api = axios.create({
  baseURL: BASE_URL,
  timeout: 30000,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

// Request interceptor to add auth token
api.interceptors.request.use(
  async config => {
    const token = store.getState().auth.token;
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  error => {
    return Promise.reject(error);
  }
);

// Response interceptor for token refresh
api.interceptors.response.use(
  response => response,
  async error => {
    const originalRequest = error.config;

    if (error.response?.status === 401 && !originalRequest._retry) {
      originalRequest._retry = true;

      try {
        const refreshTokenValue = store.getState().auth.refreshToken;
        if (refreshTokenValue) {
          const response = await axios.post(`${BASE_URL}/auth/refresh`, {
            refresh_token: refreshTokenValue,
          });

          const {token, refresh_token} = response.data;
          store.dispatch(refreshToken({token, refreshToken: refresh_token}));

          // Retry original request with new token
          originalRequest.headers.Authorization = `Bearer ${token}`;
          return api(originalRequest);
        }
      } catch (refreshError) {
        // Refresh failed, logout user
        store.dispatch(logout());
        await AsyncStorage.clear();
      }
    }

    return Promise.reject(error);
  }
);

// API endpoints
export const authAPI = {
  sendOTP: (phone) => api.post('/auth/send-otp', {phone}),
  verifyOTP: (phone, otp) => api.post('/auth/verify-otp', {phone, otp}),
  refreshToken: (refreshToken) => api.post('/auth/refresh', {refresh_token: refreshToken}),
  logout: () => api.post('/auth/logout'),
};

export const productAPI = {
  getProducts: (params) => api.get('/products', {params}),
  getProductDetails: (id) => api.get(`/products/${id}`),
  getCategories: () => api.get('/categories'),
  searchProducts: (query, filters) => api.get('/search', {params: {q: query, ...filters}}),
  getFeaturedProducts: () => api.get('/products/featured'),
  getTrendingProducts: () => api.get('/products/trending'),
};

export const cartAPI = {
  getCart: () => api.get('/cart'),
  addItem: (productId, quantity, deliveryType) => 
    api.post('/cart/add', {product_id: productId, quantity, delivery_type: deliveryType}),
  updateItem: (cartItemId, quantity) => 
    api.patch(`/cart/${cartItemId}`, {quantity}),
  removeItem: (cartItemId) => api.delete(`/cart/${cartItemId}`),
  clearCart: () => api.delete('/cart'),
  switchDeliveryType: (cartItemId, deliveryType) =>
    api.patch(`/cart/${cartItemId}/delivery-type`, {delivery_type: deliveryType}),
};

export const orderAPI = {
  placeOrder: (orderData) => api.post('/orders', orderData),
  getOrders: (page, status) => api.get('/orders', {params: {page, status}}),
  getOrderDetails: (orderId) => api.get(`/orders/${orderId}`),
  trackOrder: (orderId) => api.get(`/orders/${orderId}/track`),
  cancelOrder: (orderId, reason) => api.post(`/orders/${orderId}/cancel`, {reason}),
};

export const wishlistAPI = {
  getWishlist: () => api.get('/wishlist'),
  addToWishlist: (productId) => api.post('/wishlist/add', {product_id: productId}),
  removeFromWishlist: (productId) => api.post('/wishlist/remove', {product_id: productId}),
  toggleWishlist: (productId) => api.post('/wishlist/toggle', {product_id: productId}),
  moveToCart: (productId) => api.post('/wishlist/move-to-cart', {product_id: productId}),
};

export const userAPI = {
  updateProfile: (profileData) => api.put('/user/profile', profileData),
  updateAvatar: (imageFile) => {
    const formData = new FormData();
    formData.append('avatar', {
      uri: imageFile.uri,
      type: imageFile.type,
      name: imageFile.fileName || 'avatar.jpg',
    });
    return api.post('/user/avatar', formData, {
      headers: {'Content-Type': 'multipart/form-data'},
    });
  },
  getAddresses: () => api.get('/user/addresses'),
  addAddress: (addressData) => api.post('/user/addresses', addressData),
  updateAddress: (addressId, addressData) => api.put(`/user/addresses/${addressId}`, addressData),
  deleteAddress: (addressId) => api.delete(`/user/addresses/${addressId}`),
};

export const notificationAPI = {
  getNotifications: (page) => api.get('/notifications', {params: {page}}),
  markAsRead: (notificationId) => api.post(`/notifications/${notificationId}/read`),
  markAllAsRead: () => api.post('/notifications/mark-all-read'),
  updateDeviceToken: (token) => api.post('/notifications/device-token', {token}),
};

export const paymentAPI = {
  createOrder: (orderData) => api.post('/payment/create-order', orderData),
  verifyPayment: (paymentData) => api.post('/payment/verify', paymentData),
  getPaymentMethods: () => api.get('/payment/methods'),
};

export default api;