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
  sectionTitle