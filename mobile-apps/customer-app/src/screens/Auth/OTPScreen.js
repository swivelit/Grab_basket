import React, {useState, useRef, useEffect} from 'react';
import {
  View,
  StyleSheet,
  TouchableOpacity,
  Dimensions,
  StatusBar,
  TextInput as RNTextInput,
} from 'react-native';
import {
  Text,
  Button,
  Card,
  HelperText,
} from 'react-native-paper';
import {useDispatch, useSelector} from 'react-redux';
import LinearGradient from 'react-native-linear-gradient';
import Icon from 'react-native-vector-icons/MaterialIcons';

import {verifyOTP, resendOTP} from '../../store/slices/authSlice';
import theme from '../../config/theme';

const {width, height} = Dimensions.get('window');

const OTPScreen = ({route, navigation}) => {
  const {phone} = route.params;
  const [otp, setOtp] = useState(['', '', '', '', '', '']);
  const [resendTimer, setResendTimer] = useState(60);
  const [errors, setErrors] = useState({});
  
  const dispatch = useDispatch();
  const {verifyLoading, error} = useSelector(state => state.auth);
  
  // Refs for OTP inputs
  const otpRefs = [
    useRef(null),
    useRef(null),
    useRef(null),
    useRef(null),
    useRef(null),
    useRef(null),
  ];

  // Resend timer countdown
  useEffect(() => {
    if (resendTimer > 0) {
      const timer = setTimeout(() => setResendTimer(resendTimer - 1), 1000);
      return () => clearTimeout(timer);
    }
  }, [resendTimer]);

  const handleOTPChange = (value, index) => {
    // Only allow numbers
    if (!/^\d*$/.test(value)) return;

    const newOtp = [...otp];
    newOtp[index] = value;
    setOtp(newOtp);

    // Auto-focus next input
    if (value && index < 5) {
      otpRefs[index + 1].current?.focus();
    }

    // Auto-submit when all 6 digits are entered
    if (index === 5 && value) {
      const fullOtp = newOtp.join('');
      if (fullOtp.length === 6) {
        handleVerifyOTP(fullOtp);
      }
    }
  };

  const handleKeyPress = (e, index) => {
    if (e.nativeEvent.key === 'Backspace' && !otp[index] && index > 0) {
      otpRefs[index - 1].current?.focus();
    }
  };

  const handleVerifyOTP = (otpValue = otp.join('')) => {
    setErrors({});

    if (otpValue.length !== 6) {
      setErrors({otp: 'Please enter complete 6-digit OTP'});
      return;
    }

    dispatch(verifyOTP({phone, otp: otpValue}))
      .unwrap()
      .then(() => {
        // Navigation handled by AppNavigator based on auth state
      })
      .catch(() => {
        // Error handled by reducer
      });
  };

  const handleResendOTP = () => {
    if (resendTimer > 0) return;

    dispatch(resendOTP(phone))
      .unwrap()
      .then(() => {
        setResendTimer(60);
        setOtp(['', '', '', '', '', '']);
        otpRefs[0].current?.focus();
      })
      .catch(() => {
        // Error handled by reducer
      });
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
        <TouchableOpacity
          style={styles.backButton}
          onPress={() => navigation.goBack()}>
          <Icon name="arrow-back" size={24} color="#FFFFFF" />
        </TouchableOpacity>
        
        <View style={styles.iconContainer}>
          <Icon name="message" size={60} color="#FFFFFF" />
        </View>
        
        <Text style={styles.headerTitle}>Verify Phone Number</Text>
        <Text style={styles.headerSubtitle}>
          Enter the 6-digit code sent to{'\n'}
          <Text style={styles.phoneNumber}>+91 {phone}</Text>
        </Text>
      </View>

      {/* OTP Input Card */}
      <Card style={styles.otpCard}>
        <Card.Content style={styles.cardContent}>
          {/* OTP Input */}
          <View style={styles.otpInputContainer}>
            {otp.map((digit, index) => (
              <RNTextInput
                key={index}
                ref={otpRefs[index]}
                style={[
                  styles.otpInput,
                  digit ? styles.otpInputFilled : {},
                  errors.otp ? styles.otpInputError : {},
                ]}
                value={digit}
                onChangeText={(value) => handleOTPChange(value, index)}
                onKeyPress={(e) => handleKeyPress(e, index)}
                keyboardType="numeric"
                maxLength={1}
                selectTextOnFocus
              />
            ))}
          </View>

          {errors.otp && (
            <HelperText type="error" visible={!!errors.otp} style={styles.errorText}>
              {errors.otp}
            </HelperText>
          )}

          {error && (
            <HelperText type="error" visible={!!error} style={styles.errorText}>
              {error.message || 'Invalid OTP. Please try again.'}
            </HelperText>
          )}

          {/* Verify Button */}
          <Button
            mode="contained"
            onPress={() => handleVerifyOTP()}
            loading={verifyLoading}
            disabled={verifyLoading || otp.join('').length !== 6}
            style={styles.verifyButton}
            contentStyle={styles.buttonContent}>
            {verifyLoading ? 'Verifying...' : 'Verify OTP'}
          </Button>

          {/* Resend OTP */}
          <View style={styles.resendContainer}>
            <Text style={styles.resendText}>Didn't receive the code?</Text>
            <TouchableOpacity
              onPress={handleResendOTP}
              disabled={resendTimer > 0}>
              <Text
                style={[
                  styles.resendButton,
                  resendTimer > 0 && styles.resendButtonDisabled,
                ]}>
                {resendTimer > 0
                  ? `Resend in ${resendTimer}s`
                  : 'Resend OTP'}
              </Text>
            </TouchableOpacity>
          </View>

          {/* Help Text */}
          <View style={styles.helpContainer}>
            <Icon name="info-outline" size={16} color={theme.colors.darkGray} />
            <Text style={styles.helpText}>
              Please enter the OTP sent via SMS to your registered mobile number
            </Text>
          </View>
        </Card.Content>
      </Card>
    </LinearGradient>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  header: {
    flex: 0.35,
    justifyContent: 'center',
    alignItems: 'center',
    paddingHorizontal: 20,
    paddingTop: 40,
  },
  backButton: {
    position: 'absolute',
    top: 40,
    left: 20,
    padding: 8,
  },
  iconContainer: {
    width: 100,
    height: 100,
    borderRadius: 50,
    backgroundColor: 'rgba(255, 255, 255, 0.2)',
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: 20,
  },
  headerTitle: {
    fontSize: 26,
    fontWeight: 'bold',
    color: '#FFFFFF',
    textAlign: 'center',
    marginBottom: 10,
  },
  headerSubtitle: {
    fontSize: 15,
    color: 'rgba(255, 255, 255, 0.9)',
    textAlign: 'center',
    lineHeight: 22,
  },
  phoneNumber: {
    fontWeight: 'bold',
    fontSize: 16,
  },
  otpCard: {
    flex: 0.65,
    marginHorizontal: 20,
    marginTop: 20,
    borderTopLeftRadius: 30,
    borderTopRightRadius: 30,
    elevation: 8,
  },
  cardContent: {
    padding: 30,
  },
  otpInputContainer: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: 30,
  },
  otpInput: {
    width: 45,
    height: 55,
    borderWidth: 2,
    borderColor: theme.colors.border,
    borderRadius: 12,
    textAlign: 'center',
    fontSize: 24,
    fontWeight: 'bold',
    backgroundColor: '#F8F8F8',
    color: theme.colors.text,
  },
  otpInputFilled: {
    borderColor: theme.colors.primary,
    backgroundColor: '#FFFFFF',
  },
  otpInputError: {
    borderColor: theme.colors.error,
  },
  errorText: {
    textAlign: 'center',
    marginBottom: 10,
  },
  verifyButton: {
    marginVertical: 20,
    borderRadius: 25,
  },
  buttonContent: {
    paddingVertical: 8,
  },
  resendContainer: {
    flexDirection: 'row',
    justifyContent: 'center',
    alignItems: 'center',
    marginTop: 20,
  },
  resendText: {
    fontSize: 14,
    color: theme.colors.darkGray,
    marginRight: 5,
  },
  resendButton: {
    fontSize: 14,
    color: theme.colors.primary,
    fontWeight: '600',
  },
  resendButtonDisabled: {
    color: theme.colors.darkGray,
  },
  helpContainer: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    marginTop: 30,
    paddingHorizontal: 10,
  },
  helpText: {
    fontSize: 12,
    color: theme.colors.darkGray,
    marginLeft: 8,
    flex: 1,
    lineHeight: 18,
  },
});

export default OTPScreen;
