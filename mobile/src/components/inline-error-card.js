import React from 'react';
import { StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import { Ionicons } from '@expo/vector-icons';

const COLORS = {
  bg: '#FFF6F4',
  border: '#F4D3CB',
  text: '#6D2E25',
  muted: '#8C5B53',
  icon: '#D45454',
  actionBg: '#FFFFFF',
};

export default function InlineErrorCard({ title = 'Something went wrong', message = '', onRetry, onDismiss }) {
  if (!message) return null;

  return (
    <View style={styles.card}>
      <View style={styles.headerRow}>
        <View style={styles.titleRow}>
          <Ionicons name="alert-circle-outline" size={18} color={COLORS.icon} />
          <Text style={styles.title}>{title}</Text>
        </View>
        {typeof onDismiss === 'function' ? (
          <TouchableOpacity accessibilityRole="button" onPress={onDismiss} style={styles.iconButton}>
            <Ionicons name="close" size={18} color={COLORS.muted} />
          </TouchableOpacity>
        ) : null}
      </View>

      <Text style={styles.message}>{message}</Text>

      {typeof onRetry === 'function' ? (
        <TouchableOpacity accessibilityRole="button" onPress={onRetry} style={styles.retryButton}>
          <Text style={styles.retryText}>Retry</Text>
        </TouchableOpacity>
      ) : null}
    </View>
  );
}

const styles = StyleSheet.create({
  card: {
    backgroundColor: COLORS.bg,
    borderRadius: 18,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 14,
    gap: 10,
  },
  headerRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  titleRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    flex: 1,
  },
  title: {
    flex: 1,
    color: COLORS.text,
    fontSize: 14,
    fontWeight: '800',
  },
  message: {
    color: COLORS.muted,
    fontSize: 13,
    lineHeight: 19,
  },
  retryButton: {
    alignSelf: 'flex-start',
    backgroundColor: COLORS.actionBg,
    borderRadius: 999,
    paddingHorizontal: 12,
    paddingVertical: 8,
    borderWidth: 1,
    borderColor: COLORS.border,
  },
  retryText: {
    color: COLORS.text,
    fontSize: 12,
    fontWeight: '700',
  },
  iconButton: {
    padding: 4,
  },
});
