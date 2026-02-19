import {createSlice, createAsyncThunk} from '@reduxjs/toolkit';
import {productAPI} from '../../services/api';

// Async thunks
export const fetchProducts = createAsyncThunk(
  'products/fetchProducts',
  async ({page = 1, category = null, search = null, filters = {}}, {rejectWithValue}) => {
    try {
      const response = await productAPI.getProducts({page, category, search, ...filters});
      return response.data;
    } catch (error) {
      return rejectWithValue(error.response?.data || 'Failed to fetch products');
    }
  }
);

export const fetchProductDetails = createAsyncThunk(
  'products/fetchProductDetails',
  async (productId, {rejectWithValue}) => {
    try {
      const response = await productAPI.getProductDetails(productId);
      return response.data;
    } catch (error) {
      return rejectWithValue(error.response?.data || 'Failed to fetch product details');
    }
  }
);

export const fetchCategories = createAsyncThunk(
  'products/fetchCategories',
  async (_, {rejectWithValue}) => {
    try {
      const response = await productAPI.getCategories();
      return response.data;
    } catch (error) {
      return rejectWithValue(error.response?.data || 'Failed to fetch categories');
    }
  }
);

export const searchProducts = createAsyncThunk(
  'products/searchProducts',
  async ({query, filters = {}}, {rejectWithValue}) => {
    try {
      const response = await productAPI.searchProducts(query, filters);
      return response.data;
    } catch (error) {
      return rejectWithValue(error.response?.data || 'Search failed');
    }
  }
);

const initialState = {
  items: [],
  categories: [],
  currentProduct: null,
  searchResults: [],
  featured: [],
  trending: [],
  loading: false,
  searchLoading: false,
  categoryLoading: false,
  error: null,
  searchError: null,
  pagination: {
    currentPage: 1,
    totalPages: 1,
    hasNextPage: false,
    hasPreviousPage: false,
  },
  filters: {
    priceRange: [0, 10000],
    sortBy: 'newest',
    category: null,
    inStock: true,
  },
};

const productSlice = createSlice({
  name: 'products',
  initialState,
  reducers: {
    clearError: state => {
      state.error = null;
      state.searchError = null;
    },
    clearCurrentProduct: state => {
      state.currentProduct = null;
    },
    clearSearchResults: state => {
      state.searchResults = [];
    },
    setFilters: (state, action) => {
      state.filters = {...state.filters, ...action.payload};
    },
    resetFilters: state => {
      state.filters = initialState.filters;
    },
  },
  extraReducers: builder => {
    builder
      // Fetch products
      .addCase(fetchProducts.pending, state => {
        state.loading = true;
        state.error = null;
      })
      .addCase(fetchProducts.fulfilled, (state, action) => {
        state.loading = false;
        if (action.meta.arg.page === 1) {
          state.items = action.payload.data;
        } else {
          state.items = [...state.items, ...action.payload.data];
        }
        state.pagination = {
          currentPage: action.payload.current_page,
          totalPages: action.payload.last_page,
          hasNextPage: action.payload.current_page < action.payload.last_page,
          hasPreviousPage: action.payload.current_page > 1,
        };
      })
      .addCase(fetchProducts.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload;
      })
      // Fetch product details
      .addCase(fetchProductDetails.pending, state => {
        state.loading = true;
        state.error = null;
      })
      .addCase(fetchProductDetails.fulfilled, (state, action) => {
        state.loading = false;
        state.currentProduct = action.payload;
      })
      .addCase(fetchProductDetails.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload;
      })
      // Fetch categories
      .addCase(fetchCategories.pending, state => {
        state.categoryLoading = true;
      })
      .addCase(fetchCategories.fulfilled, (state, action) => {
        state.categoryLoading = false;
        state.categories = action.payload;
      })
      .addCase(fetchCategories.rejected, (state, action) => {
        state.categoryLoading = false;
        state.error = action.payload;
      })
      // Search products
      .addCase(searchProducts.pending, state => {
        state.searchLoading = true;
        state.searchError = null;
      })
      .addCase(searchProducts.fulfilled, (state, action) => {
        state.searchLoading = false;
        state.searchResults = action.payload.data;
      })
      .addCase(searchProducts.rejected, (state, action) => {
        state.searchLoading = false;
        state.searchError = action.payload;
      });
  },
});

export const {
  clearError,
  clearCurrentProduct,
  clearSearchResults,
  setFilters,
  resetFilters,
} = productSlice.actions;

export default productSlice.reducer;