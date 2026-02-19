import { createSlice, createAsyncThunk } from '@reduxjs/toolkit';
import axios from 'axios';

const API_URL = 'https://grabbaskets.com/api';

// Async thunks
export const fetchProducts = createAsyncThunk(
    'products/fetchProducts',
    async ({ categoryId, search } = {}) => {
        const response = await axios.get(`${API_URL}/products`, {
            params: { category_id: categoryId, search },
        });
        return response.data;
    }
);

export const addToCart = createAsyncThunk(
    'products/addToCart',
    async (product, { getState }) => {
        // You can add API call here to sync with backend
        return product;
    }
);

export const removeFromCart = createAsyncThunk(
    'products/removeFromCart',
    async (productId) => {
        return productId;
    }
);

export const updateQuantity = createAsyncThunk(
    'products/updateQuantity',
    async ({ id, quantity }) => {
        return { id, quantity };
    }
);

export const toggleWishlist = createAsyncThunk(
    'products/toggleWishlist',
    async (productId) => {
        return productId;
    }
);

const productsSlice = createSlice({
    name: 'products',
    initialState: {
        products: [],
        cart: [],
        wishlist: [],
        loading: false,
        error: null,
    },
    reducers: {
        clearCart: (state) => {
            state.cart = [];
        },
    },
    extraReducers: (builder) => {
        builder
            // Fetch Products
            .addCase(fetchProducts.pending, (state) => {
                state.loading = true;
                state.error = null;
            })
            .addCase(fetchProducts.fulfilled, (state, action) => {
                state.loading = false;
                state.products = action.payload.products || action.payload;
            })
            .addCase(fetchProducts.rejected, (state, action) => {
                state.loading = false;
                state.error = action.error.message;
            })

            // Add to Cart
            .addCase(addToCart.fulfilled, (state, action) => {
                const product = action.payload;
                const existingItem = state.cart.find(item => item.id === product.id);

                if (existingItem) {
                    existingItem.quantity += product.quantity || 1;
                } else {
                    state.cart.push({ ...product, quantity: product.quantity || 1 });
                }
            })

            // Remove from Cart
            .addCase(removeFromCart.fulfilled, (state, action) => {
                state.cart = state.cart.filter(item => item.id !== action.payload);
            })

            // Update Quantity
            .addCase(updateQuantity.fulfilled, (state, action) => {
                const { id, quantity } = action.payload;
                const item = state.cart.find(item => item.id === id);
                if (item) {
                    item.quantity = quantity;
                }
            })

            // Toggle Wishlist
            .addCase(toggleWishlist.fulfilled, (state, action) => {
                const productId = action.payload;
                const index = state.wishlist.indexOf(productId);

                if (index > -1) {
                    state.wishlist.splice(index, 1);
                } else {
                    state.wishlist.push(productId);
                }
            });
    },
});

export const { clearCart } = productsSlice.actions;
export default productsSlice.reducer;
