import React, { useState, useEffect } from 'react';
import {
    View,
    StyleSheet,
    FlatList,
    TouchableOpacity,
    Image,
    Dimensions,
    StatusBar,
} from 'react-native';
import {
    Text,
    Searchbar,
    Chip,
    ActivityIndicator,
    FAB,
} from 'react-native-paper';
import { useDispatch, useSelector } from 'react-redux';
import Icon from 'react-native-vector-icons/MaterialIcons';
import FastImage from 'react-native-fast-image';

import { fetchProducts, addToCart, toggleWishlist } from '../../store/slices/productsSlice';
import theme from '../../config/theme';

const { width } = Dimensions.get('window');
const ITEM_WIDTH = (width - 48) / 2;

const ProductListScreen = ({ route, navigation }) => {
    const { categoryId, categoryName } = route.params || {};
    const [searchQuery, setSearchQuery] = useState('');
    const [selectedFilter, setSelectedFilter] = useState('all');

    const dispatch = useDispatch();
    const { products, loading, cart, wishlist } = useSelector(state => state.products);

    useEffect(() => {
        dispatch(fetchProducts({ categoryId }));
    }, [categoryId]);

    const filters = [
        { key: 'all', label: 'All' },
        { key: 'popular', label: '🔥 Popular' },
        { key: 'lowPrice', label: '💰 Low Price' },
        { key: 'highPrice', label: '💎 Premium' },
        { key: 'inStock', label: '✅ In Stock' },
    ];

    const getFilteredProducts = () => {
        let filtered = products;

        // Search filter
        if (searchQuery) {
            filtered = filtered.filter(p =>
                p.name.toLowerCase().includes(searchQuery.toLowerCase())
            );
        }

        // Apply selected filter
        switch (selectedFilter) {
            case 'popular':
                filtered = filtered.filter(p => p.popular);
                break;
            case 'lowPrice':
                filtered = [...filtered].sort((a, b) => a.price - b.price);
                break;
            case 'highPrice':
                filtered = [...filtered].sort((a, b) => b.price - a.price);
                break;
            case 'inStock':
                filtered = filtered.filter(p => p.stock > 0);
                break;
        }

        return filtered;
    };

    const handleAddToCart = (product) => {
        dispatch(addToCart(product));
    };

    const handleToggleWishlist = (productId) => {
        dispatch(toggleWishlist(productId));
    };

    const renderProduct = ({ item }) => {
        const isInWishlist = wishlist.includes(item.id);
        const isInCart = cart.some(c => c.id === item.id);

        return (
            <TouchableOpacity
                style={styles.productCard}
                activeOpacity={0.8}
                onPress={() => navigation.navigate('ProductDetail', { product: item })}>
                {/* Product Image */}
                <View style={styles.imageContainer}>
                    <FastImage
                        source={{ uri: item.image, priority: FastImage.priority.normal }}
                        style={styles.productImage}
                        resizeMode={FastImage.resizeMode.cover}
                    />

                    {/* Wishlist Button */}
                    <TouchableOpacity
                        style={styles.wishlistButton}
                        onPress={() => handleToggleWishlist(item.id)}>
                        <Icon
                            name={isInWishlist ? 'favorite' : 'favorite-border'}
                            size={20}
                            color={isInWishlist ? theme.colors.error : theme.colors.darkGray}
                        />
                    </TouchableOpacity>

                    {/* Discount Badge */}
                    {item.discount > 0 && (
                        <View style={styles.discountBadge}>
                            <Text style={styles.discountText}>{item.discount}% OFF</Text>
                        </View>
                    )}
                </View>

                {/* Product Info */}
                <View style={styles.productInfo}>
                    <Text style={styles.productName} numberOfLines={2}>
                        {item.name}
                    </Text>
                    <Text style={styles.productWeight} numberOfLines={1}>
                        {item.weight || '1 unit'}
                    </Text>

                    {/* Price */}
                    <View style={styles.priceContainer}>
                        <Text style={styles.price}>₹{item.price}</Text>
                        {item.originalPrice && (
                            <Text style={styles.originalPrice}>₹{item.originalPrice}</Text>
                        )}
                    </View>

                    {/* Rating */}
                    <View style={styles.ratingContainer}>
                        <Icon name="star" size={14} color={theme.colors.warning} />
                        <Text style={styles.rating}>{item.rating || '4.5'}</Text>
                        <Text style={styles.reviews}>({item.reviews || '100'})</Text>
                    </View>

                    {/* Add to Cart Button */}
                    {item.stock > 0 ? (
                        <TouchableOpacity
                            style={[
                                styles.addButton,
                                isInCart && styles.addButtonInCart,
                            ]}
                            onPress={() => handleAddToCart(item)}>
                            <Icon
                                name={isInCart ? 'check' : 'add-shopping-cart'}
                                size={16}
                                color="#FFFFFF"
                            />
                            <Text style={styles.addButtonText}>
                                {isInCart ? 'In Cart' : 'Add'}
                            </Text>
                        </TouchableOpacity>
                    ) : (
                        <View style={styles.outOfStockButton}>
                            <Text style={styles.outOfStockText}>Out of Stock</Text>
                        </View>
                    )}
                </View>
            </TouchableOpacity>
        );
    };

    const filteredProducts = getFilteredProducts();

    return (
        <View style={styles.container}>
            <StatusBar barStyle="dark-content" backgroundColor="#FFFFFF" />

            {/* Header */}
            <View style={styles.header}>
                <TouchableOpacity
                    style={styles.backButton}
                    onPress={() => navigation.goBack()}>
                    <Icon name="arrow-back" size={24} color={theme.colors.text} />
                </TouchableOpacity>

                <Text style={styles.headerTitle}>{categoryName || 'Products'}</Text>

                <View style={styles.headerActions}>
                    <TouchableOpacity
                        style={styles.headerButton}
                        onPress={() => navigation.navigate('Wishlist')}>
                        <Icon name="favorite-border" size={24} color={theme.colors.text} />
                        {wishlist.length > 0 && (
                            <View style={styles.badge}>
                                <Text style={styles.badgeText}>{wishlist.length}</Text>
                            </View>
                        )}
                    </TouchableOpacity>

                    <TouchableOpacity
                        style={styles.headerButton}
                        onPress={() => navigation.navigate('Cart')}>
                        <Icon name="shopping-cart" size={24} color={theme.colors.text} />
                        {cart.length > 0 && (
                            <View style={styles.badge}>
                                <Text style={styles.badgeText}>{cart.length}</Text>
                            </View>
                        )}
                    </TouchableOpacity>
                </View>
            </View>

            {/* Search Bar */}
            <View style={styles.searchContainer}>
                <Searchbar
                    placeholder="Search products..."
                    onChangeText={setSearchQuery}
                    value={searchQuery}
                    style={styles.searchBar}
                    iconColor={theme.colors.primary}
                />
            </View>

            {/* Filters */}
            <View style={styles.filterContainer}>
                <FlatList
                    horizontal
                    showsHorizontalScrollIndicator={false}
                    data={filters}
                    keyExtractor={item => item.key}
                    renderItem={({ item }) => (
                        <Chip
                            selected={selectedFilter === item.key}
                            onPress={() => setSelectedFilter(item.key)}
                            style={[
                                styles.filterChip,
                                selectedFilter === item.key && styles.filterChipSelected,
                            ]}
                            textStyle={[
                                styles.filterChipText,
                                selectedFilter === item.key && styles.filterChipTextSelected,
                            ]}>
                            {item.label}
                        </Chip>
                    )}
                />
            </View>

            {/* Products List */}
            {loading ? (
                <View style={styles.loadingContainer}>
                    <ActivityIndicator size="large" color={theme.colors.primary} />
                    <Text style={styles.loadingText}>Loading products...</Text>
                </View>
            ) : filteredProducts.length === 0 ? (
                <View style={styles.emptyContainer}>
                    <Icon name="shopping-basket" size={80} color={theme.colors.border} />
                    <Text style={styles.emptyText}>No products found</Text>
                    <Text style={styles.emptySubtext}>
                        {searchQuery
                            ? 'Try adjusting your search'
                            : 'Check back later for new products'}
                    </Text>
                </View>
            ) : (
                <FlatList
                    data={filteredProducts}
                    renderItem={renderProduct}
                    keyExtractor={item => item.id.toString()}
                    numColumns={2}
                    columnWrapperStyle={styles.row}
                    contentContainerStyle={styles.listContent}
                    showsVerticalScrollIndicator={false}
                />
            )}

            {/* Floating Cart FAB */}
            {cart.length > 0 && (
                <FAB
                    icon="shopping-cart"
                    label={`${cart.length} items`}
                    style={styles.fab}
                    onPress={() => navigation.navigate('Cart')}
                    color="#FFFFFF"
                />
            )}
        </View>
    );
};

