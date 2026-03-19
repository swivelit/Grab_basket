import React, { useCallback, useEffect, useMemo, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  RefreshControl,
  SafeAreaView,
  ScrollView,
  StatusBar,
  StyleSheet,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { Ionicons } from '@expo/vector-icons';
import { API_BASE_URL } from './src/config';

const STORAGE_CART = '@grab_basket/cart_v2';
const STORAGE_FAVORITES = '@grab_basket/favorites_v1';

const TOP_SERVICES = [
  { key: 'food', icon: 'restaurant-outline', label: 'Food', subtitle: 'Restaurants' },
  { key: 'instamart', icon: 'basket-outline', label: 'Instamart', subtitle: 'Groceries' },
  { key: 'dineout', icon: 'wine-outline', label: 'Dineout', subtitle: 'Dining deals' },
  { key: 'scenes', icon: 'color-wand-outline', label: 'Scenes', subtitle: 'Events' },
];

const HOME_FILTERS = ['All', 'Open now', 'Low to high', 'High to low', 'A-Z'];

const HOME_SHORTCUTS = [
  { key: 'all', label: 'All', icon: 'apps-outline' },
  { key: 'fresh', label: 'Fresh', icon: 'leaf-outline' },
  { key: 'maxx', label: 'Maxxsaver', icon: 'pricetags-outline' },
  { key: 'ramzan', label: 'Ramzan', icon: 'moon-outline' },
  { key: 'exam', label: 'Exam Ready', icon: 'school-outline' },
];

const FESTIVAL_TILES = [
  { key: 'navratri', title: 'Chaitra\nNavratri', emoji: '🪔', accent: '#f8c8e6' },
  { key: 'eid', title: 'Eid-Ul-Fitr', emoji: '🥤', accent: '#f8c8e6' },
  { key: 'ugadi', title: 'Ugadi', emoji: '🥣', accent: '#f8c8e6' },
  { key: 'gudi', title: 'Gudi\nPadwa', emoji: '🌾', accent: '#f8c8e6' },
];

const MERCH_DEALS = [
  { key: 'deal-1', name: 'Fresh Curd', price: '₹9', subtitle: 'Starter essential', emoji: '🥛' },
  { key: 'deal-2', name: 'Chocolate Bar', price: '₹9', subtitle: 'Quick sweet bite', emoji: '🍫' },
  { key: 'deal-3', name: 'Mixed Fruit Jam', price: '₹9', subtitle: 'Breakfast saver', emoji: '🍓' },
  { key: 'deal-4', name: 'Classic Chips', price: '₹9', subtitle: 'Impulse add-on', emoji: '🥔' },
];

const CATEGORY_GRID = [
  { emoji: '🥬', title: 'Vegetables' },
  { emoji: '🍎', title: 'Fruits' },
  { emoji: '🥛', title: 'Dairy' },
  { emoji: '🍞', title: 'Bakery' },
  { emoji: '🍫', title: 'Snacks' },
  { emoji: '🥤', title: 'Drinks' },
  { emoji: '🧴', title: 'Personal Care' },
  { emoji: '🧼', title: 'Home Care' },
];

const ACCOUNT_ROWS = [
  { icon: 'location-outline', label: 'Saved addresses', value: 'Connect this to /addresses when auth returns' },
  { icon: 'card-outline', label: 'Payments', value: 'Wallet / UPI / cards next' },
  { icon: 'ticket-outline', label: 'Coupons', value: 'Dynamic promo engine pending' },
  { icon: 'chatbubble-ellipses-outline', label: 'Support', value: 'Help centre and order issue flows pending' },
];

const STORE_TONES = ['#D9F99D', '#FDE68A', '#BFDBFE', '#FBCFE8', '#C7D2FE', '#A7F3D0'];

function money(value) {
  return `₹${Number(value || 0).toFixed(0)}`;
}

function initials(name = '') {
  return name
    .split(' ')
    .filter(Boolean)
    .map((part) => part[0])
    .join('')
    .slice(0, 2)
    .toUpperCase();
}

function estimateEta(vendor) {
  if (vendor?.distance_km != null) {
    if (vendor.distance_km <= 2) return '15-20 mins';
    if (vendor.distance_km <= 5) return '20-30 mins';
    return '30-45 mins';
  }
  return '23 mins';
}

function getDeliveryFee(vendor) {
  if (vendor?.distance_km != null && vendor.distance_km <= 2) return 'Free delivery';
  if (vendor?.distance_km != null && vendor.distance_km <= 5) return '₹19 delivery';
  return '₹29 delivery';
}

function getStoreTone(seed = 0) {
  return STORE_TONES[seed % STORE_TONES.length];
}

function buildVendorQuery(search, filter) {
  const params = new URLSearchParams();
  if (search.trim()) params.set('q', search.trim());
  if (filter === 'Open now') params.set('open_only', 'true');
  params.set('limit', '50');
  return `/vendors?${params.toString()}`;
}

function sortVendors(list, filter) {
  const cloned = [...list];

  switch (filter) {
    case 'Low to high':
      return cloned.sort((a, b) => {
        const av = a.distance_km ?? Number.MAX_SAFE_INTEGER;
        const bv = b.distance_km ?? Number.MAX_SAFE_INTEGER;
        return av - bv;
      });
    case 'High to low':
      return cloned.sort((a, b) => {
        const av = a.distance_km ?? 0;
        const bv = b.distance_km ?? 0;
        return bv - av;
      });
    case 'A-Z':
      return cloned.sort((a, b) => a.name.localeCompare(b.name));
    default:
      return cloned;
  }
}

async function apiRequest(path) {
  const response = await fetch(`${API_BASE_URL}${path}`, {
    headers: {
      Accept: 'application/json',
    },
  });

  const raw = await response.text();
  let data = null;

  try {
    data = raw ? JSON.parse(raw) : null;
  } catch {
    data = raw;
  }

  if (!response.ok) {
    const message =
      (data && typeof data === 'object' && (data.detail || data?.error?.message)) ||
      (typeof data === 'string' && data) ||
      'Request failed';
    throw new Error(message);
  }

  return data;
}

export default function App() {
  const [activeTab, setActiveTab] = useState('home');
  const [activeService, setActiveService] = useState('instamart');
  const [activeShortcut, setActiveShortcut] = useState('all');
  const [homeSearch, setHomeSearch] = useState('');
  const [homeFilter, setHomeFilter] = useState('All');

  const [vendors, setVendors] = useState([]);
  const [vendorsLoading, setVendorsLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const [selectedVendor, setSelectedVendor] = useState(null);
  const [productSearch, setProductSearch] = useState('');
  const [products, setProducts] = useState([]);
  const [productsLoading, setProductsLoading] = useState(false);

  const [cart, setCart] = useState({ vendorId: null, items: {} });
  const [favorites, setFavorites] = useState({});
  const [showCart, setShowCart] = useState(false);

  useEffect(() => {
    let active = true;

    (async () => {
      try {
        const stored = await AsyncStorage.multiGet([STORAGE_CART, STORAGE_FAVORITES]);
        if (!active) return;

        const savedCart = stored[0]?.[1];
        const savedFavorites = stored[1]?.[1];

        if (savedCart) {
          setCart(JSON.parse(savedCart));
        }
        if (savedFavorites) {
          setFavorites(JSON.parse(savedFavorites));
        }
      } catch {
        // Ignore boot persistence errors for guest mode.
      }
    })();

    return () => {
      active = false;
    };
  }, []);

  useEffect(() => {
    AsyncStorage.setItem(STORAGE_CART, JSON.stringify(cart)).catch(() => {});
  }, [cart]);

  useEffect(() => {
    AsyncStorage.setItem(STORAGE_FAVORITES, JSON.stringify(favorites)).catch(() => {});
  }, [favorites]);

  const loadVendors = useCallback(
    async ({ pullToRefresh = false } = {}) => {
      try {
        if (pullToRefresh) {
          setRefreshing(true);
        } else {
          setVendorsLoading(true);
        }

        const data = await apiRequest(buildVendorQuery(homeSearch, homeFilter));
        const parsed = Array.isArray(data) ? data : [];
        setVendors(sortVendors(parsed, homeFilter));
      } catch (error) {
        setVendors([]);
        Alert.alert('Could not load stores', error.message);
      } finally {
        setVendorsLoading(false);
        setRefreshing(false);
      }
    },
    [homeSearch, homeFilter]
  );

  useEffect(() => {
    const timer = setTimeout(() => {
      loadVendors();
    }, 250);

    return () => clearTimeout(timer);
  }, [loadVendors]);

  const loadProducts = useCallback(async (vendor, searchValue = '') => {
    try {
      setProductsLoading(true);
      const params = new URLSearchParams();
      if (searchValue.trim()) params.set('q', searchValue.trim());
      params.set('limit', '200');
      const qs = params.toString();
      const data = await apiRequest(`/vendors/${vendor.id}/products${qs ? `?${qs}` : ''}`);
      setProducts(Array.isArray(data) ? data : []);
    } catch (error) {
      setProducts([]);
      Alert.alert('Could not load products', error.message);
    } finally {
      setProductsLoading(false);
    }
  }, []);

  useEffect(() => {
    if (!selectedVendor) return undefined;

    const timer = setTimeout(() => {
      loadProducts(selectedVendor, productSearch);
    }, 250);

    return () => clearTimeout(timer);
  }, [selectedVendor, productSearch, loadProducts]);

  const cartItems = useMemo(() => Object.values(cart.items), [cart]);
  const cartCount = useMemo(
    () => cartItems.reduce((sum, item) => sum + item.qty, 0),
    [cartItems]
  );
  const cartSubtotal = useMemo(
    () => cartItems.reduce((sum, item) => sum + Number(item.price || 0) * item.qty, 0),
    [cartItems]
  );

  const cartVendorName = useMemo(() => {
    if (!cart.vendorId) return 'your store';
    const vendor = vendors.find((item) => item.id === cart.vendorId) || selectedVendor;
    return vendor?.name || 'your store';
  }, [cart.vendorId, vendors, selectedVendor]);

  const featuredVendors = useMemo(() => vendors.slice(0, 6), [vendors]);
  const favoriteVendors = useMemo(
    () => vendors.filter((vendor) => favorites[vendor.id]),
    [vendors, favorites]
  );
  const bestSellerProducts = useMemo(() => products.slice(0, 4), [products]);

  const toggleFavorite = (vendorId) => {
    setFavorites((current) => ({
      ...current,
      [vendorId]: !current[vendorId],
    }));
  };

  const replaceCartWith = (product) => {
    setCart({
      vendorId: product.vendor_id,
      items: {
        [product.id]: {
          ...product,
          qty: 1,
        },
      },
    });
  };

  const addToCart = (product) => {
    if (cart.vendorId && cart.vendorId !== product.vendor_id) {
      Alert.alert(
        'Replace cart?',
        'Only one store is active in the basket. Replace the current basket with this item?',
        [
          { text: 'Cancel', style: 'cancel' },
          {
            text: 'Replace',
            style: 'destructive',
            onPress: () => replaceCartWith(product),
          },
        ]
      );
      return;
    }

    setCart((current) => {
      const existing = current.items[product.id];
      return {
        vendorId: product.vendor_id,
        items: {
          ...current.items,
          [product.id]: {
            ...(existing || product),
            qty: existing ? existing.qty + 1 : 1,
          },
        },
      };
    });
  };

  const updateQty = (product, delta) => {
    setCart((current) => {
      const existing = current.items[product.id];
      if (!existing) return current;

      const nextQty = existing.qty + delta;
      const nextItems = { ...current.items };

      if (nextQty <= 0) {
        delete nextItems[product.id];
      } else {
        nextItems[product.id] = {
          ...existing,
          qty: nextQty,
        };
      }

      const hasItems = Object.keys(nextItems).length > 0;

      return {
        vendorId: hasItems ? current.vendorId : null,
        items: nextItems,
      };
    });
  };

  const clearCart = () => {
    setCart({ vendorId: null, items: {} });
  };

  const openVendor = async (vendor) => {
    setSelectedVendor(vendor);
    setProductSearch('');
    setProducts([]);
    await loadProducts(vendor, '');
  };

  const previewCheckout = () => {
    if (cartItems.length === 0) {
      Alert.alert('Cart is empty', 'Add some products first.');
      return;
    }

    Alert.alert(
      'Checkout flow pending',
      'Guest browsing is ready. The next step is wiring addresses, payments, and final order placement into /orders.'
    );
  };

  const renderHome = () => (
    <View style={styles.homeScreen}>
      <ScrollView
        showsVerticalScrollIndicator={false}
        contentContainerStyle={styles.homeScrollContent}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={() => loadVendors({ pullToRefresh: true })}
            tintColor="#ffffff"
          />
        }>
        <View style={styles.heroShell}>
          <View style={styles.heroTopRow}>
            <View style={{ flex: 1 }}>
              <Text style={styles.heroEta}>23 mins</Text>
              <TouchableOpacity activeOpacity={0.85} style={styles.addressRow}>
                <Text style={styles.addressText} numberOfLines={1}>
                  To Valliachans Place: 12b, Great Orchard...
                </Text>
                <Ionicons name="chevron-down" size={16} color="#d1fae5" />
              </TouchableOpacity>
            </View>
            <TouchableOpacity style={styles.profileDot} activeOpacity={0.9}>
              <Ionicons name="person-outline" size={22} color="#ffffff" />
            </TouchableOpacity>
          </View>

          <ScrollView
            horizontal
            showsHorizontalScrollIndicator={false}
            contentContainerStyle={styles.serviceRail}>
            {TOP_SERVICES.map((item) => {
              const active = activeService === item.key;
              return (
                <TouchableOpacity
                  key={item.key}
                  activeOpacity={0.9}
                  style={[styles.serviceCard, active && styles.serviceCardActive]}
                  onPress={() => setActiveService(item.key)}>
                  <View style={[styles.serviceIconWrap, active && styles.serviceIconWrapActive]}>
                    <Ionicons name={item.icon} size={22} color={active ? '#0b7a5a' : '#ffffff'} />
                  </View>
                  <Text style={[styles.serviceLabel, active && styles.serviceLabelActive]}>{item.label}</Text>
                  <Text style={[styles.serviceSubLabel, active && styles.serviceSubLabelActive]}>{item.subtitle}</Text>
                </TouchableOpacity>
              );
            })}
          </ScrollView>

          <View style={styles.searchRow}>
            <View style={styles.searchBoxHero}>
              <Ionicons name="search-outline" size={20} color="#6b7280" />
              <TextInput
                style={styles.searchInput}
                placeholder="Search for Sunscreen"
                placeholderTextColor="#9ca3af"
                value={homeSearch}
                onChangeText={setHomeSearch}
              />
              <Ionicons name="receipt-outline" size={20} color="#6b7280" />
            </View>
            <TouchableOpacity style={styles.bookmarkButton} activeOpacity={0.9}>
              <Ionicons name="bookmark-outline" size={22} color="#ffffff" />
            </TouchableOpacity>
          </View>

          <ScrollView
            horizontal
            showsHorizontalScrollIndicator={false}
            contentContainerStyle={styles.shortcutRail}>
            {HOME_SHORTCUTS.map((item) => {
              const active = activeShortcut === item.key;
              return (
                <TouchableOpacity
                  key={item.key}
                  style={styles.shortcutItem}
                  activeOpacity={0.9}
                  onPress={() => setActiveShortcut(item.key)}>
                  <View style={[styles.shortcutIconWrap, active && styles.shortcutIconWrapActive]}>
                    <Ionicons name={item.icon} size={18} color={active ? '#ffffff' : '#d1fae5'} />
                  </View>
                  <Text style={[styles.shortcutLabel, active && styles.shortcutLabelActive]}>{item.label}</Text>
                  {active ? <View style={styles.shortcutUnderline} /> : <View style={styles.shortcutUnderlineSpacer} />}
                </TouchableOpacity>
              );
            })}
          </ScrollView>

          <View style={styles.celebrationWrap}>
            <Text style={styles.celebrationEyebrow}>SEASON OF</Text>
            <Text style={styles.celebrationTitle}>CELEBRATION</Text>
          </View>

          <ScrollView
            horizontal
            showsHorizontalScrollIndicator={false}
            contentContainerStyle={styles.festivalRail}>
            {FESTIVAL_TILES.map((item) => (
              <View key={item.key} style={[styles.festivalCard, { backgroundColor: item.accent }]}>
                <Text style={styles.festivalTitle}>{item.title}</Text>
                <Text style={styles.festivalEmoji}>{item.emoji}</Text>
              </View>
            ))}
          </ScrollView>
        </View>

        <View style={styles.bodySurface}>
          <View style={styles.everydayBanner}>
            <View style={styles.everydayBadge}>
              <Text style={styles.everydayPrice}>₹9</Text>
            </View>
            <View style={{ flex: 1 }}>
              <Text style={styles.everydayTitle}>everyday</Text>
              <Text style={styles.everydayText}>Shop for ₹199 to get one item at ₹9</Text>
            </View>
          </View>

          <View style={styles.dealGrid}>
            {MERCH_DEALS.map((item) => (
              <QuickDealCard key={item.key} item={item} />
            ))}
          </View>

          <ScrollView
            horizontal
            showsHorizontalScrollIndicator={false}
            contentContainerStyle={styles.filterRail}>
            {HOME_FILTERS.map((item) => (
              <TouchableOpacity
                key={item}
                style={[styles.filterChip, homeFilter === item && styles.filterChipActive]}
                onPress={() => setHomeFilter(item)}>
                <Text style={[styles.filterChipText, homeFilter === item && styles.filterChipTextActive]}>{item}</Text>
              </TouchableOpacity>
            ))}
          </ScrollView>

          <SectionHeader
            title="Popular categories"
            subtitle="The home now feels closer to the screenshot, but true Swiggy quality still needs real banners, real photos, dynamic ranking and personalization."
          />

          <View style={styles.categoryGrid}>
            {CATEGORY_GRID.map((item) => (
              <View key={item.title} style={styles.categoryTile}>
                <Text style={styles.categoryEmoji}>{item.emoji}</Text>
                <Text style={styles.categoryTitle}>{item.title}</Text>
              </View>
            ))}
          </View>

          <SectionHeader title="Featured stores" subtitle="Powered by your existing /vendors endpoint." />

          {vendorsLoading ? (
            <LoadingBlock label="Loading stores..." />
          ) : featuredVendors.length === 0 ? (
            <EmptyState
              title="No stores found"
              text="Create vendor and product data in the backend and this feed will populate automatically."
            />
          ) : (
            featuredVendors.map((vendor, index) => (
              <StoreCard
                key={vendor.id}
                vendor={vendor}
                favorite={!!favorites[vendor.id]}
                onOpen={() => openVendor(vendor)}
                onToggleFavorite={() => toggleFavorite(vendor.id)}
                tone={getStoreTone(index)}
              />
            ))
          )}
        </View>
      </ScrollView>
    </View>
  );

  const renderCategories = () => (
    <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={styles.pageContent}>
      <SectionHeader title="Categories" subtitle="This is the grocery-first browse view." />

      <View style={styles.categoryGrid}>
        {CATEGORY_GRID.map((item) => (
          <View key={item.title} style={styles.categoryTileLarge}>
            <Text style={styles.categoryEmojiLarge}>{item.emoji}</Text>
            <Text style={styles.categoryTitleLarge}>{item.title}</Text>
            <Text style={styles.tileMeta}>Top picks and bundles</Text>
          </View>
        ))}
      </View>

      <SectionHeader title="Favorite stores" subtitle="Stored locally for guest mode right now." />
      {favoriteVendors.length === 0 ? (
        <EmptyState title="No favorites yet" text="Tap the heart icon on a store card to save it here." />
      ) : (
        favoriteVendors.map((vendor, index) => (
          <StoreCard
            key={`fav-${vendor.id}`}
            vendor={vendor}
            favorite={!!favorites[vendor.id]}
            onOpen={() => openVendor(vendor)}
            onToggleFavorite={() => toggleFavorite(vendor.id)}
            tone={getStoreTone(index + 1)}
          />
        ))
      )}
    </ScrollView>
  );

  const renderReorder = () => (
    <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={styles.pageContent}>
      <SectionHeader title="Reorder" subtitle="Since we are skipping sign-in, this stays in guest mode." />
      {cartItems.length === 0 ? (
        <EmptyState
          title="No recent guest basket"
          text="Once you add items, this tab becomes a useful reorder shortcut. For real reorders, connect it to /orders/me after auth is back."
        />
      ) : (
        <View style={styles.panelCard}>
          <Text style={styles.panelTitle}>Current basket snapshot</Text>
          <Text style={styles.panelText}>{cartVendorName}</Text>
          <Text style={styles.panelSubText}>
            {cartCount} items · {money(cartSubtotal)}
          </Text>
          <TouchableOpacity style={styles.primaryButton} onPress={() => setShowCart(true)}>
            <Text style={styles.primaryButtonText}>Open cart</Text>
          </TouchableOpacity>
        </View>
      )}

      <View style={styles.noteCard}>
        <Text style={styles.noteTitle}>What still blocks a true reorder experience</Text>
        <Text style={styles.noteText}>
          Reorder history, past invoices, repeat recommendations, unavailable-item substitution and scheduled delivery are still missing.
        </Text>
      </View>
    </ScrollView>
  );

  const renderAccount = () => (
    <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={styles.pageContent}>
      <SectionHeader title="Account" subtitle="Sign-in is intentionally skipped for now, so this tab stays informational." />
      <View style={styles.accountCard}>
        <View style={styles.accountAvatar}>
          <Text style={styles.accountAvatarText}>{initials('Grab Basket')}</Text>
        </View>
        <Text style={styles.accountTitle}>Guest mode active</Text>
        <Text style={styles.accountText}>
          You can browse stores, search products, manage favorites and build a basket without auth.
        </Text>
      </View>

      {ACCOUNT_ROWS.map((row) => (
        <View key={row.label} style={styles.accountRow}>
          <View style={styles.accountRowIcon}>
            <Ionicons name={row.icon} size={18} color="#111827" />
          </View>
          <View style={{ flex: 1 }}>
            <Text style={styles.accountRowLabel}>{row.label}</Text>
            <Text style={styles.accountRowValue}>{row.value}</Text>
          </View>
          <Ionicons name="chevron-forward" size={18} color="#9ca3af" />
        </View>
      ))}

      <View style={styles.noteCard}>
        <Text style={styles.noteTitle}>Biggest product gaps vs Swiggy standard</Text>
        <Text style={styles.noteText}>
          Real image-led cards, smart search suggestions, address-aware ETA, dynamic promos, ratings, delivery fees, order tracking and checkout confidence layers.
        </Text>
      </View>
    </ScrollView>
  );

  const renderVendorDetails = () => (
    <View style={styles.screen}>
      <View style={styles.innerHeader}>
        <TouchableOpacity style={styles.iconButton} onPress={() => setSelectedVendor(null)}>
          <Ionicons name="arrow-back-outline" size={20} color="#111827" />
        </TouchableOpacity>
        <View style={{ flex: 1 }}>
          <Text style={styles.innerHeaderTitle}>{selectedVendor?.name}</Text>
          <Text style={styles.innerHeaderSubtitle}>
            {estimateEta(selectedVendor)} · {selectedVendor?.open_now ? 'Open' : 'Store details'}
          </Text>
        </View>
        <TouchableOpacity style={styles.iconButton} onPress={() => toggleFavorite(selectedVendor.id)}>
          <Ionicons
            name={favorites[selectedVendor.id] ? 'heart' : 'heart-outline'}
            size={18}
            color="#111827"
          />
        </TouchableOpacity>
      </View>

      <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={styles.pageContent}>
        <View style={styles.vendorHeroCard}>
          <Text style={styles.vendorHeroTitle}>{selectedVendor?.name}</Text>
          <Text style={styles.vendorHeroText}>
            {selectedVendor?.description || selectedVendor?.address || 'Quick grocery and essentials store'}
          </Text>
          <View style={styles.vendorBadgeRow}>
            <MetaBadge text={selectedVendor?.open_now ? 'Open now' : 'Store'} />
            <MetaBadge text={estimateEta(selectedVendor)} />
            <MetaBadge text={getDeliveryFee(selectedVendor)} />
            {selectedVendor?.distance_km != null ? (
              <MetaBadge text={`${selectedVendor.distance_km.toFixed(1)} km`} />
            ) : null}
          </View>
        </View>

        <View style={styles.searchBoxPlain}>
          <Ionicons name="search-outline" size={20} color="#6b7280" />
          <TextInput
            style={styles.searchInput}
            placeholder="Search inside store"
            placeholderTextColor="#9ca3af"
            value={productSearch}
            onChangeText={setProductSearch}
          />
        </View>

        {productsLoading ? <LoadingBlock label="Loading products..." /> : null}

        {!productsLoading && bestSellerProducts.length > 0 ? (
          <>
            <SectionHeader title="Bestsellers" subtitle="A high-conversion section for store detail pages." />
            {bestSellerProducts.map((product) => (
              <ProductCard
                key={`best-${product.id}`}
                product={product}
                featured
                qty={cart.items[product.id]?.qty || 0}
                onAdd={() => addToCart(product)}
                onRemove={() => updateQty(product, -1)}
              />
            ))}
          </>
        ) : null}

        <SectionHeader title="All items" subtitle="Fetched from /vendors/{id}/products." />
        {!productsLoading && products.length === 0 ? (
          <EmptyState
            title="No products yet"
            text="Add products from the seller side and this page will start looking complete."
          />
        ) : (
          products.map((product) => (
            <ProductCard
              key={product.id}
              product={product}
              qty={cart.items[product.id]?.qty || 0}
              onAdd={() => addToCart(product)}
              onRemove={() => updateQty(product, -1)}
            />
          ))
        )}
      </ScrollView>

      {cartCount > 0 && cart.vendorId === selectedVendor?.id ? (
        <TouchableOpacity style={styles.floatingCart} onPress={() => setShowCart(true)}>
          <View>
            <Text style={styles.floatingCartTitle}>View cart</Text>
            <Text style={styles.floatingCartSubtitle}>
              {cartCount} items · {money(cartSubtotal)}
            </Text>
          </View>
          <Ionicons name="arrow-forward" size={20} color="#ffffff" />
        </TouchableOpacity>
      ) : null}
    </View>
  );

  const renderCart = () => (
    <View style={styles.screen}>
      <View style={styles.innerHeader}>
        <TouchableOpacity style={styles.iconButton} onPress={() => setShowCart(false)}>
          <Ionicons name="arrow-back-outline" size={20} color="#111827" />
        </TouchableOpacity>
        <View style={{ flex: 1 }}>
          <Text style={styles.innerHeaderTitle}>Cart</Text>
          <Text style={styles.innerHeaderSubtitle}>{cartVendorName}</Text>
        </View>
      </View>

      <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={styles.pageContent}>
        {cartItems.length === 0 ? (
          <EmptyState title="Your cart is empty" text="Add products from a single store and they will appear here." />
        ) : (
          <>
            <View style={styles.panelCard}>
              {cartItems.map((item) => (
                <View key={item.id} style={styles.cartLine}>
                  <View style={{ flex: 1, paddingRight: 12 }}>
                    <Text style={styles.cartLineTitle}>{item.name}</Text>
                    <Text style={styles.cartLineMeta}>{money(item.price)} each</Text>
                  </View>
                  <QtyStepper
                    qty={item.qty}
                    onAdd={() => addToCart(item)}
                    onRemove={() => updateQty(item, -1)}
                  />
                </View>
              ))}
            </View>

            <View style={styles.panelCard}>
              <Text style={styles.panelTitle}>Bill details</Text>
              <SummaryRow label="Subtotal" value={money(cartSubtotal)} />
              <SummaryRow label="Delivery fee" value="Dynamic next" />
              <SummaryRow label="Platform fee" value="Later" />
              <View style={styles.summaryDivider} />
              <SummaryRow label="Items" value={`${cartCount}`} strong />
            </View>

            <View style={styles.noteCard}>
              <Text style={styles.noteTitle}>Guest flow only</Text>
              <Text style={styles.noteText}>
                As requested, sign-in is skipped. This cart is production-like in UI, but checkout should be wired back to auth, addresses and /orders next.
              </Text>
            </View>

            <TouchableOpacity style={styles.primaryButton} onPress={previewCheckout}>
              <Text style={styles.primaryButtonText}>Proceed to checkout</Text>
            </TouchableOpacity>
            <TouchableOpacity style={styles.secondaryButton} onPress={clearCart}>
              <Text style={styles.secondaryButtonText}>Clear cart</Text>
            </TouchableOpacity>
          </>
        )}
      </ScrollView>
    </View>
  );

  const renderContent = () => {
    if (selectedVendor) return renderVendorDetails();
    if (showCart) return renderCart();

    switch (activeTab) {
      case 'categories':
        return renderCategories();
      case 'reorder':
        return renderReorder();
      case 'account':
        return renderAccount();
      case 'home':
      default:
        return renderHome();
    }
  };

  const isHomeRoot = !selectedVendor && !showCart && activeTab === 'home';

  return (
    <SafeAreaView style={[styles.safeArea, isHomeRoot && styles.safeAreaHome]}>
      <StatusBar
        barStyle={isHomeRoot ? 'light-content' : 'dark-content'}
        backgroundColor={isHomeRoot ? '#0b7a5a' : '#f7f8fa'}
      />
      {renderContent()}

      {!selectedVendor && !showCart ? (
        <View style={styles.bottomTabBar}>
          <BottomTab
            icon="bag-handle-outline"
            label="Instamart"
            active={activeTab === 'home'}
            onPress={() => setActiveTab('home')}
          />
          <BottomTab
            icon="grid-outline"
            label="Categories"
            active={activeTab === 'categories'}
            onPress={() => setActiveTab('categories')}
          />
          <BottomTab
            icon="reload-outline"
            label="Reorder"
            active={activeTab === 'reorder'}
            onPress={() => setActiveTab('reorder')}
          />
          <BottomTab
            icon="person-outline"
            label="Account"
            active={activeTab === 'account'}
            onPress={() => setActiveTab('account')}
          />
        </View>
      ) : null}
    </SafeAreaView>
  );
}

function SectionHeader({ title, subtitle }) {
  return (
    <View style={styles.sectionHeader}>
      <Text style={styles.sectionTitle}>{title}</Text>
      {subtitle ? <Text style={styles.sectionSubtitle}>{subtitle}</Text> : null}
    </View>
  );
}

function LoadingBlock({ label }) {
  return (
    <View style={styles.loadingWrap}>
      <ActivityIndicator size="large" />
      <Text style={styles.loadingText}>{label}</Text>
    </View>
  );
}

function EmptyState({ title, text }) {
  return (
    <View style={styles.emptyCard}>
      <Text style={styles.emptyTitle}>{title}</Text>
      <Text style={styles.emptyText}>{text}</Text>
    </View>
  );
}

function MetaBadge({ text }) {
  return (
    <View style={styles.metaBadge}>
      <Text style={styles.metaBadgeText}>{text}</Text>
    </View>
  );
}

function QuickDealCard({ item }) {
  return (
    <View style={styles.quickDealCard}>
      <TouchableOpacity style={styles.quickDealSelect} activeOpacity={0.9}>
        <Text style={styles.quickDealSelectText}>Select</Text>
      </TouchableOpacity>
      <View style={styles.quickDealImageMock}>
        <Text style={styles.quickDealEmoji}>{item.emoji}</Text>
      </View>
      <Text style={styles.quickDealTitle} numberOfLines={1}>
        {item.name}
      </Text>
      <Text style={styles.quickDealSubtitle}>{item.subtitle}</Text>
      <Text style={styles.quickDealPrice}>{item.price}</Text>
    </View>
  );
}

function StoreCard({ vendor, onOpen, onToggleFavorite, favorite, tone }) {
  return (
    <TouchableOpacity activeOpacity={0.94} style={styles.storeCard} onPress={onOpen}>
      <View style={[styles.storeHero, { backgroundColor: tone }]}>
        <View style={styles.storeHeroBadgeRow}>
          <View style={styles.storePromoBadge}>
            <Text style={styles.storePromoBadgeText}>{estimateEta(vendor)}</Text>
          </View>
          <TouchableOpacity onPress={onToggleFavorite} style={styles.favoriteButton}>
            <Ionicons name={favorite ? 'heart' : 'heart-outline'} size={18} color="#111827" />
          </TouchableOpacity>
        </View>
        <View style={styles.storeHeroCircle}>
          <Text style={styles.storeHeroCircleText}>{initials(vendor.name)}</Text>
        </View>
      </View>

      <View style={styles.storeContent}>
        <View style={styles.storeTitleRow}>
          <Text style={styles.storeName} numberOfLines={1}>
            {vendor.name}
          </Text>
          <Text style={styles.storeRating}>4.4</Text>
        </View>
        <Text style={styles.storeDescription} numberOfLines={2}>
          {vendor.description || vendor.address || 'Daily essentials and groceries'}
        </Text>
        <View style={styles.vendorBadgeRow}>
          <MetaBadge text={vendor.open_now ? 'Open now' : 'Store'} />
          <MetaBadge text={estimateEta(vendor)} />
          <MetaBadge text={getDeliveryFee(vendor)} />
          {vendor.distance_km != null ? <MetaBadge text={`${vendor.distance_km.toFixed(1)} km`} /> : null}
        </View>
        <View style={styles.storeCardBottom}>
          <Text style={styles.storeAddress} numberOfLines={1}>
            {vendor.address || 'Tap to browse the store'}
          </Text>
          <View style={styles.browsePill}>
            <Text style={styles.browsePillText}>Browse</Text>
          </View>
        </View>
      </View>
    </TouchableOpacity>
  );
}

function ProductCard({ product, qty, onAdd, onRemove, featured = false }) {
  return (
    <View style={styles.productCard}>
      <View style={{ flex: 1, paddingRight: 12 }}>
        {featured ? <Text style={styles.featuredLabel}>BESTSELLER</Text> : null}
        <Text style={styles.productTitle}>{product.name}</Text>
        <Text style={styles.productDescription}>{product.description || 'Store product'}</Text>
        <Text style={styles.productPrice}>{money(product.price)}</Text>
      </View>
      <View style={styles.productRightBlock}>
        <View style={styles.productImageMock}>
          <Ionicons name="cube-outline" size={24} color="#6b7280" />
        </View>
        {qty > 0 ? (
          <QtyStepper qty={qty} onAdd={onAdd} onRemove={onRemove} />
        ) : (
          <TouchableOpacity style={styles.addButton} onPress={onAdd}>
            <Text style={styles.addButtonText}>ADD</Text>
          </TouchableOpacity>
        )}
      </View>
    </View>
  );
}

function QtyStepper({ qty, onAdd, onRemove }) {
  return (
    <View style={styles.qtyStepper}>
      <TouchableOpacity style={styles.qtyAction} onPress={onRemove}>
        <Ionicons name="remove" size={16} color="#111827" />
      </TouchableOpacity>
      <Text style={styles.qtyText}>{qty}</Text>
      <TouchableOpacity style={styles.qtyAction} onPress={onAdd}>
        <Ionicons name="add" size={16} color="#111827" />
      </TouchableOpacity>
    </View>
  );
}

function SummaryRow({ label, value, strong = false }) {
  return (
    <View style={styles.summaryRow}>
      <Text style={[styles.summaryLabel, strong && styles.summaryLabelStrong]}>{label}</Text>
      <Text style={[styles.summaryValue, strong && styles.summaryValueStrong]}>{value}</Text>
    </View>
  );
}

function BottomTab({ icon, label, active, onPress }) {
  return (
    <TouchableOpacity style={styles.bottomTab} onPress={onPress} activeOpacity={0.9}>
      <Ionicons name={icon} size={20} color={active ? '#111827' : '#6b7280'} />
      <Text style={[styles.bottomTabLabel, active && styles.bottomTabLabelActive]}>{label}</Text>
    </TouchableOpacity>
  );
}

const styles = StyleSheet.create({
  safeArea: {
    flex: 1,
    backgroundColor: '#f7f8fa',
  },
  safeAreaHome: {
    backgroundColor: '#0b7a5a',
  },
  screen: {
    flex: 1,
    backgroundColor: '#f7f8fa',
  },
  homeScreen: {
    flex: 1,
    backgroundColor: '#0b7a5a',
  },
  homeScrollContent: {
    paddingBottom: 110,
  },
  pageContent: {
    padding: 16,
    paddingBottom: 120,
  },
  heroShell: {
    backgroundColor: '#0b7a5a',
    paddingHorizontal: 16,
    paddingTop: 8,
    paddingBottom: 24,
  },
  heroTopRow: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  heroEta: {
    fontSize: 28,
    fontWeight: '800',
    color: '#ffffff',
  },
  addressRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginTop: 4,
    alignSelf: 'flex-start',
  },
  addressText: {
    maxWidth: 260,
    color: '#d1fae5',
    fontSize: 15,
    fontWeight: '600',
    marginRight: 4,
  },
  profileDot: {
    width: 46,
    height: 46,
    borderRadius: 23,
    backgroundColor: 'rgba(255,255,255,0.22)',
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.18)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  serviceRail: {
    paddingTop: 18,
  },
  serviceCard: {
    width: 112,
    backgroundColor: 'rgba(0,0,0,0.14)',
    borderRadius: 24,
    paddingVertical: 14,
    paddingHorizontal: 12,
    marginRight: 12,
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.08)',
  },
  serviceCardActive: {
    backgroundColor: '#ffffff',
    borderColor: '#ffffff',
  },
  serviceIconWrap: {
    width: 42,
    height: 42,
    borderRadius: 16,
    backgroundColor: 'rgba(255,255,255,0.16)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  serviceIconWrapActive: {
    backgroundColor: '#dcfce7',
  },
  serviceLabel: {
    marginTop: 12,
    fontSize: 16,
    fontWeight: '800',
    color: '#ffffff',
  },
  serviceLabelActive: {
    color: '#111827',
  },
  serviceSubLabel: {
    marginTop: 4,
    fontSize: 12,
    color: '#d1fae5',
  },
  serviceSubLabelActive: {
    color: '#6b7280',
  },
  searchRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginTop: 18,
  },
  searchBoxHero: {
    flex: 1,
    minHeight: 58,
    borderRadius: 18,
    backgroundColor: '#ffffff',
    paddingHorizontal: 14,
    flexDirection: 'row',
    alignItems: 'center',
  },
  searchBoxPlain: {
    minHeight: 58,
    borderRadius: 18,
    backgroundColor: '#ffffff',
    borderWidth: 1,
    borderColor: '#eceef2',
    paddingHorizontal: 14,
    flexDirection: 'row',
    alignItems: 'center',
  },
  searchInput: {
    flex: 1,
    marginHorizontal: 10,
    fontSize: 16,
    color: '#111827',
    paddingVertical: 14,
  },
  bookmarkButton: {
    width: 58,
    height: 58,
    marginLeft: 12,
    borderRadius: 18,
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.18)',
    backgroundColor: 'rgba(0,0,0,0.12)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  shortcutRail: {
    paddingTop: 14,
    paddingBottom: 6,
  },
  shortcutItem: {
    alignItems: 'center',
    marginRight: 18,
  },
  shortcutIconWrap: {
    width: 42,
    height: 42,
    borderRadius: 21,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: 'rgba(255,255,255,0.08)',
  },
  shortcutIconWrapActive: {
    backgroundColor: 'rgba(255,255,255,0.18)',
  },
  shortcutLabel: {
    marginTop: 8,
    color: '#d1fae5',
    fontSize: 12,
    fontWeight: '600',
  },
  shortcutLabelActive: {
    color: '#ffffff',
  },
  shortcutUnderline: {
    marginTop: 8,
    width: 20,
    height: 3,
    borderRadius: 999,
    backgroundColor: '#ffffff',
  },
  shortcutUnderlineSpacer: {
    marginTop: 8,
    width: 20,
    height: 3,
    opacity: 0,
  },
  celebrationWrap: {
    alignItems: 'center',
    marginTop: 8,
  },
  celebrationEyebrow: {
    color: '#d1fae5',
    fontSize: 14,
    fontWeight: '800',
    letterSpacing: 1.6,
  },
  celebrationTitle: {
    marginTop: 4,
    color: '#f9a8d4',
    fontSize: 34,
    fontWeight: '900',
    letterSpacing: 1,
  },
  festivalRail: {
    paddingTop: 16,
  },
  festivalCard: {
    width: 130,
    borderRadius: 22,
    paddingHorizontal: 14,
    paddingVertical: 16,
    marginRight: 12,
    minHeight: 150,
    justifyContent: 'space-between',
  },
  festivalTitle: {
    color: '#111827',
    fontSize: 18,
    fontWeight: '800',
    lineHeight: 23,
  },
  festivalEmoji: {
    fontSize: 38,
    alignSelf: 'flex-end',
  },
  bodySurface: {
    marginTop: -8,
    backgroundColor: '#f7f8fa',
    borderTopLeftRadius: 28,
    borderTopRightRadius: 28,
    paddingHorizontal: 16,
    paddingTop: 18,
    paddingBottom: 8,
  },
  everydayBanner: {
    backgroundColor: '#ffffff',
    borderRadius: 22,
    borderWidth: 1,
    borderColor: '#eceef2',
    padding: 16,
    flexDirection: 'row',
    alignItems: 'center',
  },
  everydayBadge: {
    width: 58,
    height: 58,
    borderRadius: 18,
    backgroundColor: '#4338ca',
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 14,
  },
  everydayPrice: {
    color: '#fde047',
    fontSize: 24,
    fontWeight: '900',
  },
  everydayTitle: {
    fontSize: 28,
    fontWeight: '900',
    color: '#111827',
  },
  everydayText: {
    marginTop: 4,
    color: '#6b7280',
    lineHeight: 19,
  },
  dealGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    justifyContent: 'space-between',
    marginTop: 14,
  },
  quickDealCard: {
    width: '48.5%',
    backgroundColor: '#ffffff',
    borderRadius: 22,
    borderWidth: 1,
    borderColor: '#eceef2',
    padding: 12,
    marginBottom: 12,
  },
  quickDealSelect: {
    alignSelf: 'flex-end',
    minWidth: 74,
    borderRadius: 14,
    borderWidth: 1,
    borderColor: '#93c5fd',
    paddingVertical: 8,
    alignItems: 'center',
    justifyContent: 'center',
  },
  quickDealSelectText: {
    color: '#2563eb',
    fontWeight: '800',
  },
  quickDealImageMock: {
    height: 90,
    borderRadius: 18,
    backgroundColor: '#f3f4f6',
    alignItems: 'center',
    justifyContent: 'center',
    marginTop: 10,
  },
  quickDealEmoji: {
    fontSize: 42,
  },
  quickDealTitle: {
    marginTop: 12,
    fontSize: 14,
    fontWeight: '800',
    color: '#111827',
  },
  quickDealSubtitle: {
    marginTop: 4,
    fontSize: 12,
    color: '#6b7280',
  },
  quickDealPrice: {
    marginTop: 8,
    fontSize: 16,
    fontWeight: '900',
    color: '#111827',
  },
  filterRail: {
    paddingTop: 4,
    paddingBottom: 2,
  },
  filterChip: {
    backgroundColor: '#ffffff',
    borderWidth: 1,
    borderColor: '#e5e7eb',
    borderRadius: 999,
    paddingHorizontal: 14,
    paddingVertical: 10,
    marginRight: 10,
  },
  filterChipActive: {
    borderColor: '#111827',
    backgroundColor: '#111827',
  },
  filterChipText: {
    color: '#4b5563',
    fontWeight: '700',
  },
  filterChipTextActive: {
    color: '#ffffff',
  },
  sectionHeader: {
    marginTop: 24,
    marginBottom: 12,
  },
  sectionTitle: {
    fontSize: 20,
    fontWeight: '800',
    color: '#111827',
  },
  sectionSubtitle: {
    marginTop: 4,
    fontSize: 13,
    lineHeight: 19,
    color: '#6b7280',
  },
  categoryGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    justifyContent: 'space-between',
  },
  categoryTile: {
    width: '23%',
    backgroundColor: '#ffffff',
    borderRadius: 20,
    borderWidth: 1,
    borderColor: '#eceef2',
    paddingVertical: 16,
    paddingHorizontal: 8,
    alignItems: 'center',
    marginBottom: 10,
  },
  categoryEmoji: {
    fontSize: 24,
  },
  categoryTitle: {
    marginTop: 8,
    fontSize: 12,
    fontWeight: '700',
    color: '#111827',
    textAlign: 'center',
  },
  categoryTileLarge: {
    width: '48%',
    backgroundColor: '#ffffff',
    borderRadius: 24,
    borderWidth: 1,
    borderColor: '#eceef2',
    padding: 16,
    marginBottom: 12,
  },
  categoryEmojiLarge: {
    fontSize: 32,
  },
  categoryTitleLarge: {
    marginTop: 10,
    fontSize: 16,
    fontWeight: '800',
    color: '#111827',
  },
  tileMeta: {
    marginTop: 4,
    color: '#6b7280',
    fontSize: 13,
  },
  loadingWrap: {
    paddingVertical: 40,
    alignItems: 'center',
    justifyContent: 'center',
  },
  loadingText: {
    marginTop: 12,
    color: '#6b7280',
    fontWeight: '600',
  },
  emptyCard: {
    backgroundColor: '#ffffff',
    borderRadius: 24,
    borderWidth: 1,
    borderColor: '#eceef2',
    padding: 20,
  },
  emptyTitle: {
    fontSize: 18,
    fontWeight: '800',
    color: '#111827',
  },
  emptyText: {
    marginTop: 8,
    color: '#6b7280',
    lineHeight: 20,
  },
  storeCard: {
    backgroundColor: '#ffffff',
    borderRadius: 24,
    borderWidth: 1,
    borderColor: '#eceef2',
    marginBottom: 14,
    overflow: 'hidden',
  },
  storeHero: {
    height: 132,
    padding: 14,
    justifyContent: 'space-between',
  },
  storeHeroBadgeRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  storePromoBadge: {
    backgroundColor: 'rgba(255,255,255,0.92)',
    paddingHorizontal: 10,
    paddingVertical: 6,
    borderRadius: 999,
  },
  storePromoBadgeText: {
    color: '#111827',
    fontSize: 12,
    fontWeight: '800',
  },
  favoriteButton: {
    width: 34,
    height: 34,
    borderRadius: 17,
    backgroundColor: 'rgba(255,255,255,0.92)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  storeHeroCircle: {
    width: 64,
    height: 64,
    borderRadius: 22,
    backgroundColor: 'rgba(17,24,39,0.88)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  storeHeroCircleText: {
    color: '#ffffff',
    fontSize: 20,
    fontWeight: '900',
  },
  storeContent: {
    padding: 16,
  },
  storeTitleRow: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  storeName: {
    flex: 1,
    fontSize: 18,
    fontWeight: '800',
    color: '#111827',
  },
  storeRating: {
    color: '#111827',
    fontSize: 15,
    fontWeight: '800',
  },
  storeDescription: {
    marginTop: 6,
    fontSize: 13,
    lineHeight: 18,
    color: '#6b7280',
  },
  vendorBadgeRow: {
    marginTop: 10,
    flexDirection: 'row',
    flexWrap: 'wrap',
  },
  metaBadge: {
    backgroundColor: '#f3f4f6',
    borderRadius: 999,
    paddingHorizontal: 10,
    paddingVertical: 6,
    marginRight: 8,
    marginBottom: 8,
  },
  metaBadgeText: {
    fontSize: 12,
    fontWeight: '700',
    color: '#374151',
  },
  storeCardBottom: {
    marginTop: 4,
    flexDirection: 'row',
    alignItems: 'center',
  },
  storeAddress: {
    flex: 1,
    color: '#4b5563',
    fontSize: 13,
    marginRight: 10,
  },
  browsePill: {
    backgroundColor: '#111827',
    paddingHorizontal: 16,
    paddingVertical: 10,
    borderRadius: 14,
  },
  browsePillText: {
    color: '#ffffff',
    fontWeight: '800',
  },
  panelCard: {
    backgroundColor: '#ffffff',
    borderRadius: 24,
    borderWidth: 1,
    borderColor: '#eceef2',
    padding: 16,
    marginBottom: 12,
  },
  panelTitle: {
    fontSize: 17,
    fontWeight: '800',
    color: '#111827',
  },
  panelText: {
    marginTop: 10,
    fontSize: 15,
    fontWeight: '700',
    color: '#111827',
  },
  panelSubText: {
    marginTop: 4,
    color: '#6b7280',
  },
  noteCard: {
    backgroundColor: '#eef2ff',
    borderRadius: 20,
    padding: 16,
    marginTop: 4,
    marginBottom: 12,
  },
  noteTitle: {
    fontSize: 15,
    fontWeight: '800',
    color: '#111827',
  },
  noteText: {
    marginTop: 6,
    color: '#374151',
    lineHeight: 20,
  },
  accountCard: {
    backgroundColor: '#ffffff',
    borderRadius: 24,
    borderWidth: 1,
    borderColor: '#eceef2',
    padding: 20,
    alignItems: 'center',
    marginBottom: 14,
  },
  accountAvatar: {
    width: 64,
    height: 64,
    borderRadius: 32,
    backgroundColor: '#111827',
    alignItems: 'center',
    justifyContent: 'center',
  },
  accountAvatarText: {
    color: '#ffffff',
    fontSize: 20,
    fontWeight: '800',
  },
  accountTitle: {
    marginTop: 12,
    fontSize: 18,
    fontWeight: '800',
    color: '#111827',
  },
  accountText: {
    marginTop: 8,
    textAlign: 'center',
    lineHeight: 20,
    color: '#6b7280',
  },
  accountRow: {
    backgroundColor: '#ffffff',
    borderRadius: 20,
    borderWidth: 1,
    borderColor: '#eceef2',
    padding: 16,
    marginBottom: 10,
    flexDirection: 'row',
    alignItems: 'center',
  },
  accountRowIcon: {
    width: 38,
    height: 38,
    borderRadius: 19,
    backgroundColor: '#f3f4f6',
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 12,
  },
  accountRowLabel: {
    fontSize: 15,
    fontWeight: '700',
    color: '#111827',
  },
  accountRowValue: {
    marginTop: 4,
    color: '#6b7280',
    fontSize: 13,
  },
  innerHeader: {
    backgroundColor: '#ffffff',
    borderBottomWidth: 1,
    borderBottomColor: '#eceef2',
    paddingHorizontal: 16,
    paddingVertical: 12,
    flexDirection: 'row',
    alignItems: 'center',
  },
  iconButton: {
    width: 42,
    height: 42,
    borderRadius: 14,
    backgroundColor: '#f3f4f6',
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 12,
  },
  innerHeaderTitle: {
    fontSize: 18,
    fontWeight: '800',
    color: '#111827',
  },
  innerHeaderSubtitle: {
    marginTop: 4,
    color: '#6b7280',
    fontSize: 13,
  },
  vendorHeroCard: {
    backgroundColor: '#ffffff',
    borderRadius: 24,
    borderWidth: 1,
    borderColor: '#eceef2',
    padding: 18,
    marginBottom: 16,
  },
  vendorHeroTitle: {
    fontSize: 22,
    fontWeight: '800',
    color: '#111827',
  },
  vendorHeroText: {
    marginTop: 6,
    fontSize: 14,
    color: '#6b7280',
    lineHeight: 20,
  },
  productCard: {
    backgroundColor: '#ffffff',
    borderRadius: 24,
    borderWidth: 1,
    borderColor: '#eceef2',
    padding: 16,
    marginBottom: 12,
    flexDirection: 'row',
  },
  featuredLabel: {
    fontSize: 11,
    fontWeight: '800',
    color: '#16a34a',
    letterSpacing: 0.7,
    marginBottom: 8,
  },
  productTitle: {
    fontSize: 16,
    fontWeight: '800',
    color: '#111827',
  },
  productDescription: {
    marginTop: 6,
    color: '#6b7280',
    lineHeight: 18,
    fontSize: 13,
  },
  productPrice: {
    marginTop: 10,
    fontSize: 17,
    fontWeight: '800',
    color: '#111827',
  },
  productRightBlock: {
    width: 104,
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  productImageMock: {
    width: 92,
    height: 92,
    borderRadius: 18,
    backgroundColor: '#f3f4f6',
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 12,
  },
  addButton: {
    minWidth: 92,
    backgroundColor: '#111827',
    borderRadius: 14,
    paddingVertical: 10,
    alignItems: 'center',
    justifyContent: 'center',
  },
  addButtonText: {
    color: '#ffffff',
    fontWeight: '800',
    letterSpacing: 0.5,
  },
  qtyStepper: {
    minWidth: 92,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    backgroundColor: '#f3f4f6',
    borderRadius: 14,
    padding: 4,
  },
  qtyAction: {
    width: 28,
    height: 28,
    borderRadius: 10,
    backgroundColor: '#ffffff',
    alignItems: 'center',
    justifyContent: 'center',
  },
  qtyText: {
    minWidth: 24,
    textAlign: 'center',
    fontWeight: '800',
    color: '#111827',
  },
  floatingCart: {
    position: 'absolute',
    left: 16,
    right: 16,
    bottom: 16,
    backgroundColor: '#111827',
    borderRadius: 20,
    paddingHorizontal: 18,
    paddingVertical: 16,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  floatingCartTitle: {
    color: '#ffffff',
    fontSize: 16,
    fontWeight: '800',
  },
  floatingCartSubtitle: {
    marginTop: 4,
    color: '#d1d5db',
  },
  cartLine: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: 12,
    borderBottomWidth: StyleSheet.hairlineWidth,
    borderBottomColor: '#e5e7eb',
  },
  cartLineTitle: {
    fontSize: 15,
    fontWeight: '700',
    color: '#111827',
  },
  cartLineMeta: {
    marginTop: 4,
    color: '#6b7280',
  },
  summaryRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingVertical: 8,
  },
  summaryLabel: {
    color: '#4b5563',
  },
  summaryLabelStrong: {
    color: '#111827',
    fontWeight: '800',
  },
  summaryValue: {
    color: '#111827',
    fontWeight: '600',
  },
  summaryValueStrong: {
    fontWeight: '800',
  },
  summaryDivider: {
    height: 1,
    backgroundColor: '#e5e7eb',
    marginVertical: 8,
  },
  primaryButton: {
    backgroundColor: '#111827',
    borderRadius: 18,
    paddingVertical: 16,
    alignItems: 'center',
    justifyContent: 'center',
    marginTop: 6,
  },
  primaryButtonText: {
    color: '#ffffff',
    fontSize: 15,
    fontWeight: '800',
  },
  secondaryButton: {
    backgroundColor: '#ffffff',
    borderRadius: 18,
    paddingVertical: 16,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    borderColor: '#d1d5db',
    marginTop: 12,
  },
  secondaryButtonText: {
    color: '#111827',
    fontSize: 15,
    fontWeight: '700',
  },
  bottomTabBar: {
    backgroundColor: '#ffffff',
    borderTopWidth: 1,
    borderTopColor: '#eceef2',
    flexDirection: 'row',
    paddingTop: 8,
    paddingBottom: 12,
  },
  bottomTab: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  bottomTabLabel: {
    marginTop: 4,
    fontSize: 12,
    color: '#6b7280',
  },
  bottomTabLabelActive: {
    color: '#111827',
    fontWeight: '800',
  },
});