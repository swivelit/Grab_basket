import React, { useEffect, useMemo, useState } from 'react';
import {
  ActivityIndicator,
  ScrollView,
  StatusBar,
  StyleSheet,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useBottomTabBarHeight } from '@react-navigation/bottom-tabs';
import { Ionicons } from '@expo/vector-icons';

import { useGrabBasket } from '../../App';
import InlineErrorCard from './inline-error-card';
import InlineNoticeCard from './inline-notice-card';
import { BrandPalette, createShadow } from '../constants/theme';

const VARIANT_COPY = {
  delivery: {
    eyebrow: 'Rider operations',
    title: 'Go online, sync assigned deliveries, and complete every drop from one rider console.',
    subtitle:
      'Use your delivery partner account here. Once signed in, the rider app will pick up the same live order handoff created by the consumer and partner apps.',
    loginCta: 'Sign in to rider app',
    registerCta: 'Create rider account',
    helper: 'Seeded demo login: partner@demo.com / password',
    demoEmail: 'partner@demo.com',
    demoPassword: 'password',
    roleLabel: 'delivery partner',
    icon: 'bicycle-outline',
  },
  partner: {
    eyebrow: 'Merchant operations',
    title: 'Accept orders, manage catalog readiness, and hand deliveries to riders from one partner console.',
    subtitle:
      'Use your seller account here. Once signed in, the partner app will stay in sync with customer checkout, order prep, rider assignment, and live delivery tracking.',
    loginCta: 'Sign in to partner app',
    registerCta: 'Create partner account',
    helper: 'Seeded demo login: seller@demo.com / password',
    demoEmail: 'seller@demo.com',
    demoPassword: 'password',
    roleLabel: 'store partner',
    icon: 'storefront-outline',
  },
};

function Field({ value, onChangeText, placeholder, keyboardType = 'default', secureTextEntry = false }) {
  return (
    <TextInput
      value={value}
      onChangeText={onChangeText}
      placeholder={placeholder}
      placeholderTextColor={BrandPalette.textSubtle}
      keyboardType={keyboardType}
      autoCapitalize="none"
      autoCorrect={false}
      secureTextEntry={secureTextEntry}
      style={styles.field}
    />
  );
}

