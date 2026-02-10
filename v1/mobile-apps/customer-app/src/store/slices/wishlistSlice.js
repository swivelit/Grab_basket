import {createSlice, createAsyncThunk} from '@reduxjs/toolkit';
import {wishlistAPI} from '../../services/api';

// Async thunks
export const fetchWishlist = createAsyncThunk(
  'wishlist/fetchWishlist',
  async (_, {rejectWithValue}) => {
    try {
      const response = await wishlistAPI.getWishlist();
      return response.data;
    } catch (error) {
      return rejectWithValue(error.response?.data || 'Failed to fetch wishlist');
    }
  }
);

export const addToWishlist = createAsyncThunk(
  'wishlist/addToWishlist',
  async (productId, {rejectWithValue}) => {
    try {
      const response = await wishlistAPI.addToWishlist(productId);
      return response.data;
    } catch (error) {
      return rejectWithValue(error.response?.data || 'Failed to add to wishlist');
    }
  }
);

export const removeFromWishlist = createAsyncThunk(
  'wishlist/removeFromWishlist',
  async (productId, {rejectWithValue}) => {
    try {
      await wishlistAPI.removeFromWishlist(productId);
      return productId;
    } catch (error) {
      return rejectWithValue(error.response?.data || 'Failed to remove from wishlist');
    }
  }
);

export const toggleWishlist = createAsyncThunk(
  'wishlist/toggleWishlist',
  async (productId, {rejectWithValue}) => {
    try {
      const response = await wishlistAPI.toggleWishlist(productId);
      return response.data;
    } catch (error) {
      return rejectWithValue(error.response?.data || 'Failed to toggle wishlist');
    }
  }
);

export const moveToCart = createAsyncThunk(
  'wishlist/moveToCart',
  async (productId, {rejectWithValue}) => {
    try {
      const response = await wishlistAPI.moveToCart(productId);
      return {productId, ...response.data};
    } catch (error) {
      return rejectWithValue(error.response?.data || 'Failed to move to cart');
    }
  }
);

const initialState = {
  items: [],
  itemCount: 0,
  loading: false,
  error: null,
  actionLoading: {}, // For individual item actions
};

const wishlistSlice = createSlice({
  name: 'wishlist',
  initialState,
  reducers: {
    clearError: state => {
      state.error = null;
    },
    setItemLoading: (state, action) => {
      const {productId, loading} = action.payload;
      state.actionLoading[productId] = loading;
    },
  },
  extraReducers: builder => {
    builder
      // Fetch wishlist
      .addCase(fetchWishlist.pending, state => {
        state.loading = true;
        state.error = null;
      })
      .addCase(fetchWishlist.fulfilled, (state, action) => {
        state.loading = false;
        state.items = action.payload.items || [];
        state.itemCount = action.payload.items?.length || 0;
      })
      .addCase(fetchWishlist.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload;
      })
      // Add to wishlist
      .addCase(addToWishlist.pending, (state, action) => {
        state.actionLoading[action.meta.arg] = true;
      })
      .addCase(addToWishlist.fulfilled, (state, action) => {
        const productId = action.meta.arg;
        state.actionLoading[productId] = false;
        
        // Add to items if not already present
        const existingItem = state.items.find(item => item.product_id === productId);
        if (!existingItem) {
          state.items.push(action.payload);
          state.itemCount += 1;
        }
      })
      .addCase(addToWishlist.rejected, (state, action) => {
        const productId = action.meta.arg;
        state.actionLoading[productId] = false;
        state.error = action.payload;
      })
      // Remove from wishlist
      .addCase(removeFromWishlist.pending, (state, action) => {
        state.actionLoading[action.meta.arg] = true;
      })
      .addCase(removeFromWishlist.fulfilled, (state, action) => {
        const productId = action.payload;
        state.actionLoading[productId] = false;
        state.items = state.items.filter(item => item.product_id !== productId);
        state.itemCount -= 1;
      })
      .addCase(removeFromWishlist.rejected, (state, action) => {
        const productId = action.meta.arg;
        state.actionLoading[productId] = false;
        state.error = action.payload;
      })
      // Toggle wishlist
      .addCase(toggleWishlist.pending, (state, action) => {
        state.actionLoading[action.meta.arg] = true;
      })
      .addCase(toggleWishlist.fulfilled, (state, action) => {
        const productId = action.meta.arg;
        state.actionLoading[productId] = false;
        
        const existingItem = state.items.find(item => item.product_id === productId);
        if (action.payload.added) {
          if (!existingItem) {
            state.items.push(action.payload.item);
            state.itemCount += 1;
          }
        } else {
          if (existingItem) {
            state.items = state.items.filter(item => item.product_id !== productId);
            state.itemCount -= 1;
          }
        }
      })
      .addCase(toggleWishlist.rejected, (state, action) => {
        const productId = action.meta.arg;
        state.actionLoading[productId] = false;
        state.error = action.payload;
      })
      // Move to cart
      .addCase(moveToCart.pending, (state, action) => {
        state.actionLoading[action.meta.arg] = true;
      })
      .addCase(moveToCart.fulfilled, (state, action) => {
        const productId = action.payload.productId;
        state.actionLoading[productId] = false;
        state.items = state.items.filter(item => item.product_id !== productId);
        state.itemCount -= 1;
      })
      .addCase(moveToCart.rejected, (state, action) => {
        const productId = action.meta.arg;
        state.actionLoading[productId] = false;
        state.error = action.payload;
      });
  },
});

export const {clearError, setItemLoading} = wishlistSlice.actions;
export default wishlistSlice.reducer;