import React, {useEffect, useState} from 'react';
import {
  View,
  StyleSheet,
  ScrollView,
  RefreshControl,
  Dimensions,
  TouchableOpacity,
  FlatList,
} from 'react-native';
import {
  Text,
  Searchbar,
  Card,
  Badge,
  ActivityIndicator,
} from 'react-native-paper';
import {useDispatch, useSelector} from 'react-redux';
import LinearGradient from 'react-native-linear-gradient';
import FastImage from 'react-native-fast-image';
import Icon from 'react-native-vector-icons/MaterialIcons';

import {fetchCategories, fetchProducts} from '../../store/slices/productSlice';
import {fetchCart} from '../../store/slices/cartSlice';
import theme from '../../config/theme';
import ProductCard from '../../components/ProductCard';
import CategoryCard from '../../components/CategoryCard';
import BannerSlider from '../../components/BannerSlider';

const {width} = Dimensions.get('window');

const HomeScreen = ({navigation}) => {
  const [searchQuery, setSearchQuery] = useState('');
  const [refreshing, setRefreshing] = useState(false);

  const dispatch = useDispatch();
  const {user} = useSelector(state => state.auth);
  const {categories, categoryLoading} = useSelector(state => state.products);
  const {items: products, loading: productsLoading} = useSelector(state => state.products);
  const {itemCount} = useSelector(state => state.cart);

  useEffect(() => {
    loadData();
  }, []);

  const loadData = () => {
    dispatch(fetchCategories());
    dispatch(fetchProducts({page: 1, limit: 10}));
    dispatch(fetchCart());
  };

  const onRefresh = () => {
    setRefreshing(true);
    loadData();
    setTimeout(() => setRefreshing(false), 1000);
  };

  const handleSearch = () => {
    if (searchQuery.trim()) {
      navigation.navigate('Search', {query: searchQuery});
    }
  };

  const renderCategory = ({item}) => (
    <TouchableOpacity
      style={styles.categoryItem}
      onPress={() => navigation.navigate('ProductList', {categoryId: item.id, categoryName: item.name})}>
      <View style={styles.categoryIcon}>
        <Text style={styles.categoryEmoji}>{item.emoji || '🛒'}</Text>
      </View>
      <Text style={styles.categoryName}>{item.name}</Text>
    </TouchableOpacity>
  );

  const renderProduct = ({item}) => (
    <ProductCard
      product={item}
      onPress={() => navigation.navigate('ProductDetail', {productId: item.id})}
      style={styles.productCard}
    />
  );

  return (
    <View style={styles.container}>
      {/* Header */}
      <LinearGradient
        colors={[theme.colors.primary, theme.colors.accent]}
        style={styles.header}>
        <View style={styles.headerTop}>
          <View style={styles.locationContainer}>
            <Icon name="location-on" size={20} color="#FFFFFF" />
            <View style={styles.locationText}>
              <Text style={styles.deliveryText}>Deliver to</Text>
              <Text style={styles.locationName}>Current Location</Text>
            </View>
          </View>
          
          <TouchableOpacity
            style={styles.cartButton}
            onPress={() => navigation.navigate('Cart')}>
            <Icon name="shopping-cart" size={24} color="#FFFFFF" />
            {itemCount > 0 && (
              <Badge style={styles.cartBadge}>{itemCount}</Badge>
            )}
          </TouchableOpacity>
        </View>

        <Text style={styles.welcomeText}>
          Hello {user?.name || 'Guest'}! 👋
        </Text>
        <Text style={styles.taglineText}>
          What would you like to order today?
        </Text>

        {/* Search Bar */}
        <Searchbar
          placeholder="Search groceries, brands..."
          value={searchQuery}
          onChangeText={setSearchQuery}
          onSubmitEditing={handleSearch}
          style={styles.searchBar}
          inputStyle={styles.searchInput}
          iconColor={theme.colors.primary}
        />
      </LinearGradient>

      <ScrollView
        style={styles.content}
        showsVerticalScrollIndicator={false}
        refreshControl={
          <RefreshControl refreshing={refreshing} onRefresh={onRefresh} />
        }>
        
        {/* Quick Delivery Banner */}
        <Card style={styles.quickDeliveryCard}>
          <LinearGradient
            colors={['#FF6B6B', '#FF8E53']}
            style={styles.quickDeliveryGradient}
            start={{x: 0, y: 0}}
            end={{x: 1, y: 0}}>
            <View style={styles.quickDeliveryContent}>
              <View>
                <Text style={styles.quickDeliveryTitle}>⚡ 10-Minute Delivery</Text>
                <Text style={styles.quickDeliverySubtitle}>
                  Get your essentials delivered super fast!
                </Text>
              </View>
              <Icon name="flash-on" size={40} color="#FFFFFF" />
            </View>
          </LinearGradient>
        </Card>

        {/* Categories Section */}
        <View style={styles.section}>
          <View style={styles.sectionHeader}>
            <Text style={styles.sectionTitle}>Shop by Category</Text>
            <TouchableOpacity onPress={() => navigation.navigate('Category')}>
              <Text style={styles.seeAllText}>See All</Text>
            </TouchableOpacity>
          </View>

          {categoryLoading ? (
            <ActivityIndicator size="large" color={theme.colors.primary} />
          ) : (
            <FlatList
              data={categories.slice(0, 8)}
              renderItem={renderCategory}
              keyExtractor={item => item.id.toString()}
              numColumns={4}
              scrollEnabled={false}
              contentContainerStyle={styles.categoriesContainer}
            />
          )}
        </View>

        {/* Featured Products */}
        <View style={styles.section}>
          <View style={styles.sectionHeader}>
            <Text style={styles.sectionTitle}>Featured Products</Text>
            <TouchableOpacity onPress={() => navigation.navigate('ProductList', {featured: true})}>
              <Text style={styles.seeAllText}>See All</Text>
            </TouchableOpacity>
          </View>

          {productsLoading ? (
            <ActivityIndicator size="large" color={theme.colors.primary} />
          ) : (
            <FlatList
              data={products.slice(0, 6)}
              renderItem={renderProduct}
              keyExtractor={item => item.id.toString()}
              horizontal
              showsHorizontalScrollIndicator={false}
              contentContainerStyle={styles.productsContainer}
            />
          )}
        </View>

        {/* Promotional Banner */}
        <Card style={styles.promoCard}>
          <FastImage
            source={{uri: 'https://via.placeholder.com/350x150/FF6B00/FFFFFF?text=Special+Offers'}}
            style={styles.promoImage}
            resizeMode={FastImage.resizeMode.cover}
          />
          <View style={styles.promoOverlay}>
            <Text style={styles.promoTitle}>Special Offers</Text>
            <Text style={styles.promoSubtitle}>Up to 50% off on selected items</Text>
          </View>
        </Card>

        {/* Popular Products */}
        <View style={styles.section}>
          <View style={styles.sectionHeader}>
            <Text style={styles.sectionTitle}>Popular This Week</Text>
            <TouchableOpacity onPress={() => navigation.navigate('ProductList', {popular: true})}>
              <Text style={styles.seeAllText}>See All</Text>
            </TouchableOpacity>
          </View>

          <FlatList
            data={products.slice(0, 4)}
            renderItem={renderProduct}
            keyExtractor={item => `popular-${item.id}`}
            numColumns={2}
            scrollEnabled={false}
            contentContainerStyle={styles.popularProductsContainer}
          />
        </View>

        <View style={styles.bottomPadding} />
      </ScrollView>
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#FFFFFF',
  },
  header: {
    paddingTop: 50,
    paddingHorizontal: 20,
    paddingBottom: 20,
    borderBottomLeftRadius: 25,
    borderBottomRightRadius: 25,
  },
  headerTop: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 20,
  },
  locationContainer: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  locationText: {
    marginLeft: 8,
  },
  deliveryText: {
    fontSize: 12,
    color: 'rgba(255, 255, 255, 0.8)',
  },
  locationName: {
    fontSize: 14,
    color: '#FFFFFF',
    fontWeight: '500',
  },
  cartButton: {
    position: 'relative',
  },
  cartBadge: {
    position: 'absolute',
    top: -8,
    right: -8,
    backgroundColor: '#FF3B30',
  },
  welcomeText: {
    fontSize: 24,
    fontWeight: 'bold',
    color: '#FFFFFF',
    marginBottom: 4,
  },
  taglineText: {
    fontSize: 16,
    color: 'rgba(255, 255, 255, 0.8)',
    marginBottom: 20,
  },
  searchBar: {
    backgroundColor: '#FFFFFF',
    borderRadius: 15,
    elevation: 2,
  },
  searchInput: {
    fontSize: 16,
  },
  content: {
    flex: 1,
    paddingTop: 20,
  },
  quickDeliveryCard: {
    marginHorizontal: 20,
    marginBottom: 20,
    borderRadius: 15,
    overflow: 'hidden',
  },
  quickDeliveryGradient: {
    padding: 20,
  },
  quickDeliveryContent: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  quickDeliveryTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#FFFFFF',
    marginBottom: 4,
  },
  quickDeliverySubtitle: {
    fontSize: 14,
    color: 'rgba(255, 255, 255, 0.9)',
  },
  section: {
    marginBottom: 25,
  },
  sectionHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: 20,
    marginBottom: 15,
  },
  sectionTitle: {
    fontSize: 20,
    fontWeight: 'bold',
    color: theme.colors.text,
  },
  seeAllText: {
    fontSize: 14,
    color: theme.colors.primary,
    fontWeight: '500',
  },
  categoriesContainer: {
    paddingHorizontal: 15,
  },
  categoryItem: {
    flex: 1,
    alignItems: 'center',
    margin: 5,
    padding: 15,
    backgroundColor: theme.colors.lightGray,
    borderRadius: 12,
  },
  categoryIcon: {
    width: 40,
    height: 40,
    borderRadius: 20,
    backgroundColor: '#FFFFFF',
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: 8,
  },
  categoryEmoji: {
    fontSize: 20,
  },
  categoryName: {
    fontSize: 12,
    color: theme.colors.text,
    textAlign: 'center',
    fontWeight: '500',
  },
  productsContainer: {
    paddingHorizontal: 20,
  },
  productCard: {
    width: 150,
    marginRight: 15,
  },
  promoCard: {
    marginHorizontal: 20,
    marginBottom: 25,
    borderRadius: 15,
    overflow: 'hidden',
  },
  promoImage: {
    width: '100%',
    height: 150,
  },
  promoOverlay: {
    position: 'absolute',
    bottom: 0,
    left: 0,
    right: 0,
    backgroundColor: 'rgba(0, 0, 0, 0.5)',
    padding: 15,
  },
  promoTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#FFFFFF',
    marginBottom: 4,
  },
  promoSubtitle: {
    fontSize: 14,
    color: 'rgba(255, 255, 255, 0.9)',
  },
  popularProductsContainer: {
    paddingHorizontal: 15,
  },
  bottomPadding: {
    height: 20,
  },
});

export default HomeScreen;