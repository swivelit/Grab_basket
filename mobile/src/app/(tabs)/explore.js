import React, { useEffect, useMemo, useState } from 'react';
import {
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
import { useRouter } from 'expo-router';

import { BrandPalette, createShadow } from '@/constants/theme';
import { useGrabBasket } from '../../../App';

const FILTERS_BY_SERVICE = {
  food: ['All', 'Favorites', 'Fast delivery', 'Top rated', 'Offers', 'Biryani', 'Desserts'],
  warehouse: ['All', 'Favorites', 'Fresh', 'Snacks', 'Daily needs', 'Quick delivery'],
  eatout: ['All', 'Favorites', 'Offers', 'Top rated', 'Cafes', 'Family'],
  scenes: ['All', 'Music', 'Comedy', 'Workshops', 'Weekend', 'Premium'],
};

const CATEGORY_COPY = {
  food: [
    { key: 'protein', title: 'Protein bowls', icon: 'nutrition-outline', subtitle: 'Healthy & clean' },
    { key: 'cake', title: 'Cakes', icon: 'ice-cream-outline', subtitle: 'Celebration picks' },
    { key: 'burger', title: 'Burgers', icon: 'fast-food-outline', subtitle: 'Comfort cravings' },
    { key: 'combo', title: 'Combos', icon: 'restaurant-outline', subtitle: 'Best value meals' },
  ],
  warehouse: [
    { key: 'milk', title: 'Milk & dairy', icon: 'water-outline', subtitle: 'Daily essentials' },
    { key: 'snacks', title: 'Snacks & drinks', icon: 'cafe-outline', subtitle: 'Fast add-ons' },
    { key: 'fruits', title: 'Fruits & greens', icon: 'leaf-outline', subtitle: 'Fresh produce' },
    { key: 'beauty', title: 'Beauty', icon: 'flower-outline', subtitle: 'Everyday care' },
  ],
  eatout: [
    { key: 'rooftop', title: 'Rooftop', icon: 'moon-outline', subtitle: 'Evening vibes' },
    { key: 'family', title: 'Family tables', icon: 'people-outline', subtitle: 'Larger groups' },
    { key: 'cafe', title: 'Cafes', icon: 'cafe-outline', subtitle: 'Coffee & chats' },
    { key: 'chef', title: 'Chef picks', icon: 'wine-outline', subtitle: 'Premium dining' },
  ],
  scenes: [
    { key: 'music', title: 'Music', icon: 'musical-notes-outline', subtitle: 'Live and acoustic' },
    { key: 'comedy', title: 'Comedy', icon: 'mic-outline', subtitle: 'Stand-up nights' },
    { key: 'workshop', title: 'Workshops', icon: 'color-palette-outline', subtitle: 'Creative plans' },
    { key: 'premium', title: 'Premium', icon: 'diamond-outline', subtitle: 'High-ticket experiences' },
  ],
};

function money(value) {
  return `₹${Number(value || 0).toFixed(0)}`;
}

function normalizeText(value = '') {
  return String(value || '').trim().toLowerCase();
}

function getVendorRating(vendor) {
  const rating = Number(vendor?.avg_rating || 0);
  if (Number.isFinite(rating) && rating > 0) return rating.toFixed(1);
  const seed = Number(vendor?.id || 0) || String(vendor?.name || '').length || 3;
  return (4.1 + (seed % 7) * 0.1).toFixed(1);
}

function getVendorEta(vendor, service) {
  const eta = Number(vendor?.estimated_delivery_time_min || 0);

  if (service === 'warehouse') return eta > 0 ? `${eta} mins` : '7 mins';
  if (service === 'eatout') return eta > 0 ? `Table in ${Math.max(10, eta)} mins` : 'Book now';
  if (service === 'scenes') return 'Instant confirmation';
  if (eta > 0) return eta <= 15 ? `${eta} mins` : `${Math.max(10, eta - 5)}-${eta} mins`;
  return '23 mins';
}

function getVendorMeta(vendor, service) {
  if (service === 'warehouse') return vendor?.description || 'Fresh and quick everyday essentials';
  if (service === 'eatout') return vendor?.description || 'Tables, offers and premium dining';
  if (service === 'scenes') return vendor?.description || vendor?.address || 'Curated experiences nearby';
  return vendor?.cuisine_tags || vendor?.description || vendor?.address || 'Fast delivery and trusted quality';
}

function getBadge(service) {
  if (service === 'warehouse') return 'Quick baskets';
  if (service === 'eatout') return 'Dining perks';
  if (service === 'scenes') return 'Weekend plans';
  return 'Food discovery';
}

function getScreenTitle(service) {
  if (service === 'warehouse') return 'Market categories';
  if (service === 'eatout') return 'Dineout discover';
  if (service === 'scenes') return 'Scenes discover';
  return 'Food explore';
}

function categoryMatches(service, filter, vendor) {
  const haystack = normalizeText(
    [vendor?.name, vendor?.description, vendor?.cuisine_tags, vendor?.address].join(' ')
  );

  if (filter === 'All') return true;
  if (filter === 'Favorites') return true;
  if (filter === 'Fast delivery') return Number(vendor?.distance_km || 99) <= 5;
  if (filter === 'Top rated') return Number(getVendorRating(vendor)) >= 4.5;
  if (filter === 'Offers') return true;
  if (filter === 'Biryani') return /biryani/.test(haystack);
  if (filter === 'Desserts') return /dessert|cake|ice|sweet/.test(haystack);
  if (filter === 'Fresh') return /fresh|fruit|veg|vegetable|dairy/.test(haystack);
  if (filter === 'Snacks') return /snack|chips|cracker|cola|juice/.test(haystack);
  if (filter === 'Daily needs') return /daily|milk|bread|egg|essentials/.test(haystack);
  if (filter === 'Quick delivery') return true;
  if (filter === 'Cafes') return /cafe|coffee|bakery/.test(haystack);
  if (filter === 'Family') return /family|group|table/.test(haystack);
  if (filter === 'Music') return /music|live|band/.test(haystack);
  if (filter === 'Comedy') return /comedy|stand-up/.test(haystack);
  if (filter === 'Workshops') return /workshop|class|learn/.test(haystack);
  if (filter === 'Weekend') return true;
  if (filter === 'Premium') return /premium|chef|fine|exclusive/.test(haystack);

  if (service === 'eatout' && filter === 'Offers') return true;
  return true;
}

function SectionHeader({ title, actionLabel }) {
  return (
    <View style={styles.sectionHeader}>
      <Text style={styles.sectionTitle}>{title}</Text>
      {actionLabel ? <Text style={styles.sectionAction}>{actionLabel}</Text> : null}
    </View>
  );
}

function FilterPill({ label, active, onPress }) {
  return (
    <TouchableOpacity activeOpacity={0.94} onPress={onPress} style={[styles.filterPill, active && styles.filterPillActive]}>
      <Text style={[styles.filterPillText, active && styles.filterPillTextActive]}>{label}</Text>
    </TouchableOpacity>
  );
}

function CategoryCard({ item, onPress }) {
  return (
    <TouchableOpacity activeOpacity={0.94} onPress={onPress} style={styles.categoryCard}>
      <View style={styles.categoryIconWrap}>
        <Ionicons name={item.icon} size={20} color={BrandPalette.primary} />
      </View>
      <Text style={styles.categoryTitle}>{item.title}</Text>
      <Text style={styles.categorySubtitle}>{item.subtitle}</Text>
    </TouchableOpacity>
  );
}

function StoreRow({ vendor, service, favorite, onFavorite, onPress }) {
  return (
    <TouchableOpacity activeOpacity={0.95} onPress={onPress} style={styles.storeRow}>
      <View style={styles.storeRowVisual}>
        <Text style={styles.storeRowVisualText}>{String(vendor?.name || 'G').slice(0, 1).toUpperCase()}</Text>
      </View>

      <View style={{ flex: 1 }}>
        <View style={styles.storeRowTitleWrap}>
          <Text numberOfLines={1} style={styles.storeRowTitle}>{vendor?.name || 'Local store'}</Text>
          <View style={styles.ratingPill}>
            <Ionicons name="star" size={12} color="#FFFFFF" />
            <Text style={styles.ratingPillText}>{getVendorRating(vendor)}</Text>
          </View>
        </View>
        <Text numberOfLines={2} style={styles.storeRowMeta}>{getVendorMeta(vendor, service)}</Text>
        <Text style={styles.storeRowFoot}>{getVendorEta(vendor, service)} · {vendor?.distance_km ? `${Number(vendor.distance_km).toFixed(1)} km` : getBadge(service)}</Text>
      </View>

      <TouchableOpacity activeOpacity={0.92} onPress={onFavorite} style={styles.heartButton}>
        <Ionicons name={favorite ? 'heart' : 'heart-outline'} size={18} color={favorite ? BrandPalette.primary : BrandPalette.inkSoft} />
      </TouchableOpacity>
    </TouchableOpacity>
  );
}

function SuggestionCard({ label, onPress }) {
  return (
    <TouchableOpacity activeOpacity={0.94} onPress={onPress} style={styles.suggestionCard}>
      <Ionicons name="search-outline" size={16} color={BrandPalette.primary} />
      <Text style={styles.suggestionCardText}>{label}</Text>
    </TouchableOpacity>
  );
}

function EmptyState({ title, subtitle }) {
  return (
    <View style={styles.emptyState}>
      <Ionicons name="search-outline" size={22} color={BrandPalette.subtle} />
      <Text style={styles.emptyTitle}>{title}</Text>
      <Text style={styles.emptySubtitle}>{subtitle}</Text>
    </View>
  );
}

export default function ExploreScreen() {
  const router = useRouter();
  const tabBarHeight = useBottomTabBarHeight();
  const {
    activeService,
    vendors,
    featuredVendors,
    homeDeals,
    recentSearches,
    suggestionPool,
    favorites,
    toggleFavorite,
    rememberStore,
    rememberSearch,
    homeSearch,
    setHomeSearch,
    addToCart,
  } = useGrabBasket();

  const [activeFilter, setActiveFilter] = useState((FILTERS_BY_SERVICE[activeService] || FILTERS_BY_SERVICE.food)[0]);

  const filters = FILTERS_BY_SERVICE[activeService] || FILTERS_BY_SERVICE.food;

  useEffect(() => {
    setActiveFilter((FILTERS_BY_SERVICE[activeService] || FILTERS_BY_SERVICE.food)[0]);
  }, [activeService]);
  const categories = CATEGORY_COPY[activeService] || CATEGORY_COPY.food;
  const suggestions = useMemo(() => {
    const items = [...recentSearches, ...suggestionPool].filter(Boolean);
    return Array.from(new Set(items)).slice(0, 6);
  }, [recentSearches, suggestionPool]);

  const visibleStores = useMemo(() => {
    const query = normalizeText(homeSearch);
    const source = Array.isArray(featuredVendors) && featuredVendors.length ? featuredVendors : vendors;

    return (Array.isArray(source) ? source : [])
      .filter((vendor) => {
        const matchesFilter = categoryMatches(activeService, activeFilter, vendor);
        if (!matchesFilter) return false;
        if (!query) return activeFilter !== 'Favorites' || Boolean(favorites?.[vendor?.id]);

        const haystack = normalizeText([vendor?.name, vendor?.description, vendor?.address, vendor?.cuisine_tags].join(' '));
        const queryMatch = haystack.includes(query);
        const favoriteMatch = activeFilter !== 'Favorites' || Boolean(favorites?.[vendor?.id]);
        return queryMatch && favoriteMatch;
      })
      .slice(0, 12);
  }, [activeFilter, activeService, favorites, featuredVendors, homeSearch, vendors]);

  return (
    <SafeAreaView style={styles.safeArea} edges={['top']}>
      <StatusBar barStyle="dark-content" />
      <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={{ paddingBottom: tabBarHeight + 28 }}>
        <View style={styles.topBlock}>
          <Text style={styles.eyebrow}>{getBadge(activeService)}</Text>
          <Text style={styles.title}>{getScreenTitle(activeService)}</Text>

          <View style={styles.searchBar}>
            <Ionicons name="search-outline" size={20} color={BrandPalette.subtle} />
            <TextInput
              value={homeSearch}
              onChangeText={setHomeSearch}
              placeholder="Search stores, dishes, categories"
              placeholderTextColor={BrandPalette.subtle}
              style={styles.searchInput}
              returnKeyType="search"
              onSubmitEditing={() => {
                if (String(homeSearch || '').trim()) {
                  rememberSearch(homeSearch);
                }
              }}
            />
            <Ionicons name="options-outline" size={18} color={BrandPalette.inkSoft} />
          </View>
        </View>

        <View style={styles.pageBody}>
          <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.filterRail}>
            {filters.map((label) => (
              <FilterPill
                key={label}
                label={label}
                active={label === activeFilter}
                onPress={() => setActiveFilter(label)}
              />
            ))}
          </ScrollView>

          <SectionHeader title="Browse categories" actionLabel="See all" />
          <View style={styles.categoryGrid}>
            {categories.map((item) => (
              <CategoryCard
                key={item.key}
                item={item}
                onPress={() => {
                  setHomeSearch(item.title);
                  rememberSearch(item.title);
                }}
              />
            ))}
          </View>

          {suggestions.length ? (
            <>
              <SectionHeader title="Recent searches" />
              <View style={styles.suggestionWrap}>
                {suggestions.map((item) => (
                  <SuggestionCard
                    key={item}
                    label={item}
                    onPress={() => {
                      setHomeSearch(item);
                      rememberSearch(item);
                    }}
                  />
                ))}
              </View>
            </>
          ) : null}

          {Array.isArray(homeDeals) && homeDeals.length ? (
            <>
              <SectionHeader title="Instant add-ons" actionLabel="See all" />
              <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.dealRail}>
                {homeDeals.slice(0, 8).map((item, index) => (
                  <View key={`${item?.id || 'deal'}-${index}`} style={styles.dealCard}>
                    <View style={styles.dealCardVisual}>
                      <Ionicons name="bag-handle-outline" size={18} color={BrandPalette.primary} />
                    </View>
                    <Text numberOfLines={1} style={styles.dealCardBrand}>{item?.vendorName || 'GrabBasket'}</Text>
                    <Text numberOfLines={2} style={styles.dealCardName}>{item?.name || 'Featured item'}</Text>
                    <View style={styles.dealCardFooter}>
                      <Text style={styles.dealCardPrice}>{money(item?.price || 99)}</Text>
                      <TouchableOpacity activeOpacity={0.94} onPress={() => addToCart(item, 1)} style={styles.addButton}>
                        <Text style={styles.addButtonText}>ADD</Text>
                      </TouchableOpacity>
                    </View>
                  </View>
                ))}
              </ScrollView>
            </>
          ) : null}

          <SectionHeader title="Stores and partners" actionLabel={`${visibleStores.length} results`} />
          {visibleStores.length ? (
            <View style={styles.storeList}>
              {visibleStores.map((vendor) => (
                <StoreRow
                  key={vendor.id}
                  vendor={vendor}
                  service={activeService}
                  favorite={Boolean(favorites?.[vendor?.id])}
                  onFavorite={() => toggleFavorite(vendor.id)}
                  onPress={() => {
                    rememberStore(vendor.id);
                    router.push({ pathname: '/store/[vendorId]', params: { vendorId: String(vendor.id) } });
                  }}
                />
              ))}
            </View>
          ) : (
            <EmptyState
              title="Nothing matched"
              subtitle="Try another search or seed more demo vendors from your backend to populate this storefront."
            />
          )}
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
  topBlock: {
    paddingHorizontal: 16,
    paddingTop: 8,
    paddingBottom: 18,
    backgroundColor: BrandPalette.white,
    borderBottomWidth: 1,
    borderBottomColor: BrandPalette.line,
  },
  eyebrow: {
    fontSize: 12,
    lineHeight: 14,
    fontWeight: '900',
    color: BrandPalette.primary,
    textTransform: 'uppercase',
  },
  title: {
    marginTop: 8,
    fontSize: 28,
    lineHeight: 32,
    fontWeight: '900',
    color: BrandPalette.text,
  },
  subtitle: {
    marginTop: 8,
    fontSize: 14,
    lineHeight: 19,
    color: BrandPalette.textMuted,
  },
  searchBar: {
    marginTop: 16,
    height: 54,
    borderRadius: 18,
    backgroundColor: BrandPalette.backgroundAlt,
    borderWidth: 1,
    borderColor: BrandPalette.line,
    paddingHorizontal: 14,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
  },
  searchInput: {
    flex: 1,
    fontSize: 16,
    lineHeight: 20,
    color: BrandPalette.text,
  },
  pageBody: {
    paddingHorizontal: 16,
    paddingTop: 18,
  },
  filterRail: {
    paddingRight: 10,
    gap: 10,
    marginBottom: 8,
  },
  filterPill: {
    paddingHorizontal: 14,
    paddingVertical: 10,
    borderRadius: 999,
    backgroundColor: BrandPalette.white,
    borderWidth: 1,
    borderColor: BrandPalette.line,
  },
  filterPillActive: {
    backgroundColor: BrandPalette.primary,
    borderColor: BrandPalette.primary,
  },
  filterPillText: {
    fontSize: 13,
    lineHeight: 16,
    fontWeight: '700',
    color: BrandPalette.text,
  },
  filterPillTextActive: {
    color: BrandPalette.white,
  },
  sectionHeader: {
    marginTop: 12,
    marginBottom: 14,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 12,
  },
  sectionTitle: {
    flex: 1,
    fontSize: 20,
    lineHeight: 24,
    fontWeight: '900',
    color: BrandPalette.text,
  },
  sectionAction: {
    fontSize: 13,
    lineHeight: 16,
    fontWeight: '800',
    color: BrandPalette.primary,
  },
  categoryGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 12,
  },
  categoryCard: {
    width: '47.5%',
    borderRadius: 22,
    backgroundColor: BrandPalette.white,
    padding: 16,
    borderWidth: 1,
    borderColor: BrandPalette.line,
    ...createShadow(0.06, 10, 3),
  },
  categoryIconWrap: {
    width: 42,
    height: 42,
    borderRadius: 21,
    backgroundColor: BrandPalette.primarySoft,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 12,
  },
  categoryTitle: {
    fontSize: 15,
    lineHeight: 19,
    fontWeight: '800',
    color: BrandPalette.text,
  },
  categorySubtitle: {
    marginTop: 6,
    fontSize: 12,
    lineHeight: 16,
    color: BrandPalette.textMuted,
  },
  suggestionWrap: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 10,
  },
  suggestionCard: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    paddingHorizontal: 12,
    paddingVertical: 10,
    borderRadius: 14,
    backgroundColor: BrandPalette.white,
    borderWidth: 1,
    borderColor: BrandPalette.line,
  },
  suggestionCardText: {
    fontSize: 13,
    lineHeight: 16,
    fontWeight: '700',
    color: BrandPalette.text,
  },
  dealRail: {
    paddingRight: 10,
    gap: 12,
  },
  dealCard: {
    width: 170,
    borderRadius: 22,
    backgroundColor: BrandPalette.white,
    borderWidth: 1,
    borderColor: BrandPalette.line,
    padding: 14,
  },
  dealCardVisual: {
    height: 84,
    borderRadius: 18,
    backgroundColor: BrandPalette.primarySoft,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 12,
  },
  dealCardBrand: {
    fontSize: 12,
    lineHeight: 14,
    fontWeight: '700',
    color: BrandPalette.textMuted,
  },
  dealCardName: {
    marginTop: 6,
    minHeight: 38,
    fontSize: 15,
    lineHeight: 19,
    fontWeight: '800',
    color: BrandPalette.text,
  },
  dealCardFooter: {
    marginTop: 12,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 12,
  },
  dealCardPrice: {
    fontSize: 16,
    lineHeight: 19,
    fontWeight: '900',
    color: BrandPalette.text,
  },
  addButton: {
    minWidth: 64,
    borderRadius: 12,
    paddingHorizontal: 14,
    paddingVertical: 10,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: BrandPalette.primary,
  },
  addButtonText: {
    fontSize: 12,
    lineHeight: 14,
    fontWeight: '900',
    color: BrandPalette.white,
  },
  storeList: {
    gap: 12,
  },
  storeRow: {
    flexDirection: 'row',
    gap: 14,
    alignItems: 'center',
    padding: 14,
    borderRadius: 22,
    backgroundColor: BrandPalette.white,
    borderWidth: 1,
    borderColor: BrandPalette.line,
    ...createShadow(0.06, 10, 3),
  },
  storeRowVisual: {
    width: 64,
    height: 64,
    borderRadius: 18,
    backgroundColor: BrandPalette.primarySoft,
    alignItems: 'center',
    justifyContent: 'center',
  },
  storeRowVisualText: {
    fontSize: 24,
    lineHeight: 28,
    fontWeight: '900',
    color: BrandPalette.primary,
  },
  storeRowTitleWrap: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
  },
  storeRowTitle: {
    flex: 1,
    fontSize: 16,
    lineHeight: 20,
    fontWeight: '900',
    color: BrandPalette.text,
  },
  storeRowMeta: {
    marginTop: 6,
    fontSize: 13,
    lineHeight: 18,
    color: BrandPalette.textMuted,
  },
  storeRowFoot: {
    marginTop: 8,
    fontSize: 12,
    lineHeight: 15,
    fontWeight: '700',
    color: BrandPalette.inkSoft,
  },
  ratingPill: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    borderRadius: 999,
    backgroundColor: '#15803D',
    paddingHorizontal: 8,
    paddingVertical: 6,
  },
  ratingPillText: {
    fontSize: 11,
    lineHeight: 13,
    fontWeight: '900',
    color: BrandPalette.white,
  },
  heartButton: {
    width: 36,
    height: 36,
    borderRadius: 18,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: BrandPalette.backgroundAlt,
  },
  emptyState: {
    borderRadius: 22,
    backgroundColor: BrandPalette.white,
    borderWidth: 1,
    borderColor: BrandPalette.line,
    padding: 24,
    alignItems: 'center',
    gap: 8,
  },
  emptyTitle: {
    fontSize: 16,
    lineHeight: 20,
    fontWeight: '800',
    color: BrandPalette.text,
  },
  emptySubtitle: {
    fontSize: 13,
    lineHeight: 18,
    textAlign: 'center',
    color: BrandPalette.textMuted,
  },
});
