import React, { useEffect, useMemo, useState } from 'react';
import {
  ActivityIndicator,
  KeyboardAvoidingView,
  Modal,
  Platform,
  ScrollView,
  Share,
  StatusBar,
  StyleSheet,
  Switch,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from 'react-native';
import * as Location from 'expo-location';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import { useRouter } from 'expo-router';

import { BrandPalette, createShadow } from '@/constants/theme';
import InlineErrorCard from '@/components/inline-error-card';
import InlineNoticeCard from '@/components/inline-notice-card';
import { useGrabBasket } from '../../App';

const ADDRESS_LABEL_OPTIONS = ['Home', 'Work', 'Other'];

function firstNonEmpty(...values) {
  return (
    values
      .map((value) => String(value || '').trim())
      .find(Boolean) || ''
  );
}

function formatAddressLines(address) {
  if (!address) return [];

  const primary = firstNonEmpty(address.line1);
  const secondary = [address.line2, address.city, address.pincode].filter(Boolean).join(', ');

  return [primary, secondary].filter(Boolean);
}

function getAddressIcon(label = '') {
  const normalized = String(label || '').trim().toLowerCase();

  if (normalized.includes('home')) return 'home-outline';
  if (normalized.includes('work') || normalized.includes('office')) return 'briefcase-outline';
  return 'navigate-outline';
}

function buildShareMessage(address, phoneNumber) {
  const lines = [
    String(address?.label || 'Address').trim(),
    address?.line1,
    address?.line2,
    [address?.city, address?.pincode].filter(Boolean).join(' - '),
    phoneNumber ? `Phone number: ${phoneNumber}` : '',
  ]
    .map((item) => String(item || '').trim())
    .filter(Boolean);

  return lines.join('\n');
}

function EmptyState({ onPressAdd }) {
  return (
    <View style={styles.emptyWrap}>
      <View style={styles.emptyIconWrap}>
        <Ionicons name="location-outline" size={28} color={BrandPalette.subtle} />
      </View>
      <Text style={styles.emptyTitle}>No saved addresses yet</Text>
      <Text style={styles.emptySubtitle}>Add your home, work or stay address so checkout becomes faster.</Text>
      <TouchableOpacity activeOpacity={0.92} style={styles.emptyButton} onPress={onPressAdd}>
        <Text style={styles.emptyButtonText}>Add your first address</Text>
      </TouchableOpacity>
    </View>
  );
}

function SearchBar({ value, onChangeText }) {
  return (
    <View style={styles.searchWrap}>
      <Ionicons name="search-outline" size={22} color={BrandPalette.subtle} />
      <TextInput
        value={value}
        onChangeText={onChangeText}
        placeholder="Search your saved addresses"
        placeholderTextColor={BrandPalette.subtle}
        style={styles.searchInput}
      />
    </View>
  );
}

function AddressCard({ address, phoneNumber, isSelected, onPress, onPressEdit, onPressDelete, onPressShare }) {
  const lines = formatAddressLines(address);

  return (
    <TouchableOpacity activeOpacity={0.96} style={styles.addressCard} onPress={onPress}>
      <View style={styles.addressRow}>
        <View style={styles.addressIconWrap}>
          <Ionicons name={getAddressIcon(address?.label)} size={24} color={BrandPalette.text} />
        </View>

        <View style={styles.addressContent}>
          <View style={styles.addressTitleRow}>
            <Text style={styles.addressTitle}>{address?.label || 'Address'}</Text>
            {isSelected ? (
              <View style={styles.defaultPill}>
                <Text style={styles.defaultPillText}>DEFAULT</Text>
              </View>
            ) : null}
          </View>

          {lines.map((line) => (
            <Text key={`${address?.id}-${line}`} style={styles.addressLine}>
              {line}
            </Text>
          ))}

          {phoneNumber ? <Text style={styles.addressPhone}>Phone number: {phoneNumber}</Text> : null}

          <View style={styles.actionRow}>
            <TouchableOpacity activeOpacity={0.85} onPress={onPressEdit}>
              <Text style={styles.actionText}>EDIT</Text>
            </TouchableOpacity>
            <TouchableOpacity activeOpacity={0.85} onPress={onPressDelete}>
              <Text style={styles.actionText}>DELETE</Text>
            </TouchableOpacity>
            <TouchableOpacity activeOpacity={0.85} onPress={onPressShare}>
              <Text style={styles.actionText}>SHARE</Text>
            </TouchableOpacity>
          </View>
        </View>
      </View>
    </TouchableOpacity>
  );
}

export default function SavedAddressesScreen() {
  const router = useRouter();
  const {
    isAuthenticated,
    profile,
    addresses,
    defaultAddress,
    addressesLoading,
    inlineErrors,
    currentLocationLoading,
    loadAddresses,
    createAddress,
    setDefaultAddress,
    resolveCurrentLocation,
  } = useGrabBasket();

  const [searchQuery, setSearchQuery] = useState('');
  const [notice, setNotice] = useState('');
  const [modalVisible, setModalVisible] = useState(false);
  const [savingAddress, setSavingAddress] = useState(false);
  const [prefillingLocation, setPrefillingLocation] = useState(false);
  const [selectedLabel, setSelectedLabel] = useState('Home');
  const [customLabel, setCustomLabel] = useState('');
  const [line1, setLine1] = useState('');
  const [line2, setLine2] = useState('');
  const [city, setCity] = useState('');
  const [pincode, setPincode] = useState('');
  const [saveAsDefault, setSaveAsDefault] = useState(true);
  const [draftCoords, setDraftCoords] = useState({ lat: null, lng: null });

  useEffect(() => {
    if (!isAuthenticated) return;
    loadAddresses().catch(() => {});
  }, [isAuthenticated, loadAddresses]);

  const phoneNumber = useMemo(
    () => firstNonEmpty(profile?.phone, profile?.email),
    [profile?.email, profile?.phone]
  );

  const filteredAddresses = useMemo(() => {
    const query = String(searchQuery || '').trim().toLowerCase();
    const ordered = [...(Array.isArray(addresses) ? addresses : [])].sort((left, right) => {
      if (left?.is_default && !right?.is_default) return -1;
      if (!left?.is_default && right?.is_default) return 1;
      return Number(right?.id || 0) - Number(left?.id || 0);
    });

    if (!query) return ordered;

    return ordered.filter((address) =>
      [address?.label, address?.line1, address?.line2, address?.city, address?.pincode]
        .join(' ')
        .toLowerCase()
        .includes(query)
    );
  }, [addresses, searchQuery]);

  const resetForm = () => {
    setSelectedLabel('Home');
    setCustomLabel('');
    setLine1('');
    setLine2('');
    setCity('');
    setPincode('');
    setSaveAsDefault(!addresses?.length);
    setDraftCoords({ lat: null, lng: null });
  };

  const openAddAddressModal = () => {
    resetForm();
    setModalVisible(true);
  };

  const closeAddAddressModal = () => {
    setModalVisible(false);
    resetForm();
  };

  const handleUseCurrentLocation = async () => {
    try {
      setPrefillingLocation(true);
      const current = await resolveCurrentLocation({ force: true });

      if (!current) {
        setNotice('Location permission is needed to prefill from your current location.');
        return;
      }

      setLine1(firstNonEmpty(current.line1));
      setLine2(firstNonEmpty(current.line2));
      setCity(firstNonEmpty(current.city));
      setPincode(firstNonEmpty(current.pincode));
      setDraftCoords({ lat: Number(current.lat), lng: Number(current.lng) });
      setNotice('Current location added to the form.');
    } finally {
      setPrefillingLocation(false);
    }
  };

  const handleSaveAddress = async () => {
    const resolvedLabel = selectedLabel === 'Other' ? firstNonEmpty(customLabel, 'Other') : selectedLabel;

    if (!line1.trim()) {
      setNotice('Please enter address line 1.');
      return;
    }

    if (!city.trim()) {
      setNotice('Please enter the city.');
      return;
    }

    try {
      setSavingAddress(true);

      let lat = Number(draftCoords.lat);
      let lng = Number(draftCoords.lng);

      if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
        const geocodeQuery = [line1, line2, city, pincode, 'India'].filter(Boolean).join(', ');
        const geocodeResult = await Location.geocodeAsync(geocodeQuery);
        const match = Array.isArray(geocodeResult) ? geocodeResult[0] || null : null;

        if (!match) {
          setNotice('Could not find this address on the map. Try using current location or add a more complete address.');
          return;
        }

        lat = Number(match.latitude);
        lng = Number(match.longitude);
      }

      const created = await createAddress({
        label: resolvedLabel,
        line1,
        line2,
        city,
        pincode,
        lat,
        lng,
        is_default: saveAsDefault,
      });

      if (!created) return;

      setNotice(`${resolvedLabel} address added successfully.`);
      setModalVisible(false);
      resetForm();
      loadAddresses({ silent: true }).catch(() => {});
    } catch {
      setNotice('Could not save the address right now.');
    } finally {
      setSavingAddress(false);
    }
  };

  const handleAddressPress = async (address) => {
    if (!address?.id) return;
    if (String(defaultAddress?.id) === String(address.id)) return;

    const ok = await setDefaultAddress(address.id);
    if (ok) {
      setNotice(`${address.label || 'Address'} is now your default address.`);
    }
  };

  const handleShare = async (address) => {
    try {
      await Share.share({
        message: buildShareMessage(address, phoneNumber),
      });
    } catch {
      setNotice('Could not open the share sheet.');
    }
  };

  const handleUnavailableAction = (actionLabel) => {
    setNotice(`${actionLabel} can be connected once the address update/delete API is added.`);
  };

  if (!isAuthenticated) {
    return (
      <SafeAreaView style={styles.safeArea} edges={['top']}>
        <StatusBar barStyle="dark-content" />
        <View style={styles.header}>
          <TouchableOpacity activeOpacity={0.85} hitSlop={{ top: 10, bottom: 10, left: 10, right: 10 }} onPress={() => router.back()}>
            <Ionicons name="arrow-back" size={28} color={BrandPalette.text} />
          </TouchableOpacity>
          <Text style={styles.headerTitle}>ADDRESSES</Text>
          <View style={styles.headerSpacer} />
        </View>

        <View style={styles.centerState}>
          <Ionicons name="lock-closed-outline" size={28} color={BrandPalette.subtle} />
          <Text style={styles.centerStateTitle}>Sign in to view saved addresses</Text>
          <Text style={styles.centerStateSubtitle}>Your saved delivery places will appear here after login.</Text>
        </View>
      </SafeAreaView>
    );
  }

  return (
    <SafeAreaView style={styles.safeArea} edges={['top']}>
      <StatusBar barStyle="dark-content" />

      <View style={styles.header}>
        <TouchableOpacity activeOpacity={0.85} hitSlop={{ top: 10, bottom: 10, left: 10, right: 10 }} onPress={() => router.back()}>
          <Ionicons name="arrow-back" size={28} color={BrandPalette.text} />
        </TouchableOpacity>
        <Text style={styles.headerTitle}>ADDRESSES</Text>
        <View style={styles.headerSpacer} />
      </View>

      <ScrollView
        showsVerticalScrollIndicator={false}
        keyboardShouldPersistTaps="handled"
        contentContainerStyle={styles.scrollContent}>
        <SearchBar value={searchQuery} onChangeText={setSearchQuery} />

        {inlineErrors.addresses ? <InlineErrorCard title="Address issue" message={inlineErrors.addresses} /> : null}
        {notice ? <InlineNoticeCard title="Updated" message={notice} onDismiss={() => setNotice('')} /> : null}

        <Text style={styles.sectionLabel}>SAVED ADDRESSES</Text>

        {addressesLoading ? (
          <View style={styles.loadingWrap}>
            <ActivityIndicator color={BrandPalette.primary} />
            <Text style={styles.loadingText}>Loading your addresses...</Text>
          </View>
        ) : filteredAddresses.length ? (
          <View style={styles.addressList}>
            {filteredAddresses.map((address, index) => (
              <View key={String(address?.id || index)}>
                <AddressCard
                  address={address}
                  phoneNumber={phoneNumber}
                  isSelected={String(defaultAddress?.id) === String(address?.id)}
                  onPress={() => handleAddressPress(address)}
                  onPressEdit={() => handleUnavailableAction('Edit')}
                  onPressDelete={() => handleUnavailableAction('Delete')}
                  onPressShare={() => handleShare(address)}
                />
                {index < filteredAddresses.length - 1 ? <View style={styles.itemDivider} /> : null}
              </View>
            ))}
          </View>
        ) : searchQuery.trim() ? (
          <View style={styles.searchEmptyWrap}>
            <Text style={styles.searchEmptyTitle}>No addresses found</Text>
            <Text style={styles.searchEmptySubtitle}>Try a different search term or add a new address.</Text>
          </View>
        ) : (
          <EmptyState onPressAdd={openAddAddressModal} />
        )}
      </ScrollView>

      <View style={styles.bottomCtaWrap}>
        <TouchableOpacity activeOpacity={0.94} style={styles.bottomCtaButton} onPress={openAddAddressModal}>
          <Text style={styles.bottomCtaText}>ADD NEW ADDRESS</Text>
        </TouchableOpacity>
      </View>

      <Modal visible={modalVisible} animationType="slide" transparent onRequestClose={closeAddAddressModal}>
        <View style={styles.modalBackdrop}>
          <KeyboardAvoidingView
            behavior={Platform.OS === 'ios' ? 'padding' : undefined}
            style={styles.modalKeyboardWrap}>
            <View style={styles.modalSheet}>
              <View style={styles.modalHandle} />

              <View style={styles.modalHeader}>
                <Text style={styles.modalTitle}>Add new address</Text>
                <TouchableOpacity activeOpacity={0.85} onPress={closeAddAddressModal}>
                  <Ionicons name="close" size={24} color={BrandPalette.text} />
                </TouchableOpacity>
              </View>

              <ScrollView showsVerticalScrollIndicator={false} keyboardShouldPersistTaps="handled">
                <Text style={styles.formSectionLabel}>Save as</Text>
                <View style={styles.labelChipRow}>
                  {ADDRESS_LABEL_OPTIONS.map((option) => {
                    const active = selectedLabel === option;
                    return (
                      <TouchableOpacity
                        key={option}
                        activeOpacity={0.9}
                        style={[styles.labelChip, active && styles.labelChipActive]}
                        onPress={() => setSelectedLabel(option)}>
                        <Text style={[styles.labelChipText, active && styles.labelChipTextActive]}>{option}</Text>
                      </TouchableOpacity>
                    );
                  })}
                </View>

                {selectedLabel === 'Other' ? (
                  <TextInput
                    value={customLabel}
                    onChangeText={setCustomLabel}
                    placeholder="Label name"
                    placeholderTextColor={BrandPalette.subtle}
                    style={styles.input}
                  />
                ) : null}

                <TouchableOpacity activeOpacity={0.9} style={styles.locationButton} onPress={handleUseCurrentLocation}>
                  {prefillingLocation || currentLocationLoading ? (
                    <ActivityIndicator size="small" color={BrandPalette.primary} />
                  ) : (
                    <Ionicons name="locate-outline" size={18} color={BrandPalette.primary} />
                  )}
                  <Text style={styles.locationButtonText}>Use current location</Text>
                </TouchableOpacity>

                <TextInput
                  value={line1}
                  onChangeText={(value) => {
                    setLine1(value);
                    setDraftCoords({ lat: null, lng: null });
                  }}
                  placeholder="Flat / House no / Building / Street"
                  placeholderTextColor={BrandPalette.subtle}
                  style={styles.input}
                />
                <TextInput
                  value={line2}
                  onChangeText={(value) => {
                    setLine2(value);
                    setDraftCoords({ lat: null, lng: null });
                  }}
                  placeholder="Area / Landmark"
                  placeholderTextColor={BrandPalette.subtle}
                  style={styles.input}
                />
                <TextInput
                  value={city}
                  onChangeText={(value) => {
                    setCity(value);
                    setDraftCoords({ lat: null, lng: null });
                  }}
                  placeholder="City"
                  placeholderTextColor={BrandPalette.subtle}
                  style={styles.input}
                />
                <TextInput
                  value={pincode}
                  onChangeText={(value) => {
                    setPincode(value.replace(/[^0-9]/g, ''));
                    setDraftCoords({ lat: null, lng: null });
                  }}
                  placeholder="Pincode"
                  placeholderTextColor={BrandPalette.subtle}
                  keyboardType="number-pad"
                  maxLength={6}
                  style={styles.input}
                />

                <View style={styles.defaultToggleRow}>
                  <View style={styles.defaultToggleCopy}>
                    <Text style={styles.defaultToggleTitle}>Set as default address</Text>
                    <Text style={styles.defaultToggleSubtitle}>Use this address automatically for the next checkout.</Text>
                  </View>
                  <Switch
                    value={saveAsDefault}
                    onValueChange={setSaveAsDefault}
                    trackColor={{ false: '#D8D8D8', true: BrandPalette.successSoft }}
                    thumbColor={saveAsDefault ? BrandPalette.success : '#F4F4F4'}
                  />
                </View>

                <TouchableOpacity
                  activeOpacity={0.92}
                  style={[styles.saveButton, savingAddress && styles.saveButtonDisabled]}
                  disabled={savingAddress}
                  onPress={handleSaveAddress}>
                  {savingAddress ? <ActivityIndicator size="small" color={BrandPalette.white} /> : <Text style={styles.saveButtonText}>SAVE ADDRESS</Text>}
                </TouchableOpacity>
              </ScrollView>
            </View>
          </KeyboardAvoidingView>
        </View>
      </Modal>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: {
    flex: 1,
    backgroundColor: BrandPalette.white,
  },
  header: {
    height: 64,
    paddingHorizontal: 16,
    borderBottomWidth: 1,
    borderBottomColor: '#ECECEC',
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    backgroundColor: BrandPalette.white,
  },
  headerTitle: {
    flex: 1,
    marginLeft: 16,
    color: '#1F2430',
    fontSize: 18,
    fontWeight: '800',
    letterSpacing: 0.6,
  },
  headerSpacer: {
    width: 28,
  },
  scrollContent: {
    paddingHorizontal: 16,
    paddingTop: 14,
    paddingBottom: 112,
  },
  searchWrap: {
    minHeight: 54,
    borderRadius: 18,
    borderWidth: 1,
    borderColor: '#D7D7D7',
    paddingHorizontal: 16,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    backgroundColor: BrandPalette.white,
  },
  searchInput: {
    flex: 1,
    color: BrandPalette.text,
    fontSize: 17,
    paddingVertical: 12,
  },
  sectionLabel: {
    marginTop: 26,
    marginBottom: 18,
    color: '#848484',
    fontSize: 13,
    fontWeight: '700',
    letterSpacing: 0.8,
  },
  addressList: {
    backgroundColor: BrandPalette.white,
  },
  addressCard: {
    paddingVertical: 12,
  },
  addressRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: 14,
  },
  addressIconWrap: {
    width: 34,
    alignItems: 'center',
    paddingTop: 4,
  },
  addressContent: {
    flex: 1,
  },
  addressTitleRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    flexWrap: 'wrap',
    marginBottom: 6,
  },
  addressTitle: {
    color: '#252A36',
    fontSize: 18,
    fontWeight: '800',
  },
  defaultPill: {
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 999,
    backgroundColor: BrandPalette.successSoft,
  },
  defaultPillText: {
    color: BrandPalette.success,
    fontSize: 11,
    fontWeight: '800',
    letterSpacing: 0.4,
  },
  addressLine: {
    color: '#5E5E5E',
    fontSize: 15,
    lineHeight: 24,
  },
  addressPhone: {
    marginTop: 6,
    color: '#5E5E5E',
    fontSize: 15,
    lineHeight: 22,
  },
  actionRow: {
    marginTop: 14,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 28,
  },
  actionText: {
    color: '#F16622',
    fontSize: 14,
    fontWeight: '900',
    letterSpacing: 0.3,
  },
  itemDivider: {
    height: 1,
    backgroundColor: '#EBEBEB',
    marginVertical: 4,
  },
  bottomCtaWrap: {
    position: 'absolute',
    left: 16,
    right: 16,
    bottom: 18,
    backgroundColor: BrandPalette.white,
  },
  bottomCtaButton: {
    minHeight: 54,
    borderRadius: 0,
    borderWidth: 2,
    borderColor: BrandPalette.success,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#F8FFF9',
  },
  bottomCtaText: {
    color: BrandPalette.success,
    fontSize: 17,
    fontWeight: '900',
    letterSpacing: 0.4,
  },
  loadingWrap: {
    paddingVertical: 32,
    alignItems: 'center',
    justifyContent: 'center',
    gap: 10,
  },
  loadingText: {
    color: BrandPalette.textMuted,
    fontSize: 14,
  },
  emptyWrap: {
    paddingVertical: 28,
    alignItems: 'center',
    justifyContent: 'center',
  },
  emptyIconWrap: {
    width: 64,
    height: 64,
    borderRadius: 32,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#F4F4F4',
    marginBottom: 16,
  },
  emptyTitle: {
    color: BrandPalette.text,
    fontSize: 20,
    fontWeight: '800',
    marginBottom: 8,
  },
  emptySubtitle: {
    color: BrandPalette.textMuted,
    fontSize: 15,
    lineHeight: 24,
    textAlign: 'center',
    maxWidth: 320,
  },
  emptyButton: {
    marginTop: 18,
    minHeight: 46,
    borderRadius: 14,
    paddingHorizontal: 18,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: BrandPalette.primary,
  },
  emptyButtonText: {
    color: BrandPalette.white,
    fontSize: 15,
    fontWeight: '800',
  },
  searchEmptyWrap: {
    paddingTop: 26,
    alignItems: 'center',
    justifyContent: 'center',
  },
  searchEmptyTitle: {
    color: BrandPalette.text,
    fontSize: 18,
    fontWeight: '800',
  },
  searchEmptySubtitle: {
    marginTop: 6,
    color: BrandPalette.textMuted,
    fontSize: 14,
    lineHeight: 22,
    textAlign: 'center',
  },
  centerState: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 32,
    gap: 8,
  },
  centerStateTitle: {
    color: BrandPalette.text,
    fontSize: 20,
    fontWeight: '800',
    textAlign: 'center',
  },
  centerStateSubtitle: {
    color: BrandPalette.textMuted,
    fontSize: 15,
    lineHeight: 23,
    textAlign: 'center',
  },
  modalBackdrop: {
    flex: 1,
    backgroundColor: 'rgba(20, 18, 16, 0.24)',
    justifyContent: 'flex-end',
  },
  modalKeyboardWrap: {
    flex: 1,
    justifyContent: 'flex-end',
  },
  modalSheet: {
    maxHeight: '88%',
    borderTopLeftRadius: 28,
    borderTopRightRadius: 28,
    paddingHorizontal: 18,
    paddingTop: 10,
    paddingBottom: 18,
    backgroundColor: BrandPalette.white,
    ...createShadow(0.12, 24, -8),
  },
  modalHandle: {
    alignSelf: 'center',
    width: 54,
    height: 5,
    borderRadius: 999,
    backgroundColor: '#DADADA',
    marginBottom: 14,
  },
  modalHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 16,
  },
  modalTitle: {
    color: BrandPalette.text,
    fontSize: 20,
    fontWeight: '900',
  },
  formSectionLabel: {
    color: BrandPalette.text,
    fontSize: 14,
    fontWeight: '700',
    marginBottom: 10,
  },
  labelChipRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 10,
    marginBottom: 14,
  },
  labelChip: {
    minHeight: 40,
    paddingHorizontal: 16,
    borderRadius: 999,
    borderWidth: 1,
    borderColor: BrandPalette.border,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: BrandPalette.surface,
  },
  labelChipActive: {
    borderColor: BrandPalette.primary,
    backgroundColor: BrandPalette.primarySoft,
  },
  labelChipText: {
    color: BrandPalette.textMuted,
    fontSize: 14,
    fontWeight: '700',
  },
  labelChipTextActive: {
    color: BrandPalette.primary,
  },
  locationButton: {
    minHeight: 46,
    borderRadius: 14,
    borderWidth: 1,
    borderColor: BrandPalette.primarySoft,
    marginBottom: 14,
    paddingHorizontal: 14,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    backgroundColor: '#FFF7F2',
  },
  locationButtonText: {
    color: BrandPalette.primary,
    fontSize: 14,
    fontWeight: '800',
  },
  input: {
    minHeight: 52,
    borderRadius: 16,
    borderWidth: 1,
    borderColor: BrandPalette.border,
    paddingHorizontal: 14,
    color: BrandPalette.text,
    fontSize: 15,
    backgroundColor: BrandPalette.white,
    marginBottom: 12,
  },
  defaultToggleRow: {
    marginTop: 4,
    marginBottom: 18,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 16,
    paddingVertical: 6,
  },
  defaultToggleCopy: {
    flex: 1,
  },
  defaultToggleTitle: {
    color: BrandPalette.text,
    fontSize: 15,
    fontWeight: '800',
    marginBottom: 4,
  },
  defaultToggleSubtitle: {
    color: BrandPalette.textMuted,
    fontSize: 13,
    lineHeight: 20,
  },
  saveButton: {
    minHeight: 52,
    borderRadius: 16,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: BrandPalette.primary,
    marginBottom: Platform.OS === 'ios' ? 14 : 8,
  },
  saveButtonDisabled: {
    opacity: 0.72,
  },
  saveButtonText: {
    color: BrandPalette.white,
    fontSize: 15,
    fontWeight: '900',
    letterSpacing: 0.4,
  },
});