import React from 'react';
import { StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import { Ionicons } from '@expo/vector-icons';

const TONES = {
  info: {
    bg: '#EBF2FF',
    border: '#CFE0FF',
    icon: '#2C69C9',
    title: '#214A8B',
    text: '#365E99',
  },
  success: {
    bg: '#EAF8F0',
    border: '#CFECD8',
    icon: '#1F8F5F',
    title: '#1A6E4B',
    text: '#376A55',
  },
  warning: {
    bg: '#FFF6DE',
    border: '#F5E2A8',
    icon: '#C57B12',
    title: '#8E5B12',
    text: '#8F6F2B',
  },
  danger: {
    bg: '#FFF6F4',
    border: '#F4D3CB',
    icon: '#D45454',
    title: '#6D2E25',
    text: '#8C5B53',
  },
};

export default function InlineNoticeCard({
  title = 'Updated',
  message = '',
  tone = 'success',
  onDismiss,
  actionLabel = '',
  onAction,
}) {
  if (!message) return null;

  const palette = TONES[tone] || TONES.success;

  return (
    <View style={[styles.card, { backgroundColor: palette.bg, borderColor: palette.border }]}>
      <View style={styles.headerRow}>
        <View style={styles.titleRow}>
          <Ionicons
            name={tone === 'danger' ? 'alert-circle-outline' : 'checkmark-circle-outline'}
            size={18}
            color={palette.icon}
          />
          <Text style={[styles.title, { color: palette.title }]}>{title}</Text>
        </View>
        {typeof onDismiss === 'function' ? (
          <TouchableOpacity accessibilityRole="button" onPress={onDismiss} style={styles.iconButton}>
            <Ionicons name="close" size={18} color={palette.text} />
          </TouchableOpacity>
        ) : null}
      </View>

      <Text style={[styles.message, { color: palette.text }]}>{message}</Text>

      {typeof onAction === 'function' && actionLabel ? (
        <TouchableOpacity
          accessibilityRole="button"
          onPress={onAction}
          style={[styles.actionButton, { borderColor: palette.border }]}>
          <Text style={[styles.actionText, { color: palette.title }]}>{actionLabel}</Text>
        </TouchableOpacity>
      ) : null}
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
  headerRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 8,
  },
  titleRow: {
    flex: 1,
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
  actionButton: {
    alignSelf: 'flex-start',
    borderWidth: 1,
    borderRadius: 999,
    backgroundColor: '#FFFFFF',
    paddingHorizontal: 12,
    paddingVertical: 8,
  },
  actionText: {
    fontSize: 12,
    fontWeight: '700',
  },
  iconButton: {
    padding: 4,
  },
});
