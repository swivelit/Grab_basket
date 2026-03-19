import React, { useEffect, useMemo, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  Modal,
  Pressable,
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

const STORAGE_TOKEN = '@grab_basket/token';
const STORAGE_ROLE = '@grab_basket/role';

const TOP_SECTIONS = [
  { key: 'food', emoji: '🍔', label: 'Food' },
  { key: 'instamart', emoji: '🛒', label: 'Instamart' },
  { key: 'dineout', emoji: '🍽️', label: 'Dineout' },
  { key: 'scenes', emoji: '🎉', label: 'Scenes' },
];

const HOME_FILTERS = ['All', 'Fresh', 'Maxxsaver', 'Ramzan', 'Exam Ready', 'Snacks', 'Drinks'];

const FESTIVAL_CARDS = [
  { title: 'Chaitra Navratri', subtitle: 'Puja items, fruits, sweets' },
  { title: 'Eid-Ul-Fitr', subtitle: 'Dates, sharbat, dry fruits' },
  { title: 'Ugadi', subtitle: 'Raw mango, jaggery, neem' },
  { title: 'Gifting', subtitle: 'Desserts, celebration hampers' },
];

const CATEGORY_ITEMS = [
  { emoji: '🥬', label: 'Fresh Vegetables' },
  { emoji: '🍎', label: 'Fruits' },
  { emoji: '🥛', label: 'Dairy & Bakery' },
  { emoji: '🍫', label: 'Snacks & Chocolates' },
  { emoji: '🥤', label: 'Cold Drinks' },
  { emoji: '🍚', label: 'Rice & Staples' },
  { emoji: '🧴', label: 'Personal Care' },
  { emoji: '🧼', label: 'Home Essentials' },
];

const DEALS = [
  '₹9 everyday picks',
  'Free delivery on selected stores',
  'Under 20 mins delivery',
  'Weekend grocery refills',
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

async function apiRequest(path, { method = 'GET', token, body } = {}) {
  const response = await fetch(`${API_BASE_URL}${path}`, {
    method,
    headers: {
      Accept: 'application/json',
      ...(body ? { 'Content-Type': 'application/json' } : {}),
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
    },
    body: body ? JSON.stringify(body) : undefined,
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
  const [activeTab, setActiveTab] = useState('instamart');
  const [search, setSearch] = useState('');
  const [vendors, setVendors] = useState([]);
  const [vendorsLoading, setVendorsLoading] = useState(true);

  const [selectedVendor, setSelectedVendor] = useState(null);
  const [products, setProducts] = useState([]);
  const [productsLoading, setProductsLoading] = useState(false);

  const [cart, setCart] = useState({ vendorId: null, items: {} });
  const [showCart, setShowCart] = useState(false);

  const [token, setToken] = useState(null);
  const [role, setRole] = useState(null);

  const [orders, setOrders] = useState([]);
  const [ordersLoading, setOrdersLoading] = useState(false);

  const [showAuthModal, setShowAuthModal] = useState(false);
  const [authMode, setAuthMode] = useState('login');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [authLoading, setAuthLoading] = useState(false);

  const cartLines = useMemo(() => Object.values(cart.items), [cart]);
  const cartCount = useMemo(
    () => cartLines.reduce((sum, item) => sum + item.qty, 0),
    [cartLines]
  );
  const cartSubtotal = useMemo(
    () => cartLines.reduce((sum, item) => sum + item.price * item.qty, 0),
    [cartLines]
  );

  useEffect(() => {
    let cancelled = false;

    (async () => {
      const stored = await AsyncStorage.multiGet([STORAGE_TOKEN, STORAGE_ROLE]);
      if (cancelled) return;

      const savedToken = stored[0]?.[1] || null;
      const savedRole = stored[1]?.[1] || null;

      setToken(savedToken);
      setRole(savedRole);
    })();

    return () => {
      cancelled = true;
    };
  }, []);

  useEffect(() => {
    let cancelled = false;

    const timer = setTimeout(async () => {
      try {
        setVendorsLoading(true);
        const query = search.trim()
          ? `/vendors?q=${encodeURIComponent(search.trim())}`
          : '/vendors';
        const data = await apiRequest(query);
        if (!cancelled) {
          setVendors(Array.isArray(data) ? data : []);
        }
      } catch (error) {
        if (!cancelled) {
          setVendors([]);
          Alert.alert('Vendor loading failed', error.message);
        }
      } finally {
        if (!cancelled) {
          setVendorsLoading(false);
        }
      }
    }, 300);

    return () => {
      cancelled = true;
      clearTimeout(timer);
    };
  }, [search]);

  useEffect(() => {
    let cancelled = false;

    if (!token) {
      setOrders([]);
      return;
    }

    (async () => {
      try {
        setOrdersLoading(true);
        const data = await apiRequest('/orders/me', { token });
        if (!cancelled) {
          setOrders(Array.isArray(data) ? data : []);
        }
      } catch {
        if (!cancelled) {
          setOrders([]);
        }
      } finally {
        if (!cancelled) {
          setOrdersLoading(false);
        }
      }
    })();

    return () => {
      cancelled = true;
    };
  }, [token]);

  const openAuth = (mode = 'login') => {
    setAuthMode(mode);
    setShowAuthModal(true);
  };

  const submitAuth = async () => {
    const cleanEmail = email.trim();

    if (!cleanEmail || !password) {
      Alert.alert('Missing details', 'Enter email and password.');
      return;
    }

    try {
      setAuthLoading(true);

      const path = authMode === 'register' ? '/auth/register' : '/auth/login';
      const body =
        authMode === 'register'
          ? { email: cleanEmail, password, role: 'CUSTOMER' }
          : { email: cleanEmail, password };

      const data = await apiRequest(path, { method: 'POST', body });

      await AsyncStorage.multiSet([
        [STORAGE_TOKEN, data.access_token],
        [STORAGE_ROLE, data.role],
      ]);

      setToken(data.access_token);
      setRole(data.role);
      setShowAuthModal(false);
      setEmail('');
      setPassword('');
      setActiveTab('account');

      Alert.alert(
        authMode === 'register' ? 'Account created' : 'Logged in',
        `Signed in as ${data.role}`
      );
    } catch (error) {
      Alert.alert('Auth failed', error.message);
    } finally {
      setAuthLoading(false);
    }
  };

  const logout = async () => {
    await AsyncStorage.multiRemove([STORAGE_TOKEN, STORAGE_ROLE]);
    setToken(null);
    setRole(null);
    setOrders([]);
    Alert.alert('Logged out', 'Your session has been cleared.');
  };

  const replaceCartWithProduct = (product) => {
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
        'You can order from only one store at a time.',
        [
          { text: 'Cancel', style: 'cancel' },
          {
            text: 'Replace',
            style: 'destructive',
            onPress: () => replaceCartWithProduct(product),
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

  const changeQty = (product, delta) => {
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
    try {
      setSelectedVendor(vendor);
      setProductsLoading(true);
      setProducts([]);
      const data = await apiRequest(`/vendors/${vendor.id}/products`);
      setProducts(Array.isArray(data) ? data : []);
    } catch (error) {
      Alert.alert('Store loading failed', error.message);
    } finally {
      setProductsLoading(false);
    }
  };

  const placeOrder = async () => {
    if (!token) {
      setActiveTab('account');
      setShowCart(false);
      openAuth('login');
      return;
    }

    if (role !== 'CUSTOMER') {
      Alert.alert(
        'Customer account required',
        'Use a CUSTOMER account to place orders.'
      );
      return;
    }

    if (!cart.vendorId || cartLines.length === 0) {
      Alert.alert('Cart is empty', 'Add products before placing an order.');
      return;
    }

    try {
      const payload = {
        vendor_id: cart.vendorId,
        payment_method: 'COD',
        items: cartLines.map((item) => ({
          product_id: item.id,
          qty: item.qty,
        })),
      };

      const result = await apiRequest('/orders', {
        method: 'POST',
        token,
        body: payload,
      });

      clearCart();
      setSelectedVendor(null);
      setShowCart(false);
      setActiveTab('orders');

      const refreshed = await apiRequest('/orders/me', { token });
      setOrders(Array.isArray(refreshed) ? refreshed : []);

      Alert.alert('Order placed', `Order #${result.id} created successfully.`);
    } catch (error) {
      Alert.alert('Order failed', error.message);
    }
  };

  const renderOrdersList = (title, subtitle) => (
    <ScrollView contentContainerStyle={styles.pageContent}>
      <SectionHeader title={title} subtitle={subtitle} />
      {!token ? (
        <EmptyCard
          title="Login required"
          text="Login as a customer to view your order history and reorder quickly."
          primaryLabel="Login"
          onPrimary={() => openAuth('login')}
          secondaryLabel="Create account"
          onSecondary={() => openAuth('register')}
        />
      ) : ordersLoading ? (
        <View style={styles.centerBox}>
          <ActivityIndicator size="large" />
        </View>
      ) : orders.length === 0 ? (
        <EmptyCard
          title="No orders yet"
          text="Your placed orders will show up here."
        />
      ) : (
        orders.map((order) => <OrderCard key={order.id} order={order} />)
      )}
    </ScrollView>
  );

  const renderHome = () => (
    <ScrollView contentContainerStyle={styles.pageContent}>
      <Text style={styles.heroEta}>23 mins</Text>
      <View style={styles.addressRow}>
        <Ionicons name="location-outline" size={18} color="#333" />
        <Text style={styles.addressText}>
          To Valliachans Place: 12b, Great Orchard...
        </Text>
        <Ionicons name="chevron-down" size={16} color="#333" />
      </View>

      <ScrollView
        horizontal
        showsHorizontalScrollIndicator={false}
        contentContainerStyle={styles.railRow}
      >
        {TOP_SECTIONS.map((item) => (
          <TopShortcutCard
            key={item.key}
            emoji={item.emoji}
            label={item.label}
            active={item.key === 'instamart'}
          />
        ))}
      </ScrollView>

      <View style={styles.searchBox}>
        <Ionicons name="search-outline" size={20} color="#6b7280" />
        <TextInput
          style={styles.searchInput}
          placeholder="Search for sunscreen, stores, groceries..."
          placeholderTextColor="#9ca3af"
          value={search}
          onChangeText={setSearch}
        />
        <Ionicons name="document-text-outline" size={20} color="#6b7280" />
      </View>

      <ScrollView
        horizontal
        showsHorizontalScrollIndicator={false}
        contentContainerStyle={styles.railRow}
      >
        {HOME_FILTERS.map((item, index) => (
          <FilterChip key={item} label={item} active={index === 0} />
        ))}
      </ScrollView>

      <SectionHeader
        title="Season of celebration"
        subtitle="Same section flow as your reference home screen"
      />
      <ScrollView
        horizontal
        showsHorizontalScrollIndicator={false}
        contentContainerStyle={styles.railRow}
      >
        {FESTIVAL_CARDS.map((item) => (
          <FestivalCard
            key={item.title}
            title={item.title}
            subtitle={item.subtitle}
          />
        ))}
      </ScrollView>

      <View style={styles.bannerCard}>
        <View>
          <Text style={styles.bannerTitle}>₹9 everyday</Text>
          <Text style={styles.bannerText}>
            Shop selected add-ons and quick basket fillers.
          </Text>
        </View>
        <Ionicons name="flash-outline" size={22} color="#111" />
      </View>

      <SectionHeader
        title="Popular stores near you"
        subtitle="Loaded from your existing FastAPI backend"
      />

      {vendorsLoading ? (
        <View style={styles.centerBox}>
          <ActivityIndicator size="large" />
        </View>
      ) : vendors.length === 0 ? (
        <EmptyCard
          title="No stores found"
          text="Create seller/vendor data from your backend, then the home screen will fill automatically."
        />
      ) : (
        vendors.map((vendor) => (
          <StoreCard
            key={vendor.id}
            vendor={vendor}
            onPress={() => openVendor(vendor)}
          />
        ))
      )}
    </ScrollView>
  );

  const renderCategories = () => (
    <ScrollView contentContainerStyle={styles.pageContent}>
      <SectionHeader
        title="Categories"
        subtitle="Use this tab for the grocery-first sectioning"
      />

      <View style={styles.categoryGrid}>
        {CATEGORY_ITEMS.map((item) => (
          <View key={item.label} style={styles.categoryCard}>
            <Text style={styles.categoryEmoji}>{item.emoji}</Text>
            <Text style={styles.categoryLabel}>{item.label}</Text>
          </View>
        ))}
      </View>

      <SectionHeader
        title="Deal strips"
        subtitle="Secondary sections similar to Instamart category rails"
      />

      {DEALS.map((deal) => (
        <View key={deal} style={styles.simpleRowCard}>
          <Text style={styles.simpleRowText}>{deal}</Text>
          <Ionicons name="chevron-forward" size={18} color="#111" />
        </View>
      ))}

      <SectionHeader
        title="Browse stores"
        subtitle="Tap any store to open its product list"
      />

      {vendorsLoading ? (
        <View style={styles.centerBox}>
          <ActivityIndicator size="large" />
        </View>
      ) : vendors.length === 0 ? (
        <EmptyCard
          title="No stores available"
          text="Vendor cards will appear here when backend data exists."
        />
      ) : (
        vendors.map((vendor) => (
          <StoreCard
            key={`cat-${vendor.id}`}
            vendor={vendor}
            onPress={() => openVendor(vendor)}
          />
        ))
      )}
    </ScrollView>
  );

  const renderReorder = () =>
    renderOrdersList(
      'Reorder',
      'This tab can later be upgraded to one-tap reorder flows'
    );

  const renderAccount = () => (
    <ScrollView contentContainerStyle={styles.pageContent}>
      <SectionHeader
        title="Account"
        subtitle="Customer auth, session state, and API connection"
      />

      {!token ? (
        <EmptyCard
          title="Not logged in"
          text="Use customer login to place orders and fetch /orders/me."
          primaryLabel="Login"
          onPrimary={() => openAuth('login')}
          secondaryLabel="Create account"
          onSecondary={() => openAuth('register')}
        />
      ) : (
        <View style={styles.accountCard}>
          <View style={styles.accountHeader}>
            <View style={styles.accountAvatar}>
              <Text style={styles.accountAvatarText}>
                {initials(email || role || 'GB')}
              </Text>
            </View>
            <View style={{ flex: 1 }}>
              <Text style={styles.accountTitle}>Session active</Text>
              <Text style={styles.accountSubtitle}>Role: {role}</Text>
              <Text style={styles.accountSubtitle}>API: {API_BASE_URL}</Text>
            </View>
          </View>

          <TouchableOpacity style={styles.primaryButton} onPress={() => setActiveTab('orders')}>
            <Text style={styles.primaryButtonText}>View my orders</Text>
          </TouchableOpacity>

          <TouchableOpacity style={styles.secondaryButton} onPress={logout}>
            <Text style={styles.secondaryButtonText}>Logout</Text>
          </TouchableOpacity>
        </View>
      )}

      <SectionHeader
        title="What this screen already does"
        subtitle="Built on top of your current backend"
      />

      <View style={styles.infoCard}>
        <InfoLine text="Home screen sections follow the same structure as your screenshot." />
        <InfoLine text="Store list comes from /vendors." />
        <InfoLine text="Product list comes from /vendors/{id}/products." />
        <InfoLine text="Order placement uses /orders." />
        <InfoLine text="Order history uses /orders/me." />
      </View>
    </ScrollView>
  );

  const renderVendorScreen = () => (
    <View style={styles.screen}>
      <View style={styles.headerBar}>
        <TouchableOpacity
          style={styles.iconButton}
          onPress={() => setSelectedVendor(null)}
        >
          <Ionicons name="arrow-back-outline" size={20} color="#111" />
        </TouchableOpacity>

        <View style={{ flex: 1 }}>
          <Text style={styles.headerTitle}>{selectedVendor?.name}</Text>
          <Text style={styles.headerSubtitle}>
            {selectedVendor?.description || selectedVendor?.address || 'Store'}
          </Text>
        </View>

        <TouchableOpacity
          style={styles.cartTopButton}
          onPress={() => setShowCart(true)}
        >
          <Ionicons name="cart-outline" size={18} color="#fff" />
          <Text style={styles.cartTopButtonText}>{cartCount}</Text>
        </TouchableOpacity>
      </View>

      <ScrollView contentContainerStyle={styles.pageContent}>
        <View style={styles.vendorHero}>
          <Text style={styles.vendorHeroTitle}>{selectedVendor?.name}</Text>
          <Text style={styles.vendorHeroText}>
            {selectedVendor?.address || 'Quick grocery and essentials'}
          </Text>
        </View>

        {productsLoading ? (
          <View style={styles.centerBox}>
            <ActivityIndicator size="large" />
          </View>
        ) : products.length === 0 ? (
          <EmptyCard
            title="No products yet"
            text="Add products from the seller side and they will appear here."
          />
        ) : (
          products.map((product) => (
            <ProductCard
              key={product.id}
              product={product}
              qty={cart.items[product.id]?.qty || 0}
              onAdd={() => addToCart(product)}
              onRemove={() => changeQty(product, -1)}
            />
          ))
        )}
      </ScrollView>

      {cartCount > 0 && cart.vendorId === selectedVendor?.id ? (
        <TouchableOpacity style={styles.floatingCart} onPress={() => setShowCart(true)}>
          <View>
            <Text style={styles.floatingCartTitle}>View cart</Text>
            <Text style={styles.floatingCartText}>
              {cartCount} items • {money(cartSubtotal)}
            </Text>
          </View>
          <Ionicons name="arrow-forward" size={20} color="#fff" />
        </TouchableOpacity>
      ) : null}
    </View>
  );

  const renderCartScreen = () => (
    <View style={styles.screen}>
      <View style={styles.headerBar}>
        <TouchableOpacity
          style={styles.iconButton}
          onPress={() => setShowCart(false)}
        >
          <Ionicons name="arrow-back-outline" size={20} color="#111" />
        </TouchableOpacity>

        <View style={{ flex: 1 }}>
          <Text style={styles.headerTitle}>Your cart</Text>
          <Text style={styles.headerSubtitle}>
            {selectedVendor?.name || 'Grab Basket'}
          </Text>
        </View>
      </View>

      <ScrollView contentContainerStyle={styles.pageContent}>
        {cartLines.length === 0 ? (
          <EmptyCard
            title="Cart is empty"
            text="Add products from a store to continue."
          />
        ) : (
          <>
            <View style={styles.cartCard}>
              {cartLines.map((item) => (
                <View key={item.id} style={styles.cartLine}>
                  <View style={{ flex: 1, paddingRight: 12 }}>
                    <Text style={styles.cartLineTitle}>{item.name}</Text>
                    <Text style={styles.cartLineSubtitle}>{money(item.price)} each</Text>
                  </View>

                  <View style={styles.qtyPill}>
                    <TouchableOpacity
                      style={styles.qtyIcon}
                      onPress={() => changeQty(item, -1)}
                    >
                      <Ionicons name="remove" size={16} color="#111" />
                    </TouchableOpacity>
                    <Text style={styles.qtyValue}>{item.qty}</Text>
                    <TouchableOpacity
                      style={styles.qtyIcon}
                      onPress={() => addToCart(item)}
                    >
                      <Ionicons name="add" size={16} color="#111" />
                    </TouchableOpacity>
                  </View>
                </View>
              ))}
            </View>

            <View style={styles.summaryCard}>
              <Text style={styles.summaryTitle}>Bill details</Text>

              <View style={styles.summaryRow}>
                <Text style={styles.summaryLabel}>Subtotal</Text>
                <Text style={styles.summaryValue}>{money(cartSubtotal)}</Text>
              </View>

              <View style={styles.summaryRow}>
                <Text style={styles.summaryLabel}>Delivery</Text>
                <Text style={styles.summaryValue}>Calculated by backend</Text>
              </View>

              <View style={styles.summaryDivider} />

              <View style={styles.summaryRow}>
                <Text style={styles.summaryTotalLabel}>Items</Text>
                <Text style={styles.summaryTotalLabel}>{cartCount}</Text>
              </View>
            </View>

            <TouchableOpacity style={styles.primaryButton} onPress={placeOrder}>
              <Text style={styles.primaryButtonText}>
                {token ? 'Place order' : 'Login to place order'}
              </Text>
            </TouchableOpacity>

            <TouchableOpacity style={styles.secondaryButton} onPress={clearCart}>
              <Text style={styles.secondaryButtonText}>Clear cart</Text>
            </TouchableOpacity>
          </>
        )}
      </ScrollView>
    </View>
  );

  const renderTabContent = () => {
    if (selectedVendor) return renderVendorScreen();
    if (showCart) return renderCartScreen();

    switch (activeTab) {
      case 'categories':
        return renderCategories();
      case 'reorder':
        return renderReorder();
      case 'orders':
        return renderOrdersList('Orders', 'Track customer orders from your backend');
      case 'account':
        return renderAccount();
      case 'instamart':
      default:
        return renderHome();
    }
  };

  return (
    <SafeAreaView style={styles.safeArea}>
      <StatusBar barStyle="dark-content" backgroundColor="#ffffff" />
      {renderTabContent()}

      {!selectedVendor && !showCart ? (
        <View style={styles.tabBar}>
          <BottomTabButton
            label="Instamart"
            icon="bag-handle-outline"
            active={activeTab === 'instamart'}
            onPress={() => setActiveTab('instamart')}
          />
          <BottomTabButton
            label="Categories"
            icon="grid-outline"
            active={activeTab === 'categories'}
            onPress={() => setActiveTab('categories')}
          />
          <BottomTabButton
            label="Reorder"
            icon="reload-outline"
            active={activeTab === 'reorder'}
            onPress={() => setActiveTab('reorder')}
          />
          <BottomTabButton
            label="Orders"
            icon="receipt-outline"
            active={activeTab === 'orders'}
            onPress={() => setActiveTab('orders')}
          />
          <BottomTabButton
            label="Account"
            icon="person-outline"
            active={activeTab === 'account'}
            onPress={() => setActiveTab('account')}
          />
        </View>
      ) : null}

      <AuthModal
        visible={showAuthModal}
        mode={authMode}
        setMode={setAuthMode}
        email={email}
        setEmail={setEmail}
        password={password}
        setPassword={setPassword}
        loading={authLoading}
        onClose={() => setShowAuthModal(false)}
        onSubmit={submitAuth}
      />
    </SafeAreaView>
  );
}

function TopShortcutCard({ emoji, label, active }) {
  return (
    <View style={[styles.shortcutCard, active && styles.shortcutCardActive]}>
      <Text style={styles.shortcutEmoji}>{emoji}</Text>
      <Text style={styles.shortcutLabel}>{label}</Text>
    </View>
  );
}

function FilterChip({ label, active }) {
  return (
    <View style={[styles.filterChip, active && styles.filterChipActive]}>
      <Text style={[styles.filterChipText, active && styles.filterChipTextActive]}>
        {label}
      </Text>
    </View>
  );
}

function FestivalCard({ title, subtitle }) {
  return (
    <View style={styles.festivalCard}>
      <Text style={styles.festivalEmoji}>🎊</Text>
      <Text style={styles.festivalTitle}>{title}</Text>
      <Text style={styles.festivalSubtitle}>{subtitle}</Text>
    </View>
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

function StoreCard({ vendor, onPress }) {
  return (
    <TouchableOpacity activeOpacity={0.9} style={styles.storeCard} onPress={onPress}>
      <View style={styles.storeTop}>
        <View style={styles.storeAvatar}>
          <Text style={styles.storeAvatarText}>{initials(vendor.name)}</Text>
        </View>

        <View style={{ flex: 1 }}>
          <Text style={styles.storeName}>{vendor.name}</Text>
          <Text style={styles.storeDesc}>
            {vendor.description || vendor.address || 'Daily essentials and groceries'}
          </Text>

          <View style={styles.badgeRow}>
            <Badge text={vendor.open_now ? 'Open now' : 'Store'} />
            {vendor.can_deliver ? <Badge text="Deliverable" /> : null}
            {vendor.distance_km != null ? (
              <Badge text={`${vendor.distance_km.toFixed(1)} km`} />
            ) : null}
            {vendor.delivery_radius_km != null ? (
              <Badge text={`Radius ${vendor.delivery_radius_km} km`} />
            ) : null}
          </View>
        </View>
      </View>

      <View style={styles.storeBottom}>
        <Text style={styles.storeAddress} numberOfLines={1}>
          {vendor.address || 'Tap to view store menu'}
        </Text>
        <View style={styles.browseButton}>
          <Text style={styles.browseButtonText}>Browse</Text>
        </View>
      </View>
    </TouchableOpacity>
  );
}

function ProductCard({ product, qty, onAdd, onRemove }) {
  return (
    <View style={styles.productCard}>
      <Text style={styles.productTitle}>{product.name}</Text>
      <Text style={styles.productDescription}>
        {product.description || 'Store product'}
      </Text>

      <View style={styles.productFooter}>
        <Text style={styles.productPrice}>{money(product.price)}</Text>

        {qty > 0 ? (
          <View style={styles.qtyPill}>
            <TouchableOpacity style={styles.qtyIcon} onPress={onRemove}>
              <Ionicons name="remove" size={16} color="#111" />
            </TouchableOpacity>
            <Text style={styles.qtyValue}>{qty}</Text>
            <TouchableOpacity style={styles.qtyIcon} onPress={onAdd}>
              <Ionicons name="add" size={16} color="#111" />
            </TouchableOpacity>
          </View>
        ) : (
          <TouchableOpacity style={styles.addButton} onPress={onAdd}>
            <Text style={styles.addButtonText}>Add</Text>
          </TouchableOpacity>
        )}
      </View>
    </View>
  );
}

function OrderCard({ order }) {
  return (
    <View style={styles.orderCard}>
      <View style={styles.orderHeader}>
        <Text style={styles.orderTitle}>Order #{order.id}</Text>
        <View style={styles.statusBadge}>
          <Text style={styles.statusBadgeText}>{order.status}</Text>
        </View>
      </View>

      <Text style={styles.orderText}>
        Total: {money(order.total_amount)} • Delivery: {money(order.delivery_fee)}
      </Text>
      <Text style={styles.orderText}>Items: {order.items?.length || 0}</Text>
      <Text style={styles.orderText}>Payment: {order.payment_method}</Text>
    </View>
  );
}

function Badge({ text }) {
  return (
    <View style={styles.badge}>
      <Text style={styles.badgeText}>{text}</Text>
    </View>
  );
}

function BottomTabButton({ label, icon, active, onPress }) {
  return (
    <TouchableOpacity style={styles.tabButton} onPress={onPress}>
      <Ionicons name={icon} size={20} color={active ? '#111' : '#6b7280'} />
      <Text style={[styles.tabText, active && styles.tabTextActive]}>{label}</Text>
    </TouchableOpacity>
  );
}

function EmptyCard({
  title,
  text,
  primaryLabel,
  onPrimary,
  secondaryLabel,
  onSecondary,
}) {
  return (
    <View style={styles.emptyCard}>
      <Text style={styles.emptyTitle}>{title}</Text>
      <Text style={styles.emptyText}>{text}</Text>

      {primaryLabel ? (
        <TouchableOpacity style={styles.primaryButton} onPress={onPrimary}>
          <Text style={styles.primaryButtonText}>{primaryLabel}</Text>
        </TouchableOpacity>
      ) : null}

      {secondaryLabel ? (
        <TouchableOpacity style={styles.secondaryButton} onPress={onSecondary}>
          <Text style={styles.secondaryButtonText}>{secondaryLabel}</Text>
        </TouchableOpacity>
      ) : null}
    </View>
  );
}

function InfoLine({ text }) {
  return (
    <View style={styles.infoLine}>
      <Ionicons name="checkmark-circle-outline" size={18} color="#111" />
      <Text style={styles.infoLineText}>{text}</Text>
    </View>
  );
}

function AuthModal({
  visible,
  mode,
  setMode,
  email,
  setEmail,
  password,
  setPassword,
  loading,
  onClose,
  onSubmit,
}) {
  return (
    <Modal visible={visible} transparent animationType="slide" onRequestClose={onClose}>
      <View style={styles.modalBackdrop}>
        <Pressable style={styles.modalDismissArea} onPress={onClose} />
        <View style={styles.modalCard}>
          <View style={styles.modalHandle} />

          <Text style={styles.modalTitle}>
            {mode === 'register' ? 'Create customer account' : 'Customer login'}
          </Text>
          <Text style={styles.modalSubtitle}>
            This connects directly to your existing FastAPI auth endpoints.
          </Text>

          <View style={styles.authSwitchRow}>
            <TouchableOpacity
              style={[styles.authSwitch, mode === 'login' && styles.authSwitchActive]}
              onPress={() => setMode('login')}
            >
              <Text
                style={[
                  styles.authSwitchText,
                  mode === 'login' && styles.authSwitchTextActive,
                ]}
              >
                Login
              </Text>
            </TouchableOpacity>

            <TouchableOpacity
              style={[styles.authSwitch, mode === 'register' && styles.authSwitchActive]}
              onPress={() => setMode('register')}
            >
              <Text
                style={[
                  styles.authSwitchText,
                  mode === 'register' && styles.authSwitchTextActive,
                ]}
              >
                Register
              </Text>
            </TouchableOpacity>
          </View>

          <TextInput
            style={styles.input}
            placeholder="Email"
            keyboardType="email-address"
            autoCapitalize="none"
            value={email}
            onChangeText={setEmail}
          />

          <TextInput
            style={styles.input}
            placeholder="Password"
            secureTextEntry
            value={password}
            onChangeText={setPassword}
          />

          <TouchableOpacity
            style={styles.primaryButton}
            onPress={onSubmit}
            disabled={loading}
          >
            {loading ? (
              <ActivityIndicator color="#ffffff" />
            ) : (
              <Text style={styles.primaryButtonText}>
                {mode === 'register' ? 'Create account' : 'Login'}
              </Text>
            )}
          </TouchableOpacity>

          <TouchableOpacity style={styles.secondaryButton} onPress={onClose}>
            <Text style={styles.secondaryButtonText}>Close</Text>
          </TouchableOpacity>
        </View>
      </View>
    </Modal>
  );
}

const styles = StyleSheet.create({
  safeArea: {
    flex: 1,
    backgroundColor: '#f6f7f9',
  },
  screen: {
    flex: 1,
  },
  pageContent: {
    padding: 16,
    paddingBottom: 120,
  },
  centerBox: {
    paddingVertical: 40,
    alignItems: 'center',
    justifyContent: 'center',
  },
  heroEta: {
    fontSize: 36,
    fontWeight: '800',
    color: '#111',
  },
  addressRow: {
    marginTop: 6,
    flexDirection: 'row',
    alignItems: 'center',
  },
  addressText: {
    flex: 1,
    fontSize: 15,
    color: '#4b5563',
    marginHorizontal: 6,
  },
  railRow: {
    paddingTop: 18,
    paddingBottom: 2,
  },
  shortcutCard: {
    width: 96,
    backgroundColor: '#fff',
    borderRadius: 22,
    paddingVertical: 16,
    paddingHorizontal: 12,
    marginRight: 12,
    borderWidth: 1,
    borderColor: '#ececec',
    alignItems: 'center',
  },
  shortcutCardActive: {
    borderColor: '#111',
  },
  shortcutEmoji: {
    fontSize: 28,
  },
  shortcutLabel: {
    marginTop: 10,
    fontSize: 14,
    fontWeight: '700',
    color: '#111',
  },
  searchBox: {
    marginTop: 18,
    height: 56,
    borderRadius: 18,
    backgroundColor: '#fff',
    borderWidth: 1,
    borderColor: '#ececec',
    paddingHorizontal: 14,
    flexDirection: 'row',
    alignItems: 'center',
  },
  searchInput: {
    flex: 1,
    marginHorizontal: 10,
    fontSize: 16,
    color: '#111',
  },
  filterChip: {
    backgroundColor: '#fff',
    borderRadius: 999,
    borderWidth: 1,
    borderColor: '#e5e7eb',
    paddingHorizontal: 14,
    paddingVertical: 10,
    marginRight: 10,
  },
  filterChipActive: {
    borderColor: '#111',
  },
  filterChipText: {
    color: '#4b5563',
    fontWeight: '600',
  },
  filterChipTextActive: {
    color: '#111',
  },
  sectionHeader: {
    marginTop: 24,
    marginBottom: 12,
  },
  sectionTitle: {
    fontSize: 20,
    fontWeight: '800',
    color: '#111',
  },
  sectionSubtitle: {
    marginTop: 4,
    fontSize: 13,
    lineHeight: 18,
    color: '#6b7280',
  },
  festivalCard: {
    width: 150,
    backgroundColor: '#fff',
    borderRadius: 24,
    borderWidth: 1,
    borderColor: '#ececec',
    padding: 16,
    marginRight: 12,
  },
  festivalEmoji: {
    fontSize: 28,
  },
  festivalTitle: {
    marginTop: 12,
    fontSize: 16,
    fontWeight: '800',
    color: '#111',
  },
  festivalSubtitle: {
    marginTop: 6,
    fontSize: 13,
    color: '#6b7280',
    lineHeight: 18,
  },
  bannerCard: {
    marginTop: 18,
    backgroundColor: '#fff',
    borderRadius: 24,
    borderWidth: 1,
    borderColor: '#ececec',
    padding: 18,
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  bannerTitle: {
    fontSize: 22,
    fontWeight: '800',
    color: '#111',
  },
  bannerText: {
    marginTop: 4,
    color: '#6b7280',
  },
  storeCard: {
    backgroundColor: '#fff',
    borderRadius: 24,
    borderWidth: 1,
    borderColor: '#ececec',
    padding: 16,
    marginBottom: 12,
  },
  storeTop: {
    flexDirection: 'row',
  },
  storeAvatar: {
    width: 56,
    height: 56,
    borderRadius: 18,
    backgroundColor: '#111',
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 12,
  },
  storeAvatarText: {
    color: '#fff',
    fontSize: 18,
    fontWeight: '800',
  },
  storeName: {
    fontSize: 18,
    fontWeight: '800',
    color: '#111',
  },
  storeDesc: {
    marginTop: 4,
    fontSize: 13,
    lineHeight: 18,
    color: '#6b7280',
  },
  badgeRow: {
    marginTop: 10,
    flexDirection: 'row',
    flexWrap: 'wrap',
  },
  badge: {
    backgroundColor: '#f3f4f6',
    borderRadius: 999,
    paddingHorizontal: 10,
    paddingVertical: 6,
    marginRight: 8,
    marginBottom: 8,
  },
  badgeText: {
    fontSize: 12,
    fontWeight: '600',
    color: '#374151',
  },
  storeBottom: {
    marginTop: 10,
    flexDirection: 'row',
    alignItems: 'center',
  },
  storeAddress: {
    flex: 1,
    fontSize: 13,
    color: '#4b5563',
    marginRight: 12,
  },
  browseButton: {
    backgroundColor: '#111',
    borderRadius: 14,
    paddingHorizontal: 16,
    paddingVertical: 10,
  },
  browseButtonText: {
    color: '#fff',
    fontWeight: '700',
  },
  categoryGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    justifyContent: 'space-between',
  },
  categoryCard: {
    width: '48%',
    backgroundColor: '#fff',
    borderRadius: 20,
    borderWidth: 1,
    borderColor: '#ececec',
    padding: 16,
    marginBottom: 12,
  },
  categoryEmoji: {
    fontSize: 30,
  },
  categoryLabel: {
    marginTop: 10,
    fontSize: 16,
    fontWeight: '700',
    color: '#111',
  },
  simpleRowCard: {
    backgroundColor: '#fff',
    borderRadius: 18,
    borderWidth: 1,
    borderColor: '#ececec',
    paddingHorizontal: 16,
    paddingVertical: 16,
    marginBottom: 10,
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  simpleRowText: {
    fontSize: 15,
    fontWeight: '600',
    color: '#111',
  },
  headerBar: {
    backgroundColor: '#fff',
    borderBottomWidth: 1,
    borderColor: '#ececec',
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
  headerTitle: {
    fontSize: 19,
    fontWeight: '800',
    color: '#111',
  },
  headerSubtitle: {
    marginTop: 4,
    fontSize: 13,
    color: '#6b7280',
  },
  cartTopButton: {
    marginLeft: 12,
    backgroundColor: '#111',
    borderRadius: 14,
    paddingHorizontal: 14,
    paddingVertical: 12,
    flexDirection: 'row',
    alignItems: 'center',
  },
  cartTopButtonText: {
    marginLeft: 6,
    color: '#fff',
    fontWeight: '700',
  },
  vendorHero: {
    backgroundColor: '#fff',
    borderRadius: 24,
    borderWidth: 1,
    borderColor: '#ececec',
    padding: 18,
    marginBottom: 16,
  },
  vendorHeroTitle: {
    fontSize: 22,
    fontWeight: '800',
    color: '#111',
  },
  vendorHeroText: {
    marginTop: 6,
    fontSize: 14,
    color: '#6b7280',
  },
  productCard: {
    backgroundColor: '#fff',
    borderRadius: 22,
    borderWidth: 1,
    borderColor: '#ececec',
    padding: 16,
    marginBottom: 12,
  },
  productTitle: {
    fontSize: 17,
    fontWeight: '800',
    color: '#111',
  },
  productDescription: {
    marginTop: 6,
    fontSize: 13,
    lineHeight: 18,
    color: '#6b7280',
  },
  productFooter: {
    marginTop: 14,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  productPrice: {
    fontSize: 18,
    fontWeight: '800',
    color: '#111',
  },
  addButton: {
    backgroundColor: '#111',
    borderRadius: 14,
    paddingHorizontal: 18,
    paddingVertical: 10,
  },
  addButtonText: {
    color: '#fff',
    fontWeight: '700',
  },
  qtyPill: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#f3f4f6',
    borderRadius: 999,
    paddingHorizontal: 4,
    paddingVertical: 4,
  },
  qtyIcon: {
    width: 32,
    height: 32,
    borderRadius: 16,
    backgroundColor: '#fff',
    alignItems: 'center',
    justifyContent: 'center',
  },
  qtyValue: {
    minWidth: 26,
    textAlign: 'center',
    fontWeight: '800',
    color: '#111',
  },
  floatingCart: {
    position: 'absolute',
    left: 16,
    right: 16,
    bottom: 16,
    backgroundColor: '#111',
    borderRadius: 20,
    paddingHorizontal: 18,
    paddingVertical: 16,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  floatingCartTitle: {
    color: '#fff',
    fontSize: 16,
    fontWeight: '800',
  },
  floatingCartText: {
    marginTop: 4,
    color: '#e5e7eb',
  },
  cartCard: {
    backgroundColor: '#fff',
    borderRadius: 22,
    borderWidth: 1,
    borderColor: '#ececec',
    padding: 16,
    marginBottom: 12,
  },
  cartLine: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: 12,
    borderBottomWidth: StyleSheet.hairlineWidth,
    borderColor: '#e5e7eb',
  },
  cartLineTitle: {
    fontSize: 15,
    fontWeight: '700',
    color: '#111',
  },
  cartLineSubtitle: {
    marginTop: 4,
    color: '#6b7280',
  },
  summaryCard: {
    backgroundColor: '#fff',
    borderRadius: 22,
    borderWidth: 1,
    borderColor: '#ececec',
    padding: 16,
  },
  summaryTitle: {
    fontSize: 17,
    fontWeight: '800',
    color: '#111',
    marginBottom: 10,
  },
  summaryRow: {
    paddingVertical: 8,
    flexDirection: 'row',
    justifyContent: 'space-between',
  },
  summaryLabel: {
    color: '#4b5563',
  },
  summaryValue: {
    color: '#111',
    fontWeight: '600',
  },
  summaryDivider: {
    height: 1,
    backgroundColor: '#ececec',
    marginVertical: 8,
  },
  summaryTotalLabel: {
    fontSize: 16,
    fontWeight: '800',
    color: '#111',
  },
  primaryButton: {
    marginTop: 16,
    backgroundColor: '#111',
    borderRadius: 16,
    paddingVertical: 16,
    alignItems: 'center',
    justifyContent: 'center',
  },
  primaryButtonText: {
    color: '#fff',
    fontSize: 15,
    fontWeight: '800',
  },
  secondaryButton: {
    marginTop: 12,
    backgroundColor: '#fff',
    borderRadius: 16,
    borderWidth: 1,
    borderColor: '#d1d5db',
    paddingVertical: 16,
    alignItems: 'center',
    justifyContent: 'center',
  },
  secondaryButtonText: {
    color: '#111',
    fontSize: 15,
    fontWeight: '700',
  },
  emptyCard: {
    backgroundColor: '#fff',
    borderRadius: 22,
    borderWidth: 1,
    borderColor: '#ececec',
    padding: 20,
  },
  emptyTitle: {
    fontSize: 18,
    fontWeight: '800',
    color: '#111',
  },
  emptyText: {
    marginTop: 8,
    fontSize: 14,
    lineHeight: 20,
    color: '#6b7280',
  },
  orderCard: {
    backgroundColor: '#fff',
    borderRadius: 22,
    borderWidth: 1,
    borderColor: '#ececec',
    padding: 16,
    marginBottom: 12,
  },
  orderHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  orderTitle: {
    fontSize: 16,
    fontWeight: '800',
    color: '#111',
  },
  statusBadge: {
    backgroundColor: '#f3f4f6',
    borderRadius: 999,
    paddingHorizontal: 10,
    paddingVertical: 6,
  },
  statusBadgeText: {
    fontSize: 12,
    fontWeight: '700',
    color: '#111',
  },
  orderText: {
    marginTop: 8,
    color: '#4b5563',
  },
  accountCard: {
    backgroundColor: '#fff',
    borderRadius: 22,
    borderWidth: 1,
    borderColor: '#ececec',
    padding: 16,
  },
  accountHeader: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  accountAvatar: {
    width: 52,
    height: 52,
    borderRadius: 18,
    backgroundColor: '#111',
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 12,
  },
  accountAvatarText: {
    color: '#fff',
    fontWeight: '800',
    fontSize: 18,
  },
  accountTitle: {
    fontSize: 17,
    fontWeight: '800',
    color: '#111',
  },
  accountSubtitle: {
    marginTop: 4,
    color: '#6b7280',
  },
  infoCard: {
    backgroundColor: '#fff',
    borderRadius: 22,
    borderWidth: 1,
    borderColor: '#ececec',
    padding: 16,
  },
  infoLine: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 12,
  },
  infoLineText: {
    flex: 1,
    marginLeft: 10,
    color: '#374151',
    lineHeight: 20,
  },
  tabBar: {
    backgroundColor: '#fff',
    borderTopWidth: 1,
    borderColor: '#ececec',
    flexDirection: 'row',
    paddingTop: 8,
    paddingBottom: 12,
  },
  tabButton: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  tabText: {
    marginTop: 4,
    fontSize: 12,
    color: '#6b7280',
  },
  tabTextActive: {
    color: '#111',
    fontWeight: '700',
  },
  modalBackdrop: {
    flex: 1,
    backgroundColor: 'rgba(0,0,0,0.35)',
    justifyContent: 'flex-end',
  },
  modalDismissArea: {
    ...StyleSheet.absoluteFillObject,
  },
  modalCard: {
    backgroundColor: '#fff',
    borderTopLeftRadius: 28,
    borderTopRightRadius: 28,
    padding: 20,
  },
  modalHandle: {
    width: 44,
    height: 5,
    borderRadius: 999,
    backgroundColor: '#d1d5db',
    alignSelf: 'center',
    marginBottom: 16,
  },
  modalTitle: {
    fontSize: 20,
    fontWeight: '800',
    color: '#111',
  },
  modalSubtitle: {
    marginTop: 6,
    color: '#6b7280',
    lineHeight: 20,
  },
  authSwitchRow: {
    marginTop: 16,
    backgroundColor: '#f3f4f6',
    borderRadius: 16,
    padding: 4,
    flexDirection: 'row',
  },
  authSwitch: {
    flex: 1,
    borderRadius: 12,
    paddingVertical: 12,
    alignItems: 'center',
  },
  authSwitchActive: {
    backgroundColor: '#fff',
  },
  authSwitchText: {
    color: '#6b7280',
    fontWeight: '700',
  },
  authSwitchTextActive: {
    color: '#111',
  },
  input: {
    marginTop: 12,
    height: 52,
    borderRadius: 14,
    borderWidth: 1,
    borderColor: '#e5e7eb',
    paddingHorizontal: 14,
    fontSize: 16,
    color: '#111',
    backgroundColor: '#fff',
  },
});