export default function OperationsAuthScreen({ variant = 'delivery' }) {
  const tabBarHeight = useBottomTabBarHeight();
  const {
    appVariantName,
    sessionReady,
    isAuthenticated,
    authLoading,
    authEmail,
    login,
    register,
    inlineErrors,
  } = useGrabBasket();

  const copy = useMemo(() => VARIANT_COPY[variant] || VARIANT_COPY.delivery, [variant]);

  const [authMode, setAuthMode] = useState('login');
  const [loginIdentifier, setLoginIdentifier] = useState(authEmail || copy.demoEmail);
  const [email, setEmail] = useState('');
  const [phone, setPhone] = useState('');
  const [password, setPassword] = useState(copy.demoPassword);
  const [localNotice, setLocalNotice] = useState('');

  useEffect(() => {
    if (authEmail) {
      setLoginIdentifier(authEmail);
    }
  }, [authEmail]);

  useEffect(() => {
    setPassword(copy.demoPassword);
  }, [copy.demoPassword]);

  const fillDemoCredentials = () => {
    setAuthMode('login');
    setLoginIdentifier(copy.demoEmail);
    setPassword(copy.demoPassword);
    setLocalNotice(`Filled the seeded ${copy.roleLabel} demo credentials.`);
  };

  const handleAuth = async () => {
    setLocalNotice('');

    if (authMode === 'login') {
      if (!loginIdentifier.trim() || !password.trim()) {
        setLocalNotice('Enter your email or phone number and password.');
        return;
      }

      const ok = await login({ identifier: loginIdentifier, password });
      if (ok) {
        setLocalNotice(`Signed in to ${appVariantName}.`);
      }
      return;
    }

    if (!email.trim() || !phone.trim() || !password.trim()) {
      setLocalNotice('Enter your email, phone number and password.');
      return;
    }

    const ok = await register({ email, phone, password });
    if (ok) {
      setAuthMode('login');
      setLoginIdentifier(phone.trim() || email.trim());
      setLocalNotice(`Your ${copy.roleLabel} account is ready.`);
    }
  };

  if (!sessionReady) {
    return (
      <SafeAreaView style={styles.safeArea} edges={['top']}>
        <StatusBar barStyle="dark-content" />
        <View style={styles.loadingState}>
          <ActivityIndicator color={BrandPalette.primary} />
          <Text style={styles.loadingTitle}>Preparing {appVariantName}</Text>
          <Text style={styles.loadingSubtitle}>Loading the latest authenticated state.</Text>
        </View>
      </SafeAreaView>
    );
  }

  if (isAuthenticated) {
    return null;
  }

  return (
    <SafeAreaView style={styles.safeArea} edges={['top']}>
      <StatusBar barStyle="dark-content" />
      <ScrollView
        showsVerticalScrollIndicator={false}
        contentContainerStyle={{ paddingBottom: tabBarHeight + 28 }}>
        <View style={styles.heroCard}>
          <View style={styles.heroIconWrap}>
            <Ionicons name={copy.icon} size={22} color={BrandPalette.primary} />
          </View>
          <Text style={styles.eyebrow}>{copy.eyebrow}</Text>
          <Text style={styles.heroTitle}>{copy.title}</Text>
          <Text style={styles.heroSubtitle}>{copy.subtitle}</Text>
        </View>

        <View style={styles.sheetCard}>
          <View style={styles.toggleRow}>
            <TouchableOpacity
              activeOpacity={0.94}
              style={[styles.toggle, authMode === 'login' && styles.toggleActive]}
              onPress={() => setAuthMode('login')}>
              <Text style={[styles.toggleText, authMode === 'login' && styles.toggleTextActive]}>Login</Text>
            </TouchableOpacity>
            <TouchableOpacity
              activeOpacity={0.94}
              style={[styles.toggle, authMode === 'register' && styles.toggleActive]}
              onPress={() => setAuthMode('register')}>
              <Text style={[styles.toggleText, authMode === 'register' && styles.toggleTextActive]}>Sign up</Text>
            </TouchableOpacity>
          </View>

          {inlineErrors.auth ? <InlineErrorCard title="Authentication issue" message={inlineErrors.auth} /> : null}
          {localNotice ? (
            <InlineNoticeCard title="Ready" message={localNotice} onDismiss={() => setLocalNotice('')} />
          ) : null}

          {authMode === 'login' ? (
            <>
              <Field
                value={loginIdentifier}
                onChangeText={setLoginIdentifier}
                placeholder="Email or phone number"
              />
              <Field value={password} onChangeText={setPassword} placeholder="Password" secureTextEntry />
              <Text style={styles.helperText}>{copy.helper}</Text>
              <TouchableOpacity activeOpacity={0.9} style={styles.demoChip} onPress={fillDemoCredentials}>
                <Ionicons name="flash-outline" size={14} color={BrandPalette.primary} />
                <Text style={styles.demoChipText}>Use seeded demo credentials</Text>
              </TouchableOpacity>
            </>
          ) : (
            <>
              <Field value={email} onChangeText={setEmail} placeholder="Email address" keyboardType="email-address" />
              <Field value={phone} onChangeText={setPhone} placeholder="Phone number" keyboardType="phone-pad" />
              <Field value={password} onChangeText={setPassword} placeholder="Password" secureTextEntry />
              <Text style={styles.helperText}>
                New {copy.roleLabel} accounts can sign in here immediately after registration.
              </Text>
            </>
          )}

          <TouchableOpacity
            activeOpacity={0.95}
            style={styles.primaryButton}
            onPress={handleAuth}
            disabled={authLoading}>
            {authLoading ? (
              <ActivityIndicator color="#FFFFFF" size="small" />
            ) : (
              <Text style={styles.primaryButtonText}>
                {authMode === 'login' ? copy.loginCta : copy.registerCta}
              </Text>
            )}
          </TouchableOpacity>

          <Text style={styles.legalText}>
            By continuing, you agree to the Grab Basket platform terms and privacy policy.
          </Text>
        </View>
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: {
    flex: 1,
    backgroundColor: BrandPalette.page,
  },
  loadingState: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 24,
    gap: 10,
  },
  loadingTitle: {
    fontSize: 20,
    fontWeight: '800',
    color: BrandPalette.text,
    textAlign: 'center',
  },
  loadingSubtitle: {
    fontSize: 14,
    lineHeight: 21,
    color: BrandPalette.textMuted,
    textAlign: 'center',
  },
  heroCard: {
    marginHorizontal: 16,
    marginTop: 14,
    padding: 22,
    borderRadius: 28,
    backgroundColor: BrandPalette.surface,
    borderWidth: 1,
    borderColor: BrandPalette.border,
    gap: 10,
    ...createShadow(0.08, 16, 8),
  },
  heroIconWrap: {
    width: 44,
    height: 44,
    borderRadius: 22,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: BrandPalette.primarySoft,
  },
  eyebrow: {
    fontSize: 12,
    fontWeight: '800',
    letterSpacing: 0.5,
    textTransform: 'uppercase',
    color: BrandPalette.primary,
  },
  heroTitle: {
    fontSize: 25,
    lineHeight: 32,
    fontWeight: '900',
    color: BrandPalette.text,
  },
  heroSubtitle: {
    fontSize: 14,
    lineHeight: 22,
    color: BrandPalette.textMuted,
  },
  sheetCard: {
    marginHorizontal: 16,
    marginTop: 16,
    borderRadius: 28,
    backgroundColor: BrandPalette.surface,
    borderWidth: 1,
    borderColor: BrandPalette.border,
    padding: 18,
    gap: 14,
    ...createShadow(0.08, 16, 8),
  },
  toggleRow: {
    flexDirection: 'row',
    backgroundColor: BrandPalette.surfaceAlt,
    borderRadius: 999,
    padding: 4,
  },
  toggle: {
    flex: 1,
    borderRadius: 999,
    paddingVertical: 11,
    alignItems: 'center',
    justifyContent: 'center',
  },
  toggleActive: {
    backgroundColor: '#FFFFFF',
  },
  toggleText: {
    fontSize: 14,
    fontWeight: '700',
    color: BrandPalette.textMuted,
  },
  toggleTextActive: {
    color: BrandPalette.text,
  },
  field: {
    minHeight: 54,
    borderRadius: 18,
    borderWidth: 1,
    borderColor: BrandPalette.border,
    backgroundColor: '#FFFFFF',
    paddingHorizontal: 16,
    fontSize: 15,
    color: BrandPalette.text,
  },
  helperText: {
    fontSize: 12,
    lineHeight: 18,
    color: BrandPalette.textMuted,
  },
  demoChip: {
    alignSelf: 'flex-start',
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    borderRadius: 999,
    borderWidth: 1,
    borderColor: BrandPalette.border,
    backgroundColor: '#FFFFFF',
    paddingHorizontal: 12,
    paddingVertical: 9,
  },
  demoChipText: {
    fontSize: 12,
    fontWeight: '700',
    color: BrandPalette.text,
  },
  primaryButton: {
    minHeight: 54,
    borderRadius: 18,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: BrandPalette.primary,
  },
  primaryButtonText: {
    fontSize: 15,
    fontWeight: '800',
    color: '#FFFFFF',
  },
  legalText: {
    fontSize: 12,
    lineHeight: 18,
    color: BrandPalette.textSubtle,
    textAlign: 'center',
  },
});