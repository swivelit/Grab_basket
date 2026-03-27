import React from 'react';
import { StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import { Ionicons } from '@expo/vector-icons';

const TONES = {
  danger: {
    bg: '#FFF6F4',
    border: '#F4D3CB',
    title: '#6D2E25',
    text: '#8C5B53',
    primaryBg: '#D45454',
    primaryText: '#FFFFFF',
  },
  warning: {
    bg: '#FFF6DE',
    border: '#F5E2A8',
    title: '#8E5B12',
    text: '#8F6F2B',
    primaryBg: '#C57B12',
    primaryText: '#FFFFFF',
  },
};

export default function InlineConfirmCard({
  title = 'Please confirm',
  message = '',
  confirmLabel = 'Confirm',
  cancelLabel = 'Cancel',
  tone = 'danger',
  onConfirm,
  onCancel,
}) {
  if (!message) return null;

  const palette = TONES[tone] || TONES.danger;

  return (
    <View style={[styles.card, { backgroundColor: palette.bg, borderColor: palette.border }]}>
      <View style={styles.titleRow}>
        <Ionicons name="help-circle-outline" size={18} color={palette.title} />
        <Text style={[styles.title, { color: palette.title }]}>{title}</Text>
      </View>
      <Text style={[styles.message, { color: palette.text }]}>{message}</Text>
      <View style={styles.buttonRow}>
        <TouchableOpacity
          accessibilityRole="button"
          onPress={onCancel}
          style={[styles.secondaryButton, { borderColor: palette.border }]}>
          <Text style={[styles.secondaryText, { color: palette.title }]}>{cancelLabel}</Text>
        </TouchableOpacity>
        <TouchableOpacity
          accessibilityRole="button"
          onPress={onConfirm}
          style={[styles.primaryButton, { backgroundColor: palette.primaryBg }]}> 
          <Text style={[styles.primaryText, { color: palette.primaryText }]}>{confirmLabel}</Text>
        </TouchableOpacity>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  card: {
    borderRadius: 18,
    borderWidth: 1,
    padding: 14,
    gap: 10,
  },
  titleRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  title: {
    flex: 1,
    fontSize: 14,
    fontWeight: '800',
  },
  message: {
    fontSize: 13,
    lineHeight: 19,
  },
  buttonRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 10,
  },
  secondaryButton: {
    minHeight: 40,
    borderRadius: 999,
    borderWidth: 1,
    backgroundColor: '#FFFFFF',
    paddingHorizontal: 14,
    alignItems: 'center',
    justifyContent: 'center',
  },
  secondaryText: {
    fontSize: 12,
    fontWeight: '700',
  },
  primaryButton: {
    minHeight: 40,
    borderRadius: 999,
    paddingHorizontal: 14,
    alignItems: 'center',
    justifyContent: 'center',
  },
  primaryText: {
    fontSize: 12,
    fontWeight: '800',
  },
});
