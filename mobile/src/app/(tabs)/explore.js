import React, { useMemo, useState } from 'react';
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
import { useGrabBasket } from '../../../App';

const COLORS = {
  bg: '#f6f7fb',
  card: '#ffffff',
  text: '#101828',
  muted: '#667085',
  subtle: '#98a2b3',
  border: '#e8ecf3',
  orange: '#ff6d00',
  orangeSoft: '#fff1e7',
  green: '#119b56',
  greenSoft: '#e8f8ee',
  blue: '#0b57d0',
  blueSoft: '#edf4ff',
  purple: '#6d28d9',
  purpleSoft: '#f4edff',
  yellow: '#ffcf33',
  yellowSoft: '#fff8db',
  pink: '#ff4ca2',
  black: '#050816',
  darkSurface: '#0c1324',
  darkSurfaceAlt: '#131d35',
  darkBorder: '#202c48',
  darkMuted: '#b8c4dc',
};

const SCREEN_THEME = {
  food: {
    page: COLORS.bg,
    hero: '#fff4d7',
    heroAccent: '#ffd95e',
    heroText: COLORS.text,
    heroSub: '#6b5c2a',
    searchPlaceholder: 'Search cuisines, dishes, collections',
    title: 'Gourmet',
    subtitle: 'Curated discovery designed for premium food browsing.',
  },
  warehouse: {
    page: '#f7fbff',
    hero: '#eaf2ff',
    heroAccent: '#b7d2ff',
    heroText: COLORS.text,
    heroSub: '#45628f',
    searchPlaceholder: 'Search categories, brands, essentials',
    title: 'Categories',
    subtitle: 'Fast aisle discovery with better grocery navigation.',
  },
  eatout: {
    page: '#fbfbfd',
    hero: '#fff2e4',
    heroAccent: '#ffd1a8',
    heroText: COLORS.text,
    heroSub: '#805630',
    searchPlaceholder: 'Search restaurants, vibe, area',
    title: 'My corner',
    subtitle: 'Dining moods, shortcuts and places worth booking.',
  },
  scenes: {
    page: COLORS.black,
    hero: '#121a2e',
    heroAccent: '#223150',
    heroText: '#ffffff',
    heroSub: '#c7d2e8',
    searchPlaceholder: 'Search events, creators, experiences',
    title: 'Explore',
    subtitle: 'Experiences discovery with a premium dark surface.',
  },
};

const FOOD_COLLECTIONS = [
  {
    key: 'chef',
    title: 'Chef-curated picks',
    subtitle: 'High-rated meals and premium brands',
    icon: 'sparkles-outline',
  },
  {
    key: 'late',
    title: 'Late-night cravings',
    subtitle: 'Reliable comfort food after hours',
    icon: 'moon-outline',
  },
  {
    key: 'healthy',
    title: 'Healthy choices',
    subtitle: 'Bowls, salads and lighter meals',
    icon: 'leaf-outline',
  },
  {
    key: 'dessert',
    title: 'Dessert mission',
    subtitle: 'Cakes, jars and sweet fixes',
    icon: 'ice-cream-outline',
  },
];

const FOOD_CATEGORIES = [
  { key: 'south', icon: 'restaurant-outline', label: 'South Indian' },
  { key: 'biryani', icon: 'flame-outline', label: 'Biryani' },
  { key: 'cakes', icon: 'gift-outline', label: 'Cakes' },
  { key: 'burgers', icon: 'fast-food-outline', label: 'Burgers' },
  { key: 'healthy', icon: 'leaf-outline', label: 'Healthy' },
  { key: 'juice', icon: 'cafe-outline', label: 'Juices' },
  { key: 'breakfast', icon: 'sunny-outline', label: 'Breakfast' },
  { key: 'late', icon: 'moon-outline', label: 'Late night' },
];

const WAREHOUSE_CATEGORY_GRID = [
  { key: 'fresh', icon: 'leaf-outline', label: 'Fresh' },
  { key: 'fruit', icon: 'nutrition-outline', label: 'Fruits' },
  { key: 'dairy', icon: 'water-outline', label: 'Dairy' },
  { key: 'bakery', icon: 'pizza-outline', label: 'Bakery' },
  { key: 'snacks', icon: 'pricetag-outline', label: 'Snacks' },
  { key: 'beverages', icon: 'beer-outline', label: 'Drinks' },
  { key: 'beauty', icon: 'flower-outline', label: 'Beauty' },
  { key: 'home', icon: 'home-outline', label: 'Home care' },
];

const WAREHOUSE_AISLES = [
  {
    key: 'iftar',
    title: 'Ramzan specials',
    subtitle: 'Dates, drinks, essentials and festive snacks',
    icon: 'moon-outline',
  },
  {
    key: 'daily',
    title: 'Daily essentials',
    subtitle: 'Milk, curd, bread, eggs and breakfast basics',
    icon: 'basket-outline',
  },
  {
    key: 'value',
    title: 'Value picks',
    subtitle: 'Budget-friendly quick additions',
    icon: 'cash-outline',
  },
];

