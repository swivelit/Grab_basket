import React from 'react';
import { ActivityIndicator, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import { Ionicons } from '@expo/vector-icons';

export const sharedStyles = StyleSheet.create({
  screen: { flex: 1, backgroundColor: '#f4f5f7' },
  pageContent: { paddingHorizontal: 16, paddingTop: 16, paddingBottom: 120 },
  pageContentWithFloat: { paddingHorizontal: 16, paddingTop: 16, paddingBottom: 140 },
  bodySurface: {
    backgroundColor: '#f4f5f7',
    borderTopLeftRadius: 30,
    borderTopRightRadius: 30,
    marginTop: -18,
    paddingTop: 22,
    paddingHorizontal: 16,
    paddingBottom: 120,
  },
  bodySurfaceDark: {
    backgroundColor: '#020617',
    borderTopLeftRadius: 30,
    borderTopRightRadius: 30,
    marginTop: -18,
    paddingTop: 22,
    paddingHorizontal: 16,
    paddingBottom: 120,
  },
  sectionHeader: { marginTop: 6, marginBottom: 14 },
  sectionTitle: { fontSize: 20, fontWeight: '800', color: '#0f172a' },
  sectionTitleDark: { color: '#ffffff' },
  sectionSubtitle: { marginTop: 4, fontSize: 13, color: '#64748b' },
  sectionSubtitleDark: { color: '#cbd5e1' },
  feedbackCard: {
    borderRadius: 20,
    borderWidth: 1,
    borderColor: '#e2e8f0',
    backgroundColor: '#ffffff',
    padding: 18,
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
  },
  feedbackCardDark: {
    borderColor: '#1e293b',
    backgroundColor: '#0f172a',
  },
  feedbackTitle: { fontSize: 16, fontWeight: '700', color: '#0f172a' },
  feedbackTitleDark: { color: '#ffffff' },
  feedbackSubtitle: { fontSize: 13, color: '#64748b', textAlign: 'center' },
  feedbackSubtitleDark: { color: '#cbd5e1' },
  ghostButton: {
    marginTop: 12,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    paddingHorizontal: 14,
    paddingVertical: 10,
    borderRadius: 999,
    backgroundColor: '#eef2ff',
  },
  ghostButtonText: { fontSize: 13, fontWeight: '700', color: '#3730a3' },
});

export function SectionHeader({ title, subtitle, light = false, actionLabel, onActionPress }) {
  return (
    <View style={sharedStyles.sectionHeader}>
      <View style={{ flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: 12 }}>
        <View style={{ flex: 1 }}>
          <Text style={[sharedStyles.sectionTitle, light && sharedStyles.sectionTitleDark]}>{title}</Text>
          {subtitle ? (
            <Text style={[sharedStyles.sectionSubtitle, light && sharedStyles.sectionSubtitleDark]}>{subtitle}</Text>
          ) : null}
        </View>
        {actionLabel ? (
          <TouchableOpacity activeOpacity={0.9} onPress={onActionPress}>
            <Text style={{ fontSize: 13, fontWeight: '700', color: light ? '#ffffff' : '#4338ca' }}>{actionLabel}</Text>
          </TouchableOpacity>
        ) : null}
      </View>
    </View>
  );
}

export function LoadingState({ label = 'Loading...', dark = false }) {
  return (
    <View style={[sharedStyles.feedbackCard, dark && sharedStyles.feedbackCardDark]}>
      <ActivityIndicator color={dark ? '#ffffff' : '#4f46e5'} />
      <Text style={[sharedStyles.feedbackTitle, dark && sharedStyles.feedbackTitleDark]}>{label}</Text>
    </View>
  );
}

export function EmptyState({ icon = 'search-outline', title, subtitle, onActionPress, actionLabel = 'Try again', dark = false }) {
  return (
    <View style={[sharedStyles.feedbackCard, dark && sharedStyles.feedbackCardDark]}>
      <Ionicons name={icon} size={24} color={dark ? '#cbd5e1' : '#475569'} />
      <Text style={[sharedStyles.feedbackTitle, dark && sharedStyles.feedbackTitleDark]}>{title}</Text>
      {subtitle ? (
        <Text style={[sharedStyles.feedbackSubtitle, dark && sharedStyles.feedbackSubtitleDark]}>{subtitle}</Text>
      ) : null}
      {onActionPress ? (
        <TouchableOpacity activeOpacity={0.9} onPress={onActionPress} style={sharedStyles.ghostButton}>
          <Text style={sharedStyles.ghostButtonText}>{actionLabel}</Text>
        </TouchableOpacity>
      ) : null}
    </View>
  );
}