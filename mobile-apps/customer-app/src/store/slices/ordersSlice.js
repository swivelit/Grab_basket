import { createSlice, createAsyncThunk } from '@reduxjs/toolkit';
import axios from 'axios';

const API_URL = 'https://grabbaskets.com/api';

// Async thunks
export const fetchOrders = createAsyncThunk(
    'orders/fetchOrders',
    async (_, { getState }) => {
        const { token } = getState().auth;
        const response = await axios.get(`${API_URL}/orders`, {
            headers: { Authorization: `Bearer ${token}` },
        });
        return response.data;
    }
);

export const createOrder = createAsyncThunk(
    'orders/createOrder',
    async (orderData, { getState }) => {
        const { token } = getState().auth;
        const response = await axios.post(`${API_URL}/orders`, orderData, {
            headers: { Authorization: `Bearer ${token}` },
        });
        return response.data;
    }
);

export const trackOrder = createAsyncThunk(
    'orders/trackOrder',
    async (orderId, { getState }) => {
        const { token } = getState().auth;
        const response = await axios.get(`${API_URL}/orders/${orderId}/track`, {
            headers: { Authorization: `Bearer ${token}` },
        });
        return response.data;
    }
);

const ordersSlice = createSlice({
    name: 'orders',
    initialState: {
        orders: [],
        currentOrder: null,
        loading: false,
        error: null,
    },
    reducers: {},
    extraReducers: (builder) => {
        builder
            // Fetch Orders
            .addCase(fetchOrders.pending, (state) => {
                state.loading = true;
                state.error = null;
            })
            .addCase(fetchOrders.fulfilled, (state, action) => {
                state.loading = false;
                state.orders = action.payload.orders || action.payload;
            })
            .addCase(fetchOrders.rejected, (state, action) => {
                state.loading = false;
                state.error = action.error.message;
            })

            // Create Order
            .addCase(createOrder.fulfilled, (state, action) => {
                state.orders.unshift(action.payload);
            })

            // Track Order
            .addCase(trackOrder.fulfilled, (state, action) => {
                state.currentOrder = action.payload;
            });
    },
});

export default ordersSlice.reducer;