const EATOUT_COLLECTIONS = [
  {
    key: 'family',
    title: 'Family-friendly spots',
    subtitle: 'Comfortable dine-in places for groups',
    icon: 'people-outline',
  },
  {
    key: 'cafes',
    title: 'Cafe dates',
    subtitle: 'Coffee, desserts and low-pressure plans',
    icon: 'cafe-outline',
  },
  {
    key: 'premium',
    title: 'Premium dining',
    subtitle: 'Upgraded ambience and bill offers',
    icon: 'wine-outline',
  },
  {
    key: 'rooftop',
    title: 'Rooftop evenings',
    subtitle: 'Open-air tables and night views',
    icon: 'moon-outline',
  },
];

const EATOUT_SHORTCUTS = [
  { key: 'near', label: 'Restaurants near me', icon: 'navigate-outline' },
  { key: 'prebook', label: 'Pre-book offers', icon: 'bookmark-outline' },
  { key: 'cashback', label: 'Cashback picks', icon: 'cash-outline' },
  { key: 'new', label: 'New & Hot', icon: 'flame-outline' },
];

const SCENE_COLLECTIONS = [
  {
    key: 'music',
    title: 'Live music',
    subtitle: 'Bands, unplugged sets and local performances',
    icon: 'musical-notes-outline',
    accent: '#1f3156',
  },
  {
    key: 'comedy',
    title: 'Comedy nights',
    subtitle: 'Stand-up, crowd work and weekend laughs',
    icon: 'mic-outline',
    accent: '#44246f',
  },
  {
    key: 'kids',
    title: 'Kids activities',
    subtitle: 'Creative workshops and family plans',
    icon: 'happy-outline',
    accent: '#2c5b47',
  },
  {
    key: 'workshops',
    title: 'Workshops',
    subtitle: 'Pottery, art, maker sessions and more',
    icon: 'color-palette-outline',
    accent: '#6a402c',
  },
];

const SCENE_FILTERS = [
  'Tonight',
  'This week',
  'Weekend',
  'Music',
  'Comedy',
  'Workshops',
];

const FALLBACK_SCENES = [
  {
    id: 'scene-1',
    title: 'Rage Room Experience',
    subtitle: 'Break n Chill · Chittethukara',
    price: 299,
    icon: 'hammer-outline',
    accent: '#311015',
  },
  {
    id: 'scene-2',
    title: 'Pottery Wheel Workshop',
    subtitle: 'Soil to Soul Ceramics · Kadavanthra',
    price: 1000,
    icon: 'color-palette-outline',
    accent: '#6b4a35',
  },
  {
    id: 'scene-3',
    title: 'Stand-up Comedy Night',
    subtitle: 'Kakkanad · Top comics this weekend',
    price: 499,
    icon: 'mic-outline',
    accent: '#243963',
  },
];

function money(value) {
  return `₹${Number(value || 0).toFixed(0)}`;
}

function initials(name = '') {
  return String(name || '')
    .split(' ')
    .filter(Boolean)
    .map((part) => part[0])
    .join('')
    .slice(0, 2)
    .toUpperCase();
}

function getVendorRating(vendor) {
  const seed = Number(vendor?.id || 0) || String(vendor?.name || '').length || 1;
  return (4.1 + (seed % 8) * 0.1).toFixed(1);
}

function estimateEta(vendor, activeService) {
  if (activeService === 'warehouse') return '5-15 mins';
  if (activeService === 'eatout') return 'Table in 10-15 mins';
  if (vendor?.distance_km != null) {
    if (vendor.distance_km <= 2) return '15-20 mins';
    if (vendor.distance_km <= 5) return '20-30 mins';
  }
  return '23 mins';
}

function getVendorMeta(vendor, activeService) {
  if (activeService === 'warehouse') return 'Essentials · Snacks · Daily needs';
  if (activeService === 'eatout') return 'Table offers · Bill savings · Dining';
  return vendor?.description || 'Popular near you';
}

function getRailOffer(vendor, activeService) {
  const food = ['40% OFF', 'UPTO ₹80', 'FREE DELIVERY', 'BESTSELLER'];
  const warehouse = ['₹9 DEAL', 'VALUE PICK', 'TOP BRANDS', 'DAILY SAVER'];
  const eatout = ['Flat 50% OFF', '10% Cashback', 'Pre-book', 'Bill offer'];
  const source =
    activeService === 'warehouse'
      ? warehouse
      : activeService === 'eatout'
        ? eatout
        : food;
  const seed = Number(vendor?.id || 0) || String(vendor?.name || '').length || 0;
  return source[seed % source.length];
}

function getCardTint(name = '', activeService = 'food') {
  const seed = String(name || '').length % 5;
  if (activeService === 'warehouse') {
    return ['#1d4c9a', '#2357ae', '#103c81', '#2d67bb', '#204f9a'][seed];
  }
  if (activeService === 'eatout') {
    return ['#7a3813', '#5f2151', '#8f5a11', '#80411e', '#743217'][seed];
  }
  if (activeService === 'scenes') {
    return ['#27385d', '#54296f', '#265744', '#6c4530', '#283451'][seed];
  }
  return ['#5c229f', '#0f5e49', '#7a1e29', '#174285', '#70471d'][seed];
}

