import {createSlice, createAsyncThunk} from '@reduxjs/toolkit';
import {cartAPI} from '../../services/api';

// Async thunks
export const addToCart = createAsyncThunk(
  'cart/addToCart',
  async ({productId, quantity = 1, deliveryType = 'standard'}, {rejectWithValue}) => {
    try {
      const response = await cartAPI.addItem(productId, quantity, deliveryType);
      return response.data;
    } catch (error) {
      return rejectWithValue(error.response?.data || 'Failed to add to cart');
    }
  }
);

export const updateCartItem = createAsyncThunk(
  'cart/updateCartItem',
  async ({cartItemId, quantity}, {rejectWithValue}) => {
    try {
      const response = await cartAPI.updateItem(cartItemId, quantity);
      return response.data;
    } catch (error) {
      return rejectWithValue(error.response?.data || 'Failed to update cart');
    }
  }
);

export const removeFromCart = createAsyncThunk(
  'cart/removeFromCart',
  async (cartItemId, {rejectWithValue}) => {
    try {
      await cartAPI.removeItem(cartItemId);
      return cartItemId;
    } catch (error) {
      return rejectWithValue(error.response?.data || 'Failed to remove item');
    }
  }
);

export const fetchCart = createAsyncThunk(
  'cart/fetchCart',
  async (_, {rejectWithValue}) => {
    try {
      const response = await cartAPI.getCart();
      return response.data;
    } catch (error) {
      return rejectWithValue(error.response?.data || 'Failed to fetch cart');
    }
  }
);

export const clearCart = createAsyncThunk(
  'cart/clearCart',
  async (_, {rejectWithValue}) => {
    try {
      await cartAPI.clearCart();
      return;
    } catch (error) {
      return rejectWithValue(error.response?.data || 'Failed to clear cart');
    }
  }
);

const initialState = {
  items: [],
  total: 0,
  itemCount: 0,
  deliveryCharges: 0,
  quickDeliveryAvailable: false,
  loading: false,
  error: null,
};

const cartSlice = createSlice({
  name: 'cart',
  initialState,
  reducers: {
    clearError: state => {
      state.error = null;
    },
    toggleDeliveryType: (state, action) => {
      const {cartItemId, deliveryType} = action.payload;
      const item = state.items.find(item => item.id === cartItemId);
      if (item) {
        item.deliveryType = deliveryType;
        // Recalculate totals
        state.total = state.items.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        state.deliveryCharges = state.items.some(item => item.deliveryType === 'quick') ? 25 : 0;
      }
    },
    setQuickDeliveryAvailable: (state, action) => {
      state.quickDeliveryAvailable = action.payload;
    },
  },
  extraReducers: builder => {
    builder
      // Add to cart
      .addCase(addToCart.pending, state => {
        state.loading = true;
        state.error = null;
      })
      .addCase(addToCart.fulfilled, (state, action) => {
        state.loading = false;
        const newItem = action.payload;
        const existingItem = state.items.find(item => item.product_id === newItem.product_id);
        
        if (existingItem) {
          existingItem.quantity += newItem.quantity;
        } else {
          state.items.push(newItem);
        }
        
        // Recalculate totals
        state.itemCount = state.items.reduce((sum, item) => sum + item.quantity, 0);
        state.total = state.items.reduce((sum, item) => sum + (item.price * item.quantity), 0);
      })
      .addCase(addToCart.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload;
      })
      // Update cart item
      .addCase(updateCartItem.fulfilled, (state, action) => {
        const updatedItem = action.payload;
        const index = state.items.findIndex(item => item.id === updatedItem.id);
        if (index !== -1) {
          state.items[index] = updatedItem;
        }
        
        // Recalculate totals
        state.itemCount = state.items.reduce((sum, item) => sum + item.quantity, 0);
        state.total = state.items.reduce((sum, item) => sum + (item.price * item.quantity), 0);
      })
      // Remove from cart
      .addCase(removeFromCart.fulfilled, (state, action) => {
        state.items = state.items.filter(item => item.id !== action.payload);
        
        // Recalculate totals
        state.itemCount = state.items.reduce((sum, item) => sum + item.quantity, 0);
        state.total = state.items.reduce((sum, item) => sum + (item.price * item.quantity), 0);
      })
      // Fetch cart
      .addCase(fetchCart.pending, state => {
        state.loading = true;
      })
      .addCase(fetchCart.fulfilled, (state, action) => {
        state.loading = false;
        state.items = action.payload.items || [];
        state.total = action.payload.total || 0;
        state.itemCount = action.payload.itemCount || 0;
        state.deliveryCharges = action.payload.deliveryCharges || 0;
      })
      .addCase(fetchCart.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload;
      })
      // Clear cart
      .addCase(clearCart.fulfilled, state => {
        state.items = [];
        state.total = 0;
        state.itemCount = 0;
        state.deliveryCharges = 0;
      });
  },
});

export const {clearError, toggleDeliveryType, setQuickDeliveryAvailable} = cartSlice.actions;
export default cartSlice.reducer;