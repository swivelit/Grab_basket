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
  { key: 'food', emoji: '🍔', label: 'Food', subtitle: 'Restaurants' },
  { key: 'instamart', emoji: '🛒', label: 'Instamart', subtitle: 'Groceries' },
  { key: 'dineout', emoji: '🍽️', label: 'Dineout', subtitle: 'Dining deals' },
  { key: 'scenes', emoji: '🎉', label: 'Scenes', subtitle: 'Events' },
];

const HOME_FILTERS = ['All', 'Open now', 'Low to high', 'High to low', 'A-Z'];

const OFFER_STRIPS = [
  { title: 'Season of celebration', subtitle: 'Festival baskets, gifting and quick grocery packs' },
  { title: '₹9 everyday store', subtitle: 'Small add-ons and basket fillers at ultra-low prices' },
  { title: 'Free delivery picks', subtitle: 'Build your MVP around these merchandising slots' },
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
  { icon: 'location-outline', label: 'Saved addresses', value: 'Hook with /addresses later' },
  { icon: 'card-outline', label: 'Payments', value: 'Wallet / UPI / cards later' },
  { icon: 'ticket-outline', label: 'Coupons', value: 'Promo engine later' },
  { icon: 'chatbubble-ellipses-outline', label: 'Support', value: 'Help centre later' },
];

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
        // ignore storage boot errors for first-pass MVP
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
        'Swiggy-style flow usually keeps one active store in the cart. Replace the current basket?',
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
      'Frontend guest flow is ready. Next Swiggy-level step is wiring address selection, payments, and customer auth back into /orders.'
    );
  };

  const renderHome = () => (
    <ScrollView
      contentContainerStyle={styles.pageContent}
      refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => loadVendors({ pullToRefresh: true })} />}>
      <View style={styles.heroTopRow}>
        <View style={{ flex: 1 }}>
          <Text style={styles.heroEta}>23 mins</Text>
          <View style={styles.addressRow}>
            <Ionicons name="location-outline" size={16} color="#374151" />
            <Text style={styles.addressText}>To Valliachans Place · 12b, Great Orchard...</Text>
            <Ionicons name="chevron-down" size={14} color="#374151" />
          </View>
        </View>
        <View style={styles.profileDot}>
          <Ionicons name="person-outline" size={20} color="#111827" />
        </View>
      </View>

      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.horizontalRail}>
        {TOP_SERVICES.map((item) => (
          <TouchableOpacity
            key={item.key}
            activeOpacity={0.9}
            style={[styles.serviceCard, activeService === item.key && styles.serviceCardActive]}
            onPress={() => setActiveService(item.key)}>
            <Text style={styles.serviceEmoji}>{item.emoji}</Text>
            <Text style={styles.serviceLabel}>{item.label}</Text>
            <Text style={styles.serviceSubLabel}>{item.subtitle}</Text>
          </TouchableOpacity>
        ))}
      </ScrollView>

      <View style={styles.searchBox}>
        <Ionicons name="search-outline" size={20} color="#6b7280" />
        <TextInput
          style={styles.searchInput}
          placeholder="Search for stores, groceries and quick items"
          placeholderTextColor="#9ca3af"
          value={homeSearch}
          onChangeText={setHomeSearch}
        />
        <Ionicons name="list-outline" size={20} color="#6b7280" />
      </View>

      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.horizontalRailCompact}>
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
        title="Season of celebration"
        subtitle="Structure is now much closer to the screenshot: service rail, search, chips, banners and store feed."
      />

      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.horizontalRail}>
        {OFFER_STRIPS.map((item) => (
          <View key={item.title} style={styles.offerCard}>
            <Text style={styles.offerEyebrow}>LIVE</Text>
            <Text style={styles.offerTitle}>{item.title}</Text>
            <Text style={styles.offerSubtitle}>{item.subtitle}</Text>
          </View>
        ))}
      </ScrollView>

      <View style={styles.bannerCard}>
        <View style={{ flex: 1 }}>
          <Text style={styles.bannerHeadline}>₹9 everyday</Text>
          <Text style={styles.bannerCopy}>A Swiggy-like home needs promo blocks, merchandising slots and quick CTA modules.</Text>
        </View>
        <View style={styles.bannerIconWrap}>
          <Ionicons name="flash-outline" size={22} color="#111827" />
        </View>
      </View>

      <SectionHeader title="Popular categories" subtitle="These are visual modules for the home feed and categories tab." />
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
        <EmptyState title="No stores found" text="Create vendor and product data in the backend and this feed will populate automatically." />
      ) : (
        featuredVendors.map((vendor) => (
          <StoreCard
            key={vendor.id}
            vendor={vendor}
            favorite={!!favorites[vendor.id]}
            onOpen={() => openVendor(vendor)}
            onToggleFavorite={() => toggleFavorite(vendor.id)}
          />
        ))
      )}
    </ScrollView>
  );

  const renderCategories = () => (
    <ScrollView contentContainerStyle={styles.pageContent}>
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
        favoriteVendors.map((vendor) => (
          <StoreCard
            key={`fav-${vendor.id}`}
            vendor={vendor}
            favorite={!!favorites[vendor.id]}
            onOpen={() => openVendor(vendor)}
            onToggleFavorite={() => toggleFavorite(vendor.id)}
          />
        ))
      )}
    </ScrollView>
  );

  const renderReorder = () => (
    <ScrollView contentContainerStyle={styles.pageContent}>
      <SectionHeader title="Reorder" subtitle="Since we are skipping sign-in, this is a guest-mode placeholder." />
      {cartItems.length === 0 ? (
        <EmptyState
          title="No recent guest basket"
          text="Once you add items, this tab becomes a useful reorder shortcut. For real reorders, connect it to /orders/me after auth is back."
        />
      ) : (
        <View style={styles.panelCard}>
          <Text style={styles.panelTitle}>Current basket snapshot</Text>
          <Text style={styles.panelText}>{cartVendorName}</Text>
          <Text style={styles.panelSubText}>{cartCount} items · {money(cartSubtotal)}</Text>
          <TouchableOpacity style={styles.primaryButton} onPress={() => setShowCart(true)}>
            <Text style={styles.primaryButtonText}>Open cart</Text>
          </TouchableOpacity>
        </View>
      )}
    </ScrollView>
  );

  const renderOffers = () => (
    <ScrollView contentContainerStyle={styles.pageContent}>
      <SectionHeader title="Offers" subtitle="These are the sections a Swiggy-like app needs even before real offer logic is added." />
      {OFFER_STRIPS.map((item) => (
        <View key={item.title} style={styles.offerListCard}>
          <View style={styles.offerListBadge}>
            <Ionicons name="pricetags-outline" size={18} color="#111827" />
          </View>
          <View style={{ flex: 1 }}>
            <Text style={styles.offerListTitle}>{item.title}</Text>
            <Text style={styles.offerListText}>{item.subtitle}</Text>
          </View>
        </View>
      ))}
      <View style={styles.noteCard}>
        <Text style={styles.noteTitle}>Still missing for true Swiggy standards</Text>
        <Text style={styles.noteText}>Dynamic coupons, offer eligibility, payments, live tracking, ratings, store images, search suggestions and address-aware ETA.</Text>
      </View>
    </ScrollView>
  );

  const renderAccount = () => (
    <ScrollView contentContainerStyle={styles.pageContent}>
      <SectionHeader title="Account" subtitle="Sign-in is intentionally skipped for now, so this tab stays informational." />
      <View style={styles.accountCard}>
        <View style={styles.accountAvatar}>
          <Text style={styles.accountAvatarText}>{initials('Grab Basket')}</Text>
        </View>
        <Text style={styles.accountTitle}>Guest mode active</Text>
        <Text style={styles.accountText}>You can browse stores, search products, manage favorites and build a basket without auth.</Text>
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
        <Text style={styles.noteTitle}>What is now fixed</Text>
        <Text style={styles.noteText}>The app can be made to open your custom App.js instead of the Expo router demo screen. That was the biggest issue in the latest zip.</Text>
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
          <Text style={styles.innerHeaderSubtitle}>{estimateEta(selectedVendor)} · {selectedVendor?.open_now ? 'Open' : 'Store details'}</Text>
        </View>
        <TouchableOpacity style={styles.iconButton} onPress={() => toggleFavorite(selectedVendor.id)}>
          <Ionicons name={favorites[selectedVendor.id] ? 'heart' : 'heart-outline'} size={18} color="#111827" />
        </TouchableOpacity>
      </View>

      <ScrollView contentContainerStyle={styles.pageContent}>
        <View style={styles.vendorHeroCard}>
          <Text style={styles.vendorHeroTitle}>{selectedVendor?.name}</Text>
          <Text style={styles.vendorHeroText}>{selectedVendor?.description || selectedVendor?.address || 'Quick grocery and essentials store'}</Text>
          <View style={styles.vendorBadgeRow}>
            <MetaBadge text={selectedVendor?.open_now ? 'Open now' : 'Store'} />
            <MetaBadge text={estimateEta(selectedVendor)} />
            {selectedVendor?.distance_km != null ? <MetaBadge text={`${selectedVendor.distance_km.toFixed(1)} km`} /> : null}
            {selectedVendor?.delivery_radius_km != null ? <MetaBadge text={`Radius ${selectedVendor.delivery_radius_km} km`} /> : null}
          </View>
        </View>

        <View style={styles.searchBox}>
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
          <EmptyState title="No products yet" text="Add products from the seller side and this page will start looking complete." />
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
            <Text style={styles.floatingCartSubtitle}>{cartCount} items · {money(cartSubtotal)}</Text>
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

      <ScrollView contentContainerStyle={styles.pageContent}>
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
                  <QtyStepper qty={item.qty} onAdd={() => addToCart(item)} onRemove={() => updateQty(item, -1)} />
                </View>
              ))}
            </View>

            <View style={styles.panelCard}>
              <Text style={styles.panelTitle}>Bill details</Text>
              <SummaryRow label="Subtotal" value={money(cartSubtotal)} />
              <SummaryRow label="Delivery fee" value="Later from backend" />
              <SummaryRow label="Platform fee" value="Later" />
              <View style={styles.summaryDivider} />
              <SummaryRow label="Items" value={`${cartCount}`} strong />
            </View>

            <View style={styles.noteCard}>
              <Text style={styles.noteTitle}>Guest flow only</Text>
              <Text style={styles.noteText}>As requested, sign-in is skipped. This cart is production-like in UI, but checkout should be wired back to auth, addresses and /orders next.</Text>
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
      case 'offers':
        return renderOffers();
      case 'account':
        return renderAccount();
      case 'home':
      default:
        return renderHome();
    }
  };

  return (
    <SafeAreaView style={styles.safeArea}>
      <StatusBar barStyle="dark-content" backgroundColor="#f7f8fa" />
      {renderContent()}

      {!selectedVendor && !showCart ? (
        <View style={styles.bottomTabBar}>
          <BottomTab icon="bag-handle-outline" label="Instamart" active={activeTab === 'home'} onPress={() => setActiveTab('home')} />
          <BottomTab icon="grid-outline" label="Categories" active={activeTab === 'categories'} onPress={() => setActiveTab('categories')} />
          <BottomTab icon="reload-outline" label="Reorder" active={activeTab === 'reorder'} onPress={() => setActiveTab('reorder')} />
          <BottomTab icon="pricetags-outline" label="Offers" active={activeTab === 'offers'} onPress={() => setActiveTab('offers')} />
          <BottomTab icon="person-outline" label="Account" active={activeTab === 'account'} onPress={() => setActiveTab('account')} />
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

function StoreCard({ vendor, onOpen, onToggleFavorite, favorite }) {
  return (
    <TouchableOpacity activeOpacity={0.92} style={styles.storeCard} onPress={onOpen}>
      <View style={styles.storeCardTop}>
        <View style={styles.storeAvatar}>
          <Text style={styles.storeAvatarText}>{initials(vendor.name)}</Text>
        </View>
        <View style={{ flex: 1 }}>
          <View style={styles.storeTitleRow}>
            <Text style={styles.storeName}>{vendor.name}</Text>
            <TouchableOpacity onPress={onToggleFavorite} style={styles.favoriteButton}>
              <Ionicons name={favorite ? 'heart' : 'heart-outline'} size={18} color="#111827" />
            </TouchableOpacity>
          </View>
          <Text style={styles.storeDescription} numberOfLines={2}>
            {vendor.description || vendor.address || 'Daily essentials and groceries'}
          </Text>
          <View style={styles.vendorBadgeRow}>
            <MetaBadge text={vendor.open_now ? 'Open now' : 'Store'} />
            <MetaBadge text={estimateEta(vendor)} />
            {vendor.distance_km != null ? <MetaBadge text={`${vendor.distance_km.toFixed(1)} km`} /> : null}
            {vendor.delivery_radius_km != null ? <MetaBadge text={`Radius ${vendor.delivery_radius_km} km`} /> : null}
          </View>
        </View>
      </View>
      <View style={styles.storeCardBottom}>
        <Text style={styles.storeAddress} numberOfLines={1}>{vendor.address || 'Tap to browse the store'}</Text>
        <View style={styles.browsePill}>
          <Text style={styles.browsePillText}>Browse</Text>
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
    <TouchableOpacity style={styles.bottomTab} onPress={onPress}>
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
  screen: {
    flex: 1,
    backgroundColor: '#f7f8fa',
  },
  pageContent: {
    padding: 16,
    paddingBottom: 120,
  },
  heroTopRow: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  heroEta: {
    fontSize: 35,
    fontWeight: '800',
    color: '#111827',
  },
  addressRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginTop: 4,
  },
  addressText: {
    flex: 1,
    marginHorizontal: 6,
    color: '#4b5563',
    fontSize: 14,
    fontWeight: '600',
  },
  profileDot: {
    width: 46,
    height: 46,
    borderRadius: 23,
    backgroundColor: '#ffffff',
    borderWidth: 1,
    borderColor: '#eceef2',
    alignItems: 'center',
    justifyContent: 'center',
  },
  horizontalRail: {
    paddingTop: 18,
    paddingBottom: 2,
  },
  horizontalRailCompact: {
    paddingTop: 14,
    paddingBottom: 2,
  },
  serviceCard: {
    width: 108,
    backgroundColor: '#ffffff',
    borderRadius: 24,
    paddingVertical: 16,
    paddingHorizontal: 12,
    marginRight: 12,
    borderWidth: 1,
    borderColor: '#eceef2',
  },
  serviceCardActive: {
    borderColor: '#111827',
  },
  serviceEmoji: {
    fontSize: 28,
  },
  serviceLabel: {
    marginTop: 12,
    fontSize: 16,
    fontWeight: '800',
    color: '#111827',
  },
  serviceSubLabel: {
    marginTop: 4,
    fontSize: 12,
    color: '#6b7280',
  },
  searchBox: {
    marginTop: 18,
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
  offerCard: {
    width: 210,
    backgroundColor: '#111827',
    borderRadius: 24,
    padding: 18,
    marginRight: 12,
  },
  offerEyebrow: {
    color: '#86efac',
    fontWeight: '800',
    fontSize: 12,
    letterSpacing: 1,
  },
  offerTitle: {
    marginTop: 12,
    color: '#ffffff',
    fontSize: 20,
    fontWeight: '800',
  },
  offerSubtitle: {
    marginTop: 8,
    color: '#d1d5db',
    fontSize: 13,
    lineHeight: 19,
  },
  bannerCard: {
    marginTop: 18,
    backgroundColor: '#ffffff',
    borderRadius: 24,
    borderWidth: 1,
    borderColor: '#eceef2',
    padding: 18,
    flexDirection: 'row',
    alignItems: 'center',
  },
  bannerHeadline: {
    fontSize: 24,
    fontWeight: '800',
    color: '#111827',
  },
  bannerCopy: {
    marginTop: 6,
    color: '#6b7280',
    lineHeight: 19,
  },
  bannerIconWrap: {
    width: 52,
    height: 52,
    borderRadius: 26,
    backgroundColor: '#f3f4f6',
    alignItems: 'center',
    justifyContent: 'center',
    marginLeft: 16,
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
    padding: 16,
    marginBottom: 12,
  },
  storeCardTop: {
    flexDirection: 'row',
  },
  storeAvatar: {
    width: 56,
    height: 56,
    borderRadius: 18,
    backgroundColor: '#111827',
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 12,
  },
  storeAvatarText: {
    color: '#ffffff',
    fontSize: 18,
    fontWeight: '800',
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
  favoriteButton: {
    width: 34,
    height: 34,
    borderRadius: 17,
    backgroundColor: '#f3f4f6',
    alignItems: 'center',
    justifyContent: 'center',
    marginLeft: 12,
  },
  storeDescription: {
    marginTop: 4,
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
    marginTop: 8,
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
  offerListCard: {
    backgroundColor: '#ffffff',
    borderRadius: 20,
    borderWidth: 1,
    borderColor: '#eceef2',
    padding: 16,
    marginBottom: 12,
    flexDirection: 'row',
    alignItems: 'center',
  },
  offerListBadge: {
    width: 42,
    height: 42,
    borderRadius: 21,
    backgroundColor: '#f3f4f6',
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 12,
  },
  offerListTitle: {
    fontSize: 16,
    fontWeight: '800',
    color: '#111827',
  },
  offerListText: {
    marginTop: 4,
    color: '#6b7280',
    lineHeight: 19,
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