function pickDealEmoji(name = '') {
  const value = String(name || '').toLowerCase();
  if (/(milk|curd|dairy|paneer)/.test(value)) return '🥛';
  if (/(jam|fruit|berry)/.test(value)) return '🍓';
  if (/(chip|snack)/.test(value)) return '🥔';
  if (/(chocolate|candy)/.test(value)) return '🍫';
  return '🛍️';
}

function SectionHeader({ title, subtitle, actionLabel, onPressAction, light = false }) {
  return (
    <View style={styles.sectionHeader}>
      <View style={{ flex: 1 }}>
        <Text style={[styles.sectionTitle, light && styles.sectionTitleLight]}>{title}</Text>
        {subtitle ? (
          <Text style={[styles.sectionSubtitle, light && styles.sectionSubtitleLight]}>
            {subtitle}
          </Text>
        ) : null}
      </View>
      {actionLabel ? (
        <TouchableOpacity activeOpacity={0.92} onPress={onPressAction}>
          <Text style={[styles.sectionAction, light && styles.sectionActionLight]}>
            {actionLabel}
          </Text>
        </TouchableOpacity>
      ) : null}
    </View>
  );
}

function SearchBar({ value, onChangeText, placeholder, dark = false }) {
  return (
    <View style={[styles.searchBar, dark && styles.searchBarDark]}>
      <Ionicons
        name="search-outline"
        size={20}
        color={dark ? '#b8c4dc' : COLORS.muted}
      />
      <TextInput
        value={value}
        onChangeText={onChangeText}
        placeholder={placeholder}
        placeholderTextColor={dark ? '#8fa2c4' : COLORS.subtle}
        style={[styles.searchInput, dark && styles.searchInputDark]}
      />
      <Ionicons
        name="options-outline"
        size={20}
        color={dark ? '#dbe4ff' : COLORS.orange}
      />
    </View>
  );
}

function HeroCard({ theme, title, subtitle, primaryAction, dark = false }) {
  return (
    <View style={[styles.heroCard, { backgroundColor: theme.hero }]}>
      <View style={[styles.heroOrbOne, { backgroundColor: theme.heroAccent }]} />
      <View style={[styles.heroOrbTwo, { backgroundColor: theme.heroAccent }]} />
      <Text style={[styles.heroTitle, { color: theme.heroText }]}>{title}</Text>
      <Text style={[styles.heroSubtitle, { color: theme.heroSub }]}>{subtitle}</Text>

      <View style={styles.heroActionRow}>
        <TouchableOpacity
          activeOpacity={0.92}
          style={[
            styles.heroPrimaryButton,
            dark ? styles.heroPrimaryButtonDark : styles.heroPrimaryButtonLight,
          ]}>
          <Text
            style={[
              styles.heroPrimaryButtonText,
              dark && styles.heroPrimaryButtonTextDark,
            ]}>
            {primaryAction}
          </Text>
        </TouchableOpacity>

        <TouchableOpacity
          activeOpacity={0.92}
          style={[styles.heroIconButton, dark && styles.heroIconButtonDark]}>
          <Ionicons
            name="sparkles-outline"
            size={18}
            color={dark ? '#ffffff' : COLORS.text}
          />
        </TouchableOpacity>
      </View>
    </View>
  );
}

function CollectionCard({ item, activeService = 'food' }) {
  const dark = activeService === 'scenes';

  return (
    <TouchableOpacity
      activeOpacity={0.92}
      style={[
        styles.collectionCard,
        dark && {
          backgroundColor: item.accent || COLORS.darkSurface,
          borderColor: COLORS.darkBorder,
        },
      ]}>
      <View
        style={[
          styles.collectionIconWrap,
          dark && styles.collectionIconWrapDark,
        ]}>
        <Ionicons
          name={item.icon}
          size={20}
          color={dark ? '#ffffff' : COLORS.text}
        />
      </View>
      <Text style={[styles.collectionTitle, dark && styles.collectionTitleDark]}>
        {item.title}
      </Text>
      <Text style={[styles.collectionSubtitle, dark && styles.collectionSubtitleDark]}>
        {item.subtitle}
      </Text>
    </TouchableOpacity>
  );
}

function CategoryGridCard({ item, dark = false, active = false, onPress }) {
  return (
    <TouchableOpacity
      activeOpacity={0.92}
      style={[
        styles.categoryGridCard,
        dark && styles.categoryGridCardDark,
        active && !dark && styles.categoryGridCardActive,
      ]}
      onPress={onPress}>
      <View style={[styles.categoryIconWrap, dark && styles.categoryIconWrapDark]}>
        <Ionicons
          name={item.icon}
          size={20}
          color={dark ? '#ffffff' : active ? '#ffffff' : COLORS.text}
        />
      </View>
      <Text
        style={[
          styles.categoryGridLabel,
          dark && styles.categoryGridLabelDark,
          active && !dark && styles.categoryGridLabelActive,
        ]}>
        {item.label}
      </Text>
    </TouchableOpacity>
  );
}

