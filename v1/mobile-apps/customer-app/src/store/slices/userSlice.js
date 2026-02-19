import {createSlice, createAsyncThunk} from '@reduxjs/toolkit';
import {userAPI} from '../../services/api';

// Async thunks
export const updateProfile = createAsyncThunk(
  'user/updateProfile',
  async (profileData, {rejectWithValue}) => {
    try {
      const response = await userAPI.updateProfile(profileData);
      return response.data;
    } catch (error) {
      return rejectWithValue(error.response?.data || 'Failed to update profile');
    }
  }
);

export const updateAvatar = createAsyncThunk(
  'user/updateAvatar',
  async (imageFile, {rejectWithValue}) => {
    try {
      const response = await userAPI.updateAvatar(imageFile);
      return response.data;
    } catch (error) {
      return rejectWithValue(error.response?.data || 'Failed to update avatar');
    }
  }
);

export const fetchAddresses = createAsyncThunk(
  'user/fetchAddresses',
  async (_, {rejectWithValue}) => {
    try {
      const response = await userAPI.getAddresses();
      return response.data;
    } catch (error) {
      return rejectWithValue(error.response?.data || 'Failed to fetch addresses');
    }
  }
);

export const addAddress = createAsyncThunk(
  'user/addAddress',
  async (addressData, {rejectWithValue}) => {
    try {
      const response = await userAPI.addAddress(addressData);
      return response.data;
    } catch (error) {
      return rejectWithValue(error.response?.data || 'Failed to add address');
    }
  }
);

export const updateAddress = createAsyncThunk(
  'user/updateAddress',
  async ({addressId, addressData}, {rejectWithValue}) => {
    try {
      const response = await userAPI.updateAddress(addressId, addressData);
      return response.data;
    } catch (error) {
      return rejectWithValue(error.response?.data || 'Failed to update address');
    }
  }
);

export const deleteAddress = createAsyncThunk(
  'user/deleteAddress',
  async (addressId, {rejectWithValue}) => {
    try {
      await userAPI.deleteAddress(addressId);
      return addressId;
    } catch (error) {
      return rejectWithValue(error.response?.data || 'Failed to delete address');
    }
  }
);

const initialState = {
  profile: {
    name: '',
    email: '',
    phone: '',
    avatar: null,
    dateOfBirth: null,
    gender: null,
  },
  addresses: [],
  defaultAddressId: null,
  location: {
    latitude: null,
    longitude: null,
    address: null,
    city: null,
    state: null,
    pincode: null,
  },
  preferences: {
    notifications: {
      orderUpdates: true,
      promotions: true,
      newProducts: false,
    },
    language: 'en',
    currency: 'INR',
  },
  loading: false,
  addressLoading: false,
  error: null,
};

const userSlice = createSlice({
  name: 'user',
  initialState,
  reducers: {
    clearError: state => {
      state.error = null;
    },
    setLocation: (state, action) => {
      state.location = {...state.location, ...action.payload};
    },
    setDefaultAddress: (state, action) => {
      state.defaultAddressId = action.payload;
      // Update addresses array
      state.addresses = state.addresses.map(addr => ({
        ...addr,
        isDefault: addr.id === action.payload,
      }));
    },
    updatePreferences: (state, action) => {
      state.preferences = {...state.preferences, ...action.payload};
    },
    updateNotificationPreferences: (state, action) => {
      state.preferences.notifications = {
        ...state.preferences.notifications,
        ...action.payload,
      };
    },
  },
  extraReducers: builder => {
    builder
      // Update profile
      .addCase(updateProfile.pending, state => {
        state.loading = true;
        state.error = null;
      })
      .addCase(updateProfile.fulfilled, (state, action) => {
        state.loading = false;
        state.profile = {...state.profile, ...action.payload};
      })
      .addCase(updateProfile.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload;
      })
      // Update avatar
      .addCase(updateAvatar.pending, state => {
        state.loading = true;
      })
      .addCase(updateAvatar.fulfilled, (state, action) => {
        state.loading = false;
        state.profile.avatar = action.payload.avatar;
      })
      .addCase(updateAvatar.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload;
      })
      // Fetch addresses
      .addCase(fetchAddresses.pending, state => {
        state.addressLoading = true;
      })
      .addCase(fetchAddresses.fulfilled, (state, action) => {
        state.addressLoading = false;
        state.addresses = action.payload;
        const defaultAddress = action.payload.find(addr => addr.isDefault);
        if (defaultAddress) {
          state.defaultAddressId = defaultAddress.id;
        }
      })
      .addCase(fetchAddresses.rejected, (state, action) => {
        state.addressLoading = false;
        state.error = action.payload;
      })
      // Add address
      .addCase(addAddress.fulfilled, (state, action) => {
        state.addresses.push(action.payload);
        if (action.payload.isDefault) {
          state.defaultAddressId = action.payload.id;
        }
      })
      // Update address
      .addCase(updateAddress.fulfilled, (state, action) => {
        const index = state.addresses.findIndex(addr => addr.id === action.payload.id);
        if (index !== -1) {
          state.addresses[index] = action.payload;
        }
        if (action.payload.isDefault) {
          state.defaultAddressId = action.payload.id;
        }
      })
      // Delete address
      .addCase(deleteAddress.fulfilled, (state, action) => {
        state.addresses = state.addresses.filter(addr => addr.id !== action.payload);
        if (state.defaultAddressId === action.payload) {
          state.defaultAddressId = null;
        }
      });
  },
});

export const {
  clearError,
  setLocation,
  setDefaultAddress,
  updatePreferences,
  updateNotificationPreferences,
} = userSlice.actions;

export default userSlice.reducer;