const styles = StyleSheet.create({
    container: {
        flex: 1,
        backgroundColor: '#FFFFFF',
    },
    header: {
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'space-between',
        paddingHorizontal: 16,
        paddingVertical: 12,
        backgroundColor: '#FFFFFF',
        borderBottomWidth: 1,
        borderBottomColor: theme.colors.border,
    },
    backButton: {
        padding: 8,
    },
    headerTitle: {
        flex: 1,
        fontSize: 18,
        fontWeight: 'bold',
        color: theme.colors.text,
        marginLeft: 12,
    },
    headerActions: {
        flexDirection: 'row',
    },
    headerButton: {
        padding: 8,
        marginLeft: 8,
        position: 'relative',
    },
    badge: {
        position: 'absolute',
        top: 4,
        right: 4,
        backgroundColor: theme.colors.error,
        borderRadius: 10,
        minWidth: 20,
        height: 20,
        justifyContent: 'center',
        alignItems: 'center',
    },
    badgeText: {
        color: '#FFFFFF',
        fontSize: 10,
        fontWeight: 'bold',
    },
    searchContainer: {
        paddingHorizontal: 16,
        paddingVertical: 12,
    },
    searchBar: {
        elevation: 2,
    },
    filterContainer: {
        paddingLeft: 16,
        paddingBottom: 12,
    },
    filterChip: {
        marginRight: 8,
        backgroundColor: '#F5F5F5',
    },
    filterChipSelected: {
        backgroundColor: theme.colors.primary,
    },
    filterChipText: {
        color: theme.colors.text,
    },
    filterChipTextSelected: {
        color: '#FFFFFF',
    },
    loadingContainer: {
        flex: 1,
        justifyContent: 'center',
        alignItems: 'center',
    },
    loadingText: {
        marginTop: 12,
        fontSize: 14,
        color: theme.colors.darkGray,
    },
    emptyContainer: {
        flex: 1,
        justifyContent: 'center',
        alignItems: 'center',
        paddingHorizontal: 40,
    },
    emptyText: {
        fontSize: 18,
        fontWeight: '600',
        color: theme.colors.text,
        marginTop: 16,
    },
    emptySubtext: {
        fontSize: 14,
        color: theme.colors.darkGray,
        textAlign: 'center',
        marginTop: 8,
    },
    listContent: {
        padding: 16,
    },
    row: {
        justifyContent: 'space-between',
    },
    productCard: {
        width: ITEM_WIDTH,
        backgroundColor: '#FFFFFF',
        borderRadius: 12,
        marginBottom: 16,
        elevation: 3,
        shadowColor: '#000',
        shadowOffset: { width: 0, height: 2 },
        shadowOpacity: 0.1,
        shadowRadius: 4,
    },
    imageContainer: {
        position: 'relative',
    },
    productImage: {
        width: ITEM_WIDTH,
        height: ITEM_WIDTH,
        borderTopLeftRadius: 12,
        borderTopRightRadius: 12,
    },
    wishlistButton: {
        position: 'absolute',
        top: 8,
        right: 8,
        backgroundColor: '#FFFFFF',
        borderRadius: 20,
        padding: 6,
        elevation: 2,
    },
    discountBadge: {
        position: 'absolute',
        top: 8,
        left: 8,
        backgroundColor: theme.colors.error,
        borderRadius: 4,
        paddingHorizontal: 8,
        paddingVertical: 4,
    },
    discountText: {
        color: '#FFFFFF',
        fontSize: 10,
        fontWeight: 'bold',
    },
    productInfo: {
        padding: 12,
    },
    productName: {
        fontSize: 14,
        fontWeight: '600',
        color: theme.colors.text,
        marginBottom: 4,
    },
    productWeight: {
        fontSize: 12,
        color: theme.colors.darkGray,
        marginBottom: 8,
    },
    priceContainer: {
        flexDirection: 'row',
        alignItems: 'center',
        marginBottom: 6,
    },
    price: {
        fontSize: 16,
        fontWeight: 'bold',
        color: theme.colors.primary,
        marginRight: 6,
    },
    originalPrice: {
        fontSize: 12,
        color: theme.colors.darkGray,
        textDecorationLine: 'line-through',
    },
    ratingContainer: {
        flexDirection: 'row',
        alignItems: 'center',
        marginBottom: 10,
    },
    rating: {
        fontSize: 12,
        fontWeight: '600',
        color: theme.colors.text,
        marginLeft: 4,
    },
    reviews: {
        fontSize: 11,
        color: theme.colors.darkGray,
        marginLeft: 4,
    },
    addButton: {
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'center',
        backgroundColor: theme.colors.primary,
        borderRadius: 6,
        paddingVertical: 8,
    },
    addButtonInCart: {
        backgroundColor: theme.colors.success,
    },
    addButtonText: {
        color: '#FFFFFF',
        fontSize: 12,
        fontWeight: '600',
        marginLeft: 4,
    },
    outOfStockButton: {
        backgroundColor: '#F5F5F5',
        borderRadius: 6,
        paddingVertical: 8,
        alignItems: 'center',
    },
    outOfStockText: {
        color: theme.colors.darkGray,
        fontSize: 12,
        fontWeight: '600',
    },
    fab: {
        position: 'absolute',
        bottom: 20,
        right: 20,
        backgroundColor: theme.colors.primary,
    },
});

export default ProductListScreen;