function VendorRailCard({
  vendor,
  activeService,
  favorite,
  onToggleFavorite,
  onPress,
  dark = false,
}) {
  return (
    <TouchableOpacity
      activeOpacity={0.94}
      style={[styles.vendorRailCard, dark && styles.vendorRailCardDark]}
      onPress={onPress}>
      <View
        style={[
          styles.vendorRailVisual,
          { backgroundColor: getCardTint(vendor?.name, activeService) },
        ]}>
        <View style={styles.vendorRailTop}>
          <View style={styles.offerPill}>
            <Text style={styles.offerPillText}>{getRailOffer(vendor, activeService)}</Text>
          </View>
          <TouchableOpacity
            activeOpacity={0.92}
            style={styles.favoriteButton}
            onPress={onToggleFavorite}>
            <Ionicons
              name={favorite ? 'heart' : 'heart-outline'}
              size={16}
              color={favorite ? '#ff5b6e' : '#ffffff'}
            />
          </TouchableOpacity>
        </View>

        <View style={styles.vendorMonogram}>
          <Text style={styles.vendorMonogramText}>{initials(vendor?.name)}</Text>
        </View>
      </View>

      <Text style={[styles.vendorRailName, dark && styles.vendorRailNameDark]} numberOfLines={1}>
        {vendor?.name}
      </Text>
      <Text style={[styles.vendorRailMeta, dark && styles.vendorRailMetaDark]} numberOfLines={1}>
        ⭐ {getVendorRating(vendor)} · {estimateEta(vendor, activeService)}
      </Text>
      <Text style={[styles.vendorRailSub, dark && styles.vendorRailSubDark]} numberOfLines={1}>
        {getVendorMeta(vendor, activeService)}
      </Text>
    </TouchableOpacity>
  );
}

function ShortcutChip({ item, dark = false }) {
  return (
    <TouchableOpacity
      activeOpacity={0.92}
      style={[styles.shortcutChip, dark && styles.shortcutChipDark]}>
      <Ionicons
        name={item.icon}
        size={16}
        color={dark ? '#ffffff' : COLORS.text}
      />
      <Text style={[styles.shortcutChipText, dark && styles.shortcutChipTextDark]}>
        {item.label}
      </Text>
    </TouchableOpacity>
  );
}

function DealMiniCard({ item }) {
  return (
    <View style={styles.dealMiniCard}>
      <View style={styles.dealMiniVisual}>
        <Text style={styles.dealMiniEmoji}>{pickDealEmoji(item?.name)}</Text>
      </View>
      <Text style={styles.dealMiniName} numberOfLines={2}>
        {item?.name}
      </Text>
      <Text style={styles.dealMiniPrice}>{money(item?.price)}</Text>
    </View>
  );
}

function SceneFilterChip({ label, active, onPress }) {
  return (
    <TouchableOpacity
      activeOpacity={0.92}
      style={[styles.sceneFilterChip, active && styles.sceneFilterChipActive]}
      onPress={onPress}>
      <Text style={[styles.sceneFilterChipText, active && styles.sceneFilterChipTextActive]}>
        {label}
      </Text>
    </TouchableOpacity>
  );
}

function SceneEventCard({ item }) {
  return (
    <TouchableOpacity activeOpacity={0.92} style={styles.sceneEventCard}>
      <View style={[styles.sceneEventVisual, { backgroundColor: item.accent }]}>
        <Ionicons name={item.icon} size={30} color="#ffffff" />
        <Text style={styles.scenePricePill}>Starts at {money(item.price)}</Text>
      </View>
      <Text style={styles.sceneEventTitle} numberOfLines={2}>
        {item.title}
      </Text>
      <Text style={styles.sceneEventSubtitle} numberOfLines={2}>
        {item.subtitle}
      </Text>
    </TouchableOpacity>
  );
}

