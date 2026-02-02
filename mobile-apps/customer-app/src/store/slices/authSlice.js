import {createSlice, createAsyncThunk} from '@reduxjs/toolkit';
import {authAPI} from '../../services/api';

// Async thunks
export const loginWithOTP = createAsyncThunk(
  'auth/loginWithOTP',
  async ({phone, otp}, {rejectWithValue}) => {
    try {
      const response = await authAPI.verifyOTP(phone, otp);
      return response.data;
    } catch (error) {
      return rejectWithValue(error.response?.data || 'Login failed');
    }
  }
);

export const sendOTP = createAsyncThunk(
  'auth/sendOTP',
  async (phone, {rejectWithValue}) => {
    try {
      const response = await authAPI.sendOTP(phone);
      return response.data;
    } catch (error) {
      return rejectWithValue(error.response?.data || 'Failed to send OTP');
    }
  }
);

export const refreshToken = createAsyncThunk(
  'auth/refreshToken',
  async (_, {getState, rejectWithValue}) => {
    try {
      const {auth} = getState();
      const response = await authAPI.refreshToken(auth.refreshToken);
      return response.data;
    } catch (error) {
      return rejectWithValue(error.response?.data || 'Token refresh failed');
    }
  }
);

const initialState = {
  isAuthenticated: false,
  user: null,
  token: null,
  refreshToken: null,
  loading: false,
  error: null,
  otpSent: false,
  otpLoading: false,
};

const authSlice = createSlice({
  name: 'auth',
  initialState,
  reducers: {
    logout: state => {
      state.isAuthenticated = false;
      state.user = null;
      state.token = null;
      state.refreshToken = null;
      state.error = null;
      state.otpSent = false;
    },
    clearError: state => {
      state.error = null;
    },
    setUser: (state, action) => {
      state.user = {...state.user, ...action.payload};
    },
  },
  extraReducers: builder => {
    builder
      // Send OTP
      .addCase(sendOTP.pending, state => {
        state.otpLoading = true;
        state.error = null;
      })
      .addCase(sendOTP.fulfilled, state => {
        state.otpLoading = false;
        state.otpSent = true;
      })
      .addCase(sendOTP.rejected, (state, action) => {
        state.otpLoading = false;
        state.error = action.payload;
      })
      // Login with OTP
      .addCase(loginWithOTP.pending, state => {
        state.loading = true;
        state.error = null;
      })
      .addCase(loginWithOTP.fulfilled, (state, action) => {
        state.loading = false;
        state.isAuthenticated = true;
        state.user = action.payload.user;
        state.token = action.payload.token;
        state.refreshToken = action.payload.refreshToken;
        state.otpSent = false;
      })
      .addCase(loginWithOTP.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload;
      })
      // Refresh Token
      .addCase(refreshToken.fulfilled, (state, action) => {
        state.token = action.payload.token;
        state.refreshToken = action.payload.refreshToken;
      })
      .addCase(refreshToken.rejected, state => {
        state.isAuthenticated = false;
        state.user = null;
        state.token = null;
        state.refreshToken = null;
      });
  },
});

export const {logout, clearError, setUser} = authSlice.actions;
export default authSlice.reducer;