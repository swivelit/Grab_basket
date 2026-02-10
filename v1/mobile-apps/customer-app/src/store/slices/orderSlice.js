import {createSlice, createAsyncThunk} from '@reduxjs/toolkit';
import {orderAPI} from '../../services/api';

// Async thunks
export const placeOrder = createAsyncThunk(
  'orders/placeOrder',
  async (orderData, {rejectWithValue}) => {
    try {
      const response = await orderAPI.placeOrder(orderData);
      return response.data;
    } catch (error) {
      return rejectWithValue(error.response?.data || 'Failed to place order');
    }
  }
);

export const fetchOrders = createAsyncThunk(
  'orders/fetchOrders',
  async ({page = 1, status = null}, {rejectWithValue}) => {
    try {
      const response = await orderAPI.getOrders(page, status);
      return response.data;
    } catch (error) {
      return rejectWithValue(error.response?.data || 'Failed to fetch orders');
    }
  }
);

export const fetchOrderDetails = createAsyncThunk(
  'orders/fetchOrderDetails',
  async (orderId, {rejectWithValue}) => {
    try {
      const response = await orderAPI.getOrderDetails(orderId);
      return response.data;
    } catch (error) {
      return rejectWithValue(error.response?.data || 'Failed to fetch order details');
    }
  }
);

export const trackOrder = createAsyncThunk(
  'orders/trackOrder',
  async (orderId, {rejectWithValue}) => {
    try {
      const response = await orderAPI.trackOrder(orderId);
      return response.data;
    } catch (error) {
      return rejectWithValue(error.response?.data || 'Failed to track order');
    }
  }
);

export const cancelOrder = createAsyncThunk(
  'orders/cancelOrder',
  async ({orderId, reason}, {rejectWithValue}) => {
    try {
      const response = await orderAPI.cancelOrder(orderId, reason);
      return response.data;
    } catch (error) {
      return rejectWithValue(error.response?.data || 'Failed to cancel order');
    }
  }
);

const initialState = {
  orders: [],
  currentOrder: null,
  trackingInfo: null,
  loading: false,
  trackingLoading: false,
  error: null,
  pagination: {
    currentPage: 1,
    totalPages: 1,
    hasNextPage: false,
  },
  orderStatuses: [
    'pending',
    'confirmed',
    'preparing',
    'out_for_delivery',
    'delivered',
    'cancelled',
  ],
};

const orderSlice = createSlice({
  name: 'orders',
  initialState,
  reducers: {
    clearError: state => {
      state.error = null;
    },
    clearCurrentOrder: state => {
      state.currentOrder = null;
    },
    clearTrackingInfo: state => {
      state.trackingInfo = null;
    },
    updateOrderStatus: (state, action) => {
      const {orderId, status} = action.payload;
      const order = state.orders.find(order => order.id === orderId);
      if (order) {
        order.status = status;
      }
      if (state.currentOrder && state.currentOrder.id === orderId) {
        state.currentOrder.status = status;
      }
    },
  },
  extraReducers: builder => {
    builder
      // Place order
      .addCase(placeOrder.pending, state => {
        state.loading = true;
        state.error = null;
      })
      .addCase(placeOrder.fulfilled, (state, action) => {
        state.loading = false;
        state.orders.unshift(action.payload);
        state.currentOrder = action.payload;
      })
      .addCase(placeOrder.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload;
      })
      // Fetch orders
      .addCase(fetchOrders.pending, state => {
        state.loading = true;
        state.error = null;
      })
      .addCase(fetchOrders.fulfilled, (state, action) => {
        state.loading = false;
        if (action.meta.arg.page === 1) {
          state.orders = action.payload.data;
        } else {
          state.orders = [...state.orders, ...action.payload.data];
        }
        state.pagination = {
          currentPage: action.payload.current_page,
          totalPages: action.payload.last_page,
          hasNextPage: action.payload.current_page < action.payload.last_page,
        };
      })
      .addCase(fetchOrders.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload;
      })
      // Fetch order details
      .addCase(fetchOrderDetails.pending, state => {
        state.loading = true;
        state.error = null;
      })
      .addCase(fetchOrderDetails.fulfilled, (state, action) => {
        state.loading = false;
        state.currentOrder = action.payload;
      })
      .addCase(fetchOrderDetails.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload;
      })
      // Track order
      .addCase(trackOrder.pending, state => {
        state.trackingLoading = true;
      })
      .addCase(trackOrder.fulfilled, (state, action) => {
        state.trackingLoading = false;
        state.trackingInfo = action.payload;
      })
      .addCase(trackOrder.rejected, (state, action) => {
        state.trackingLoading = false;
        state.error = action.payload;
      })
      // Cancel order
      .addCase(cancelOrder.fulfilled, (state, action) => {
        const order = state.orders.find(order => order.id === action.payload.id);
        if (order) {
          order.status = 'cancelled';
        }
        if (state.currentOrder && state.currentOrder.id === action.payload.id) {
          state.currentOrder.status = 'cancelled';
        }
      });
  },
});

export const {clearError, clearCurrentOrder, clearTrackingInfo, updateOrderStatus} = orderSlice.actions;
export default orderSlice.reducer;