export default function ExploreScreen() {
  const router = useRouter();
  const tabBarHeight = useBottomTabBarHeight();

  const [search, setSearch] = useState('');
  const [activeWarehouseCategory, setActiveWarehouseCategory] = useState('fresh');
  const [activeSceneFilter, setActiveSceneFilter] = useState('Tonight');

  const {
    activeService,
    vendors,
    featuredVendors,
    recentVendors,
    favorites,
    toggleFavorite,
    rememberStore,
    recentSearches,
    homeDeals,
  } = useGrabBasket();

  const theme = SCREEN_THEME[activeService] || SCREEN_THEME.food;
  const isScenes = activeService === 'scenes';

  const discoveryVendors = useMemo(() => {
    const source =
      featuredVendors?.length > 0
        ? featuredVendors
        : recentVendors?.length > 0
          ? recentVendors
          : vendors || [];
    return source.slice(0, 8);
  }, [featuredVendors, recentVendors, vendors]);

  const foodDeals = useMemo(() => {
    return (homeDeals?.length > 0 ? homeDeals : []).slice(0, 6);
  }, [homeDeals]);

  const openVendor = (vendor) => {
    rememberStore(vendor.id);
    router.push({
      pathname: '/store/[vendorId]',
      params: { vendorId: String(vendor.id) },
    });
  };

  const filteredScenes = useMemo(() => {
    const items = FALLBACK_SCENES;
    if (activeSceneFilter === 'Tonight') return items;
    if (activeSceneFilter === 'Weekend') return items.slice(0, 2);
    if (activeSceneFilter === 'Comedy') {
      return items.filter((item) => item.title.toLowerCase().includes('comedy'));
    }
    if (activeSceneFilter === 'Workshops') {
      return items.filter((item) => item.title.toLowerCase().includes('workshop'));
    }
    return items;
  }, [activeSceneFilter]);

  const heroContent = useMemo(() => {
    if (activeService === 'warehouse') {
      return {
        title: 'Browse faster, shop smarter',
        subtitle: 'Sharper aisle grouping and quicker product discovery for Instamart-style shopping.',
        action: 'Open categories',
      };
    }
    if (activeService === 'eatout') {
      return {
        title: 'Your dining shortcuts',
        subtitle: 'Make the tab feel like a destination for table offers, moods and planned outings.',
        action: 'Discover places',
      };
    }
    if (activeService === 'scenes') {
      return {
        title: 'Experiences worth stepping out for',
        subtitle: 'Premium discovery for workshops, live shows and plans with friends.',
        action: 'See featured drops',
      };
    }
    return {
      title: 'Upgrade food discovery',
      subtitle: 'This tab should feel more premium than home, with stronger curation and clearer browse paths.',
      action: 'Explore collections',
    };
  }, [activeService]);

  return (
    <SafeAreaView style={[styles.safeArea, { backgroundColor: theme.page }]}>
      <StatusBar
        barStyle={isScenes ? 'light-content' : 'dark-content'}
        backgroundColor={theme.page}
      />

      <ScrollView
        showsVerticalScrollIndicator={false}
        contentContainerStyle={{ paddingBottom: tabBarHeight + 24 }}>
        <View style={[styles.headerWrap, isScenes && styles.headerWrapDark]}>
          <SectionHeader
            title={theme.title}
            subtitle={theme.subtitle}
            light={isScenes}
          />

          <SearchBar
            value={search}
            onChangeText={setSearch}
            placeholder={theme.searchPlaceholder}
            dark={isScenes}
          />

          <HeroCard
            theme={theme}
            title={heroContent.title}
            subtitle={heroContent.subtitle}
            primaryAction={heroContent.action}
            dark={isScenes}
          />
        </View>

        <View style={[styles.body, isScenes && styles.bodyDark]}>
          {activeService === 'food' ? (
            <>
              <SectionHeader title="Curated collections" actionLabel="View all" />
              <ScrollView
                horizontal
                showsHorizontalScrollIndicator={false}
                contentContainerStyle={styles.horizontalRail}>
                {FOOD_COLLECTIONS.map((item) => (
                  <CollectionCard key={item.key} item={item} activeService="food" />
                ))}
              </ScrollView>

              <SectionHeader title="Browse by cuisine" />
              <View style={styles.gridWrap}>
                {FOOD_CATEGORIES.map((item) => (
                  <View key={item.key} style={styles.gridCell}>
                    <CategoryGridCard item={item} />
                  </View>
                ))}
              </View>

              {foodDeals.length > 0 ? (
                <>
                  <SectionHeader title="Trending quick picks" actionLabel="View all" />
                  <ScrollView
                    horizontal
                    showsHorizontalScrollIndicator={false}
                    contentContainerStyle={styles.horizontalRail}>
                    {foodDeals.map((item) => (
                      <DealMiniCard key={item.id || item.name} item={item} />
                    ))}
                  </ScrollView>
                </>
              ) : null}

              <SectionHeader title="Popular picks near you" actionLabel="View all" />
              <ScrollView
                horizontal
                showsHorizontalScrollIndicator={false}
                contentContainerStyle={styles.horizontalRail}>
                {discoveryVendors.map((vendor) => (
                  <VendorRailCard
                    key={vendor.id}
                    vendor={vendor}
                    activeService="food"
                    favorite={Boolean(favorites[vendor.id])}
                    onToggleFavorite={() => toggleFavorite(vendor.id)}
                    onPress={() => openVendor(vendor)}
                  />
                ))}
              </ScrollView>
            </>
          ) : null}

          {activeService === 'warehouse' ? (
            <>
              <SectionHeader title="Shop by aisle" actionLabel="View all" />
              <View style={styles.gridWrap}>
                {WAREHOUSE_CATEGORY_GRID.map((item) => (
                  <View key={item.key} style={styles.gridCell}>
                    <CategoryGridCard
                      item={item}
                      active={activeWarehouseCategory === item.key}
                      onPress={() => setActiveWarehouseCategory(item.key)}
                    />
                  </View>
                ))}
              </View>

              <SectionHeader title="Smart aisles" />
              <ScrollView
                horizontal
                showsHorizontalScrollIndicator={false}
                contentContainerStyle={styles.horizontalRail}>
                {WAREHOUSE_AISLES.map((item) => (
                  <CollectionCard key={item.key} item={item} activeService="warehouse" />
                ))}
              </ScrollView>

              {foodDeals.length > 0 ? (
                <>
                  <SectionHeader title="Fast add-ons" actionLabel="View all" />
                  <ScrollView
                    horizontal
                    showsHorizontalScrollIndicator={false}
                    contentContainerStyle={styles.horizontalRail}>
                    {foodDeals.map((item) => (
                      <DealMiniCard key={item.id || item.name} item={item} />
                    ))}
                  </ScrollView>
                </>
              ) : null}

              <SectionHeader title="Top grocery stores" actionLabel="View all" />
              <ScrollView
                horizontal
                showsHorizontalScrollIndicator={false}
                contentContainerStyle={styles.horizontalRail}>
                {discoveryVendors.map((vendor) => (
                  <VendorRailCard
                    key={vendor.id}
                    vendor={vendor}
                    activeService="warehouse"
                    favorite={Boolean(favorites[vendor.id])}
                    onToggleFavorite={() => toggleFavorite(vendor.id)}
                    onPress={() => openVendor(vendor)}
                  />
                ))}
              </ScrollView>
            </>
          ) : null}

          {activeService === 'eatout' ? (
            <>
              <SectionHeader title="Shortcuts for tonight" />
              <View style={styles.shortcutWrap}>
                {EATOUT_SHORTCUTS.map((item) => (
                  <View key={item.key} style={styles.shortcutCell}>
                    <ShortcutChip item={item} />
                  </View>
                ))}
              </View>

              <SectionHeader title="Dining collections" actionLabel="View all" />
              <ScrollView
                horizontal
                showsHorizontalScrollIndicator={false}
                contentContainerStyle={styles.horizontalRail}>
                {EATOUT_COLLECTIONS.map((item) => (
                  <CollectionCard key={item.key} item={item} activeService="eatout" />
                ))}
              </ScrollView>

              {recentSearches?.length > 0 ? (
                <>
                  <SectionHeader title="Your recent intents" />
                  <View style={styles.recentChipWrap}>
                    {recentSearches.slice(0, 6).map((item) => (
                      <View key={item} style={styles.recentChip}>
                        <Ionicons name="search-outline" size={14} color={COLORS.muted} />
                        <Text style={styles.recentChipText}>{item}</Text>
                      </View>
                    ))}
                  </View>
                </>
              ) : null}

              <SectionHeader title="Places worth booking" actionLabel="View all" />
              <ScrollView
                horizontal
                showsHorizontalScrollIndicator={false}
                contentContainerStyle={styles.horizontalRail}>
                {discoveryVendors.map((vendor) => (
                  <VendorRailCard
                    key={vendor.id}
                    vendor={vendor}
                    activeService="eatout"
                    favorite={Boolean(favorites[vendor.id])}
                    onToggleFavorite={() => toggleFavorite(vendor.id)}
                    onPress={() => openVendor(vendor)}
                  />
                ))}
              </ScrollView>
            </>
          ) : null}

          {activeService === 'scenes' ? (
            <>
              <SectionHeader title="Featured categories" light actionLabel="View all" />
              <ScrollView
                horizontal
                showsHorizontalScrollIndicator={false}
                contentContainerStyle={styles.horizontalRail}>
                {SCENE_COLLECTIONS.map((item) => (
                  <CollectionCard key={item.key} item={item} activeService="scenes" />
                ))}
              </ScrollView>

              <SectionHeader title="Filter by vibe" light />
              <ScrollView
                horizontal
                showsHorizontalScrollIndicator={false}
                contentContainerStyle={styles.horizontalRail}>
                {SCENE_FILTERS.map((label) => (
                  <SceneFilterChip
                    key={label}
                    label={label}
                    active={activeSceneFilter === label}
                    onPress={() => setActiveSceneFilter(label)}
                  />
                ))}
              </ScrollView>

              <SectionHeader
                title="Trending experiences"
                subtitle="Make these cards feel merchandised, not placeholder."
                light
              />
              <ScrollView
                horizontal
                showsHorizontalScrollIndicator={false}
                contentContainerStyle={styles.horizontalRail}>
                {filteredScenes.map((item) => (
                  <SceneEventCard key={item.id} item={item} />
                ))}
              </ScrollView>

              <SectionHeader title="Popular venues" light actionLabel="View all" />
              <ScrollView
                horizontal
                showsHorizontalScrollIndicator={false}
                contentContainerStyle={styles.horizontalRail}>
                {discoveryVendors.map((vendor) => (
                  <VendorRailCard
                    key={vendor.id}
                    vendor={vendor}
                    activeService="scenes"
                    favorite={Boolean(favorites[vendor.id])}
                    onToggleFavorite={() => toggleFavorite(vendor.id)}
                    onPress={() => openVendor(vendor)}
                    dark
                  />
                ))}
              </ScrollView>
            </>
          ) : null}
        </View>
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: {
    flex: 1,
  },

  headerWrap: {
    paddingHorizontal: 16,
    paddingTop: 8,
  },
  headerWrapDark: {
    backgroundColor: COLORS.black,
  },

  body: {
    paddingHorizontal: 16,
    paddingTop: 14,
  },
  bodyDark: {
    backgroundColor: COLORS.black,
  },

  sectionHeader: {
    marginBottom: 12,
    flexDirection: 'row',
    alignItems: 'flex-end',
    gap: 10,
  },
  sectionTitle: {
    color: COLORS.text,
    fontSize: 26,
    fontWeight: '900',
  },
  sectionTitleLight: {
    color: '#ffffff',
  },
  sectionSubtitle: {
    marginTop: 4,
    color: COLORS.muted,
    fontSize: 13,
    fontWeight: '600',
    lineHeight: 18,
  },
  sectionSubtitleLight: {
    color: COLORS.darkMuted,
  },
  sectionAction: {
    color: COLORS.orange,
    fontSize: 14,
    fontWeight: '900',
  },
  sectionActionLight: {
    color: '#ffffff',
  },

  searchBar: {
    minHeight: 56,
    borderRadius: 18,
    backgroundColor: COLORS.card,
    borderWidth: 1,
    borderColor: COLORS.border,
    paddingHorizontal: 16,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    marginBottom: 14,
  },
  searchBarDark: {
    backgroundColor: COLORS.darkSurface,
    borderColor: COLORS.darkBorder,
  },
  searchInput: {
    flex: 1,
    color: COLORS.text,
    fontSize: 15,
    fontWeight: '600',
  },
  searchInputDark: {
    color: '#ffffff',
  },

  heroCard: {
    minHeight: 152,
    borderRadius: 26,
    overflow: 'hidden',
    padding: 20,
    justifyContent: 'space-between',
    marginBottom: 16,
  },
  heroOrbOne: {
    position: 'absolute',
    right: -28,
    top: -18,
    width: 150,
    height: 150,
    borderRadius: 75,
    opacity: 0.55,
  },
  heroOrbTwo: {
    position: 'absolute',
    left: -20,
    bottom: -40,
    width: 140,
    height: 140,
    borderRadius: 70,
    opacity: 0.35,
  },
  heroTitle: {
    fontSize: 28,
    fontWeight: '900',
    lineHeight: 32,
    maxWidth: '90%',
  },
  heroSubtitle: {
    marginTop: 8,
    fontSize: 14,
    fontWeight: '700',
    lineHeight: 20,
    maxWidth: '90%',
  },
  heroActionRow: {
    marginTop: 16,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
  },
  heroPrimaryButton: {
    minHeight: 42,
    borderRadius: 14,
    paddingHorizontal: 16,
    alignItems: 'center',
    justifyContent: 'center',
  },
  heroPrimaryButtonLight: {
    backgroundColor: COLORS.text,
  },
  heroPrimaryButtonDark: {
    backgroundColor: '#ffffff',
  },
  heroPrimaryButtonText: {
    color: '#ffffff',
    fontSize: 14,
    fontWeight: '900',
  },
  heroPrimaryButtonTextDark: {
    color: COLORS.text,
  },
  heroIconButton: {
    width: 42,
    height: 42,
    borderRadius: 14,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: 'rgba(16,24,40,0.08)',
  },
  heroIconButtonDark: {
    backgroundColor: 'rgba(255,255,255,0.12)',
  },

  horizontalRail: {
    gap: 12,
    paddingBottom: 6,
  },

  collectionCard: {
    width: 220,
    minHeight: 150,
    borderRadius: 22,
    backgroundColor: COLORS.card,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 16,
    justifyContent: 'space-between',
    marginBottom: 12,
  },
  collectionIconWrap: {
    width: 42,
    height: 42,
    borderRadius: 14,
    backgroundColor: '#f4f5f7',
    alignItems: 'center',
    justifyContent: 'center',
  },
  collectionIconWrapDark: {
    backgroundColor: 'rgba(255,255,255,0.12)',
  },
  collectionTitle: {
    marginTop: 22,
    color: COLORS.text,
    fontSize: 20,
    fontWeight: '900',
    lineHeight: 24,
  },
  collectionTitleDark: {
    color: '#ffffff',
  },
  collectionSubtitle: {
    marginTop: 8,
    color: COLORS.muted,
    fontSize: 13,
    fontWeight: '600',
    lineHeight: 18,
  },
  collectionSubtitleDark: {
    color: '#d8e1f2',
  },

  gridWrap: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    marginHorizontal: -6,
    marginBottom: 10,
  },
  gridCell: {
    width: '25%',
    paddingHorizontal: 6,
    marginBottom: 12,
  },
  categoryGridCard: {
    minHeight: 106,
    borderRadius: 20,
    backgroundColor: COLORS.card,
    borderWidth: 1,
    borderColor: COLORS.border,
    paddingHorizontal: 10,
    paddingVertical: 12,
    alignItems: 'center',
    justifyContent: 'center',
  },
  categoryGridCardDark: {
    backgroundColor: COLORS.darkSurface,
    borderColor: COLORS.darkBorder,
  },
  categoryGridCardActive: {
    backgroundColor: COLORS.blue,
    borderColor: COLORS.blue,
  },
  categoryIconWrap: {
    width: 40,
    height: 40,
    borderRadius: 14,
    backgroundColor: '#f4f5f7',
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 10,
  },
  categoryIconWrapDark: {
    backgroundColor: COLORS.darkSurfaceAlt,
  },
  categoryGridLabel: {
    color: COLORS.text,
    fontSize: 12,
    fontWeight: '800',
    textAlign: 'center',
    lineHeight: 16,
  },
  categoryGridLabelDark: {
    color: '#ffffff',
  },
  categoryGridLabelActive: {
    color: '#ffffff',
  },

  vendorRailCard: {
    width: 184,
    marginBottom: 12,
  },
  vendorRailCardDark: {},
  vendorRailVisual: {
    height: 152,
    borderRadius: 22,
    overflow: 'hidden',
    padding: 12,
    justifyContent: 'space-between',
  },
  vendorRailTop: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
  },
  offerPill: {
    borderRadius: 999,
    backgroundColor: 'rgba(255,255,255,0.18)',
    paddingHorizontal: 10,
    paddingVertical: 6,
  },
  offerPillText: {
    color: '#ffffff',
    fontSize: 11,
    fontWeight: '900',
  },
  favoriteButton: {
    width: 30,
    height: 30,
    borderRadius: 15,
    backgroundColor: 'rgba(0,0,0,0.22)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  vendorMonogram: {
    width: 58,
    height: 58,
    borderRadius: 29,
    alignSelf: 'center',
    backgroundColor: 'rgba(255,255,255,0.16)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  vendorMonogramText: {
    color: '#ffffff',
    fontSize: 22,
    fontWeight: '900',
  },
  vendorRailName: {
    marginTop: 10,
    color: COLORS.text,
    fontSize: 16,
    fontWeight: '900',
  },
  vendorRailNameDark: {
    color: '#ffffff',
  },
  vendorRailMeta: {
    marginTop: 4,
    color: COLORS.text,
    fontSize: 13,
    fontWeight: '700',
  },
  vendorRailMetaDark: {
    color: '#ffffff',
  },
  vendorRailSub: {
    marginTop: 4,
    color: COLORS.muted,
    fontSize: 12,
    fontWeight: '600',
  },
  vendorRailSubDark: {
    color: COLORS.darkMuted,
  },

  shortcutWrap: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    marginHorizontal: -6,
    marginBottom: 8,
  },
  shortcutCell: {
    width: '50%',
    paddingHorizontal: 6,
    marginBottom: 12,
  },
  shortcutChip: {
    minHeight: 58,
    borderRadius: 18,
    backgroundColor: COLORS.card,
    borderWidth: 1,
    borderColor: COLORS.border,
    paddingHorizontal: 14,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
  },
  shortcutChipDark: {
    backgroundColor: COLORS.darkSurface,
    borderColor: COLORS.darkBorder,
  },
  shortcutChipText: {
    flex: 1,
    color: COLORS.text,
    fontSize: 14,
    fontWeight: '800',
  },
  shortcutChipTextDark: {
    color: '#ffffff',
  },

  recentChipWrap: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 10,
    marginBottom: 10,
  },
  recentChip: {
    borderRadius: 999,
    backgroundColor: COLORS.card,
    borderWidth: 1,
    borderColor: COLORS.border,
    paddingHorizontal: 12,
    paddingVertical: 10,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  recentChipText: {
    color: COLORS.text,
    fontSize: 13,
    fontWeight: '700',
  },

  dealMiniCard: {
    width: 138,
    borderRadius: 20,
    backgroundColor: COLORS.card,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 12,
    marginBottom: 12,
  },
  dealMiniVisual: {
    height: 84,
    borderRadius: 14,
    backgroundColor: '#f7f8fa',
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 10,
  },
  dealMiniEmoji: {
    fontSize: 34,
  },
  dealMiniName: {
    color: COLORS.text,
    fontSize: 14,
    fontWeight: '800',
    lineHeight: 18,
    minHeight: 36,
  },
  dealMiniPrice: {
    marginTop: 8,
    color: COLORS.text,
    fontSize: 15,
    fontWeight: '900',
  },

  sceneFilterChip: {
    borderRadius: 999,
    backgroundColor: COLORS.darkSurface,
    borderWidth: 1,
    borderColor: COLORS.darkBorder,
    paddingHorizontal: 14,
    paddingVertical: 10,
    marginBottom: 8,
  },
  sceneFilterChipActive: {
    backgroundColor: '#ffffff',
    borderColor: '#ffffff',
  },
  sceneFilterChipText: {
    color: '#ffffff',
    fontSize: 13,
    fontWeight: '800',
  },
  sceneFilterChipTextActive: {
    color: COLORS.text,
  },

  sceneEventCard: {
    width: 250,
    borderRadius: 24,
    backgroundColor: COLORS.darkSurface,
    borderWidth: 1,
    borderColor: COLORS.darkBorder,
    padding: 12,
    marginBottom: 12,
  },
  sceneEventVisual: {
    height: 150,
    borderRadius: 18,
    padding: 14,
    justifyContent: 'space-between',
    marginBottom: 12,
  },
  scenePricePill: {
    alignSelf: 'flex-start',
    borderRadius: 999,
    backgroundColor: COLORS.pink,
    color: '#ffffff',
    paddingHorizontal: 10,
    paddingVertical: 6,
    overflow: 'hidden',
    fontSize: 11,
    fontWeight: '900',
  },
  sceneEventTitle: {
    color: '#ffffff',
    fontSize: 17,
    fontWeight: '900',
    lineHeight: 22,
  },
  sceneEventSubtitle: {
    marginTop: 6,
    color: COLORS.darkMuted,
    fontSize: 13,
    fontWeight: '600',
    lineHeight: 18,
  },
});