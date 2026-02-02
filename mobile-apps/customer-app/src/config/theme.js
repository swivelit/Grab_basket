import {DefaultTheme} from 'react-native-paper';

const theme = {
  ...DefaultTheme,
  colors: {
    ...DefaultTheme.colors,
    primary: '#FF6B00',
    accent: '#FFD700',
    background: '#FFFFFF',
    surface: '#FFFFFF',
    text: '#333333',
    placeholder: '#999999',
    backdrop: 'rgba(0, 0, 0, 0.5)',
    notification: '#FF3B30',
    // Custom colors
    success: '#4CAF50',
    warning: '#FF9800',
    error: '#F44336',
    info: '#2196F3',
    lightGray: '#F5F5F5',
    darkGray: '#757575',
    border: '#E0E0E0',
  },
  fonts: {
    ...DefaultTheme.fonts,
    regular: {
      fontFamily: 'System',
      fontWeight: 'normal',
    },
    medium: {
      fontFamily: 'System',
      fontWeight: '500',
    },
    light: {
      fontFamily: 'System',
      fontWeight: '300',
    },
    thin: {
      fontFamily: 'System',
      fontWeight: '100',
    },
  },
  roundness: 8,
};

export default theme;