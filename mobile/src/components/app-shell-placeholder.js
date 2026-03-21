import React, { useMemo } from 'react';
import { ScrollView, StatusBar, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import { useBottomTabBarHeight } from '@react-navigation/bottom-tabs';
import { useGrabBasket } from '../../App';

const THEMES = {
  delivery: {
    page: '#F6F9FC',
    hero: '#1463FF',
    heroSoft: '#EAF2FF',
    heroCard: '#0F4ED8',
    text: '#122033',
    muted: '#66758A',
    border: '#DCE7F4',
    card: '#FFFFFF',
    statText: '#0F3A91',
    statSoft: '#EAF2FF',
    pillBg: '#DCE9FF',
    pillText: '#0F3A91',
  },
  partner: {
    page: '#FFF9F3',
    hero: '#D97651',
    heroSoft: '#FFF0E4',
    heroCard: '#B95D3B',
    text: '#2F241C',
    muted: '#776455',
    border: '#F0DDCA',
    card: '#FFFFFF',
    statText: '#8F4B31',
    statSoft: '#FFF2E8',
    pillBg: '#FFE7D7',
    pillText: '#8F4B31',
  },
};

function StatCard({ theme, value, label }) {
  return (
    <View style={[styles.statCard, { backgroundColor: theme.statSoft, borderColor: theme.border }]}>
      <Text style={[styles.statValue, { color: theme.statText }]}>{value}</Text>
      <Text style={[styles.statLabel, { color: theme.muted }]}>{label}</Text>
    </View>
  );
}

function ActionCard({ theme, icon, label, hint }) {
  return (
    <View style={[styles.actionCard, { backgroundColor: theme.card, borderColor: theme.border }]}>
      <View style={[styles.actionIconWrap, { backgroundColor: theme.heroSoft }]}>
        <Ionicons name={icon} size={20} color={theme.hero} />
      </View>
      <Text style={[styles.actionLabel, { color: theme.text }]}>{label}</Text>
      <Text style={[styles.actionHint, { color: theme.muted }]}>{hint}</Text>
    </View>
  );
}

function SectionCard({ theme, icon, title, body }) {
  return (
    <View style={[styles.sectionCard, { backgroundColor: theme.card, borderColor: theme.border }]}>
      <View style={[styles.sectionIcon, { backgroundColor: theme.heroSoft }]}>
        <Ionicons name={icon} size={18} color={theme.hero} />
      </View>
      <View style={{ flex: 1 }}>
        <Text style={[styles.sectionTitle, { color: theme.text }]}>{title}</Text>
        <Text style={[styles.sectionBody, { color: theme.muted }]}>{body}</Text>
      </View>
    </View>
  );
}

export default function AppShellPlaceholder({
  shell = 'delivery',
  eyebrow,
  title,
  description,
  stats = [],
  actions = [],
  sections = [],
}) {
  const theme = THEMES[shell] || THEMES.delivery;
  const tabBarHeight = useBottomTabBarHeight();
  const {
    sessionReady,
    isAuthenticated,
    authEmail,
    authRole,
    vendors,
    orderHistory,
    cartCount,
  } = useGrabBasket();

  const statusPills = useMemo(() => {
    return [
      sessionReady ? 'Session ready' : 'Booting context',
      isAuthenticated ? authRole || 'Signed in' : 'Signed out',
      `${Array.isArray(vendors) ? vendors.length : 0} vendors cached`,
      `${Array.isArray(orderHistory) ? orderHistory.length : 0} orders cached`,
      `${cartCount || 0} items in cart cache`,
    ];
  }, [authRole, cartCount, isAuthenticated, orderHistory, sessionReady, vendors]);

  return (
    <SafeAreaView style={[styles.safeArea, { backgroundColor: theme.page }]} edges={['top']}>
      <StatusBar barStyle="dark-content" />
      <ScrollView
        contentContainerStyle={[styles.content, { paddingBottom: tabBarHeight + 28 }]}
        showsVerticalScrollIndicator={false}>
        <View style={[styles.hero, { backgroundColor: theme.hero }]}>
          <Text style={styles.heroEyebrow}>{eyebrow}</Text>
          <Text style={styles.heroTitle}>{title}</Text>
          <Text style={styles.heroDescription}>{description}</Text>

          <View style={styles.pillRow}>
            {statusPills.map((pill) => (
              <View key={pill} style={[styles.pill, { backgroundColor: theme.pillBg }]}>
                <Text style={[styles.pillText, { color: theme.pillText }]}>{pill}</Text>
              </View>
            ))}
          </View>

          <View style={[styles.identityCard, { backgroundColor: theme.heroCard }]}>
            <Text style={styles.identityLabel}>Current session</Text>
            <Text style={styles.identityValue}>{authEmail || 'No active account in this shell yet'}</Text>
            <Text style={styles.identityHint}>
              This shell now has its own navigation entry point and can evolve separately from the consumer app.
            </Text>
          </View>
        </View>

        {!!stats.length && (
          <View style={styles.statGrid}>
            {stats.map((item) => (
              <StatCard
                key={`${item.label}-${item.value}`}
                theme={theme}
                value={item.value}
                label={item.label}
              />
            ))}
          </View>
        )}

        {!!actions.length && (
          <View style={styles.block}>
            <Text style={[styles.blockTitle, { color: theme.text }]}>Quick actions</Text>
            <View style={styles.actionGrid}>
              {actions.map((item) => (
                <ActionCard
                  key={item.label}
                  theme={theme}
                  icon={item.icon}
                  label={item.label}
                  hint={item.hint}
                />
              ))}
            </View>
          </View>
        )}

        {!!sections.length && (
          <View style={styles.block}>
            <Text style={[styles.blockTitle, { color: theme.text }]}>What belongs in this shell</Text>
            <View style={styles.sectionList}>
              {sections.map((item) => (
                <SectionCard
                  key={item.title}
                  theme={theme}
                  icon={item.icon}
                  title={item.title}
                  body={item.body}
                />
              ))}
            </View>
          </View>
        )}
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: {
    flex: 1,
  },
  content: {
    paddingHorizontal: 18,
    paddingTop: 14,
    gap: 18,
  },
  hero: {
    borderRadius: 28,
    padding: 20,
    gap: 12,
  },
  heroEyebrow: {
    color: 'rgba(255,255,255,0.82)',
    fontSize: 12,
    fontWeight: '700',
    textTransform: 'uppercase',
    letterSpacing: 0.6,
  },
  heroTitle: {
    color: '#FFFFFF',
    fontSize: 28,
    fontWeight: '800',
    lineHeight: 34,
  },
  heroDescription: {
    color: 'rgba(255,255,255,0.9)',
    fontSize: 14,
    lineHeight: 22,
  },
  pillRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
  },
  pill: {
    borderRadius: 999,
    paddingHorizontal: 12,
    paddingVertical: 7,
  },
  pillText: {
    fontSize: 12,
    fontWeight: '700',
  },
  identityCard: {
    borderRadius: 20,
    padding: 16,
    gap: 6,
  },
  identityLabel: {
    color: 'rgba(255,255,255,0.72)',
    fontSize: 11,
    fontWeight: '700',
    textTransform: 'uppercase',
    letterSpacing: 0.5,
  },
  identityValue: {
    color: '#FFFFFF',
    fontSize: 15,
    fontWeight: '700',
  },
  identityHint: {
    color: 'rgba(255,255,255,0.84)',
    fontSize: 13,
    lineHeight: 20,
  },
  statGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 12,
  },
  statCard: {
    flexGrow: 1,
    minWidth: '47%',
    borderRadius: 20,
    borderWidth: 1,
    padding: 16,
    gap: 6,
  },
  statValue: {
    fontSize: 22,
    fontWeight: '800',
  },
  statLabel: {
    fontSize: 13,
    fontWeight: '600',
  },
  block: {
    gap: 12,
  },
  blockTitle: {
    fontSize: 18,
    fontWeight: '800',
  },
  actionGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 12,
  },
  actionCard: {
    flexGrow: 1,
    minWidth: '47%',
    borderRadius: 20,
    borderWidth: 1,
    padding: 16,
    gap: 10,
  },
  actionIconWrap: {
    width: 40,
    height: 40,
    borderRadius: 14,
    alignItems: 'center',
    justifyContent: 'center',
  },
  actionLabel: {
    fontSize: 15,
    fontWeight: '700',
  },
  actionHint: {
    fontSize: 13,
    lineHeight: 19,
  },
  sectionList: {
    gap: 12,
  },
  sectionCard: {
    borderRadius: 20,
    borderWidth: 1,
    padding: 16,
    gap: 12,
    flexDirection: 'row',
    alignItems: 'flex-start',
  },
  sectionIcon: {
    width: 38,
    height: 38,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
  },
  sectionTitle: {
    fontSize: 15,
    fontWeight: '700',
    marginBottom: 4,
  },
  sectionBody: {
    fontSize: 13,
    lineHeight: 20,
  },
});