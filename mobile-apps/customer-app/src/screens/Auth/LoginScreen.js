import React, {useState} from 'react';
import {
  View,
  StyleSheet,
  Image,
  TouchableOpacity,
  Dimensions,
  StatusBar,
} from 'react-native';
import {
  Text,
  TextInput,
  Button,
  Card,
  HelperText,
} from 'react-native-paper';
import {useDispatch, useSelector} from 'react-redux';
import LinearGradient from 'react-native-linear-gradient';
import Icon from 'react-native-vector-icons/MaterialIcons';

import {sendOTP} from '../../store/slices/authSlice';
import theme from '../../config/theme';

const {width, height} = Dimensions.get('window');

const LoginScreen = ({navigation}) => {
  const [phone, setPhone] = useState('');
  const [errors, setErrors] = useState({});

  const dispatch = useDispatch();
  const {otpLoading, error} = useSelector(state => state.auth);

  const validatePhone = (phoneNumber) => {
    const phoneRegex = /^[6-9]\d{9}$/;
    return phoneRegex.test(phoneNumber);
  };

  const handleSendOTP = () => {
    setErrors({});

    // Validation
    if (!phone) {
      setErrors({phone: 'Phone number is required'});
      return;
    }

    if (!validatePhone(phone)) {
      setErrors({phone: 'Please enter a valid 10-digit Indian phone number'});
      return;
    }

    // Dispatch OTP request
    dispatch(sendOTP(phone))
      .unwrap()
      .then(() => {
        navigation.navigate('OTP', {phone});
      })
      .catch(() => {
        // Error handled by reducer
      });
  };

  const handleSocialLogin = (provider) => {
    // TODO: Implement social login
    console.log(`Login with ${provider}`);
  };

  return (
    <LinearGradient
      colors={[theme.colors.primary, theme.colors.accent]}
      style={styles.container}
      start={{x: 0, y: 0}}
      end={{x: 1, y: 1}}>
      <StatusBar barStyle="light-content" backgroundColor={theme.colors.primary} />
      
      {/* Header */}
      <View style={styles.header}>
        <Image
          source={require('../../../assets/images/logo-white.png')}
          style={styles.logo}
          resizeMode="contain"
        />
        <Text style={styles.welcomeText}>Welcome to GrabBaskets</Text>
        <Text style={styles.subtitleText}>Get groceries delivered in 10 minutes</Text>
      </View>

      {/* Login Form */}
      <Card style={styles.loginCard}>
        <Card.Content style={styles.cardContent}>
          <Text style={styles.loginTitle}>Login with Phone</Text>
          
          <View style={styles.phoneInputContainer}>
            <View style={styles.countryCode}>
              <Text style={styles.countryCodeText}>🇮🇳 +91</Text>
            </View>
            <TextInput
              style={styles.phoneInput}
              value={phone}
              onChangeText={setPhone}
              placeholder="Enter your phone number"
              keyboardType="numeric"
              maxLength={10}
              error={!!errors.phone}
              right={phone.length === 10 && validatePhone(phone) && (
                <TextInput.Icon icon="check" color={theme.colors.success} />
              )}
            />
          </View>
          
          {errors.phone && (
            <HelperText type="error" visible={!!errors.phone}>
              {errors.phone}
            </HelperText>
          )}

          {error && (
            <HelperText type="error" visible={!!error}>
              {error.message || 'Something went wrong'}
            </HelperText>
          )}

          <Button
            mode="contained"
            onPress={handleSendOTP}
            loading={otpLoading}
            disabled={otpLoading || !phone || !validatePhone(phone)}
            style={styles.sendOTPButton}
            contentStyle={styles.buttonContent}>
            {otpLoading ? 'Sending OTP...' : 'Send OTP'}
          </Button>

          {/* Divider */}
          <View style={styles.divider}>
            <View style={styles.dividerLine} />
            <Text style={styles.dividerText}>OR</Text>
            <View style={styles.dividerLine} />
          </View>

          {/* Social Login */}
          <View style={styles.socialContainer}>
            <TouchableOpacity
              style={[styles.socialButton, styles.googleButton]}
              onPress={() => handleSocialLogin('google')}>
              <Icon name="account-circle" size={24} color="#DB4437" />
              <Text style={styles.socialButtonText}>Google</Text>
            </TouchableOpacity>

            <TouchableOpacity
              style={[styles.socialButton, styles.facebookButton]}
              onPress={() => handleSocialLogin('facebook')}>
              <Icon name="facebook" size={24} color="#4267B2" />
              <Text style={styles.socialButtonText}>Facebook</Text>
            </TouchableOpacity>
          </View>

          {/* Guest Continue */}
          <TouchableOpacity
            style={styles.guestButton}
            onPress={() => {
              // TODO: Implement guest flow
              console.log('Continue as guest');
            }}>
            <Text style={styles.guestButtonText}>Continue as Guest</Text>
          </TouchableOpacity>
        </Card.Content>
      </Card>

      {/* Footer */}
      <View style={styles.footer}>
        <Text style={styles.footerText}>
          By continuing, you agree to our{' '}
          <Text style={styles.linkText}>Terms of Service</Text> and{' '}
          <Text style={styles.linkText}>Privacy Policy</Text>
        </Text>
      </View>
    </LinearGradient>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  header: {
    flex: 0.4,
    justifyContent: 'center',
    alignItems: 'center',
    paddingHorizontal: 20,
  },
  logo: {
    width: 80,
    height: 80,
    marginBottom: 20,
  },
  welcomeText: {
    fontSize: 28,
    fontWeight: 'bold',
    color: '#FFFFFF',
    textAlign: 'center',
    marginBottom: 8,
  },
  subtitleText: {
    fontSize: 16,
    color: 'rgba(255, 255, 255, 0.8)',
    textAlign: 'center',
  },
  loginCard: {
    flex: 0.6,
    marginHorizontal: 20,
    marginTop: 20,
    borderTopLeftRadius: 30,
    borderTopRightRadius: 30,
    elevation: 8,
  },
  cardContent: {
    padding: 30,
  },
  loginTitle: {
    fontSize: 24,
    fontWeight: 'bold',
    color: theme.colors.text,
    textAlign: 'center',
    marginBottom: 30,
  },
  phoneInputContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 20,
  },
  countryCode: {
    backgroundColor: theme.colors.lightGray,
    padding: 15,
    borderRadius: 8,
    marginRight: 10,
  },
  countryCodeText: {
    fontSize: 16,
    fontWeight: '500',
  },
  phoneInput: {
    flex: 1,
    backgroundColor: 'transparent',
  },
  sendOTPButton: {
    marginVertical: 20,
    borderRadius: 25,
  },
  buttonContent: {
    paddingVertical: 8,
  },
  divider: {
    flexDirection: 'row',
    alignItems: 'center',
    marginVertical: 20,
  },
  dividerLine: {
    flex: 1,
    height: 1,
    backgroundColor: theme.colors.border,
  },
  dividerText: {
    marginHorizontal: 15,
    color: theme.colors.darkGray,
    fontSize: 14,
  },
  socialContainer: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: 20,
  },
  socialButton: {
    flex: 0.48,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    padding: 12,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: theme.colors.border,
  },
  googleButton: {
    backgroundColor: '#FFFFFF',
  },
  facebookButton: {
    backgroundColor: '#FFFFFF',
  },
  socialButtonText: {
    marginLeft: 8,
    fontSize: 14,
    fontWeight: '500',
    color: theme.colors.text,
  },
  guestButton: {
    alignItems: 'center',
    paddingVertical: 15,
  },
  guestButtonText: {
    color: theme.colors.primary,
    fontSize: 16,
    fontWeight: '500',
  },
  footer: {
    paddingHorizontal: 20,
    paddingBottom: 20,
  },
  footerText: {
    fontSize: 12,
    color: 'rgba(255, 255, 255, 0.8)',
    textAlign: 'center',
    lineHeight: 18,
  },
  linkText: {
    color: '#FFFFFF',
    fontWeight: '500',
    textDecorationLine: 'underline',
  },
});

export default LoginScreen;