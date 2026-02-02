import React, { useState } from 'react';
import {
    View,
    StyleSheet,
    ScrollView,
    TouchableOpacity,
    Dimensions,
    StatusBar,
} from 'react-native';
import {
    Text,
    Button,
    Divider,
    Card,
} from 'react-native-paper';
import { useDispatch, useSelector } from 'react-redux';
import Icon from 'react-native-vector-icons/MaterialIcons';
import FastImage from 'react-native-fast-image';
import Swiper from 'react-native-swiper';

import { addToCart, toggleWishlist } from '../../store/slices/productsSlice';
import theme from '../../config/theme';

const { width, height } = Dimensions.get('window');

const ProductDetailScreen = ({ route, navigation }) => {
    const { product } = route.params;
    const [quantity, setQuantity] = useState(1);
    const [selectedImage, setSelectedImage] = useState(0);

    const dispatch = useDispatch();
    const { cart, wishlist } = useSelector(state => state.products);

    const isInWishlist = wishlist.includes(product.id);
    const isInCart = cart.some(c => c.id === product.id);

    const images = product.images || [product.image];

    const handleAddToCart = () => {
        dispatch(addToCart({ ...product, quantity }));
        navigation.navigate('Cart');
    };

    const handleToggleWishlist = () => {
        dispatch(toggleWishlist(product.id));
    };

    const incrementQuantity = () => {
        if (quantity < product.stock) {
            setQuantity(quantity + 1);
        }
    };

    const decrementQuantity = () => {
        if (quantity > 1) {
            setQuantity(quantity - 1);
        }
    };

    const totalPrice = (product.price * quantity).toFixed(2);

    return (
        <View style={styles.container}>
            <StatusBar barStyle="light-content" backgroundColor="transparent" translucent />

            {/* Image Carousel */}
            <View style={styles.imageContainer}>
                <Swiper
                    loop={false}
                    showsPagination={true}
                    paginationStyle={styles.pagination}
                    dotStyle={styles.dot}
                    activeDotStyle={styles.activeDot}
                    onIndexChanged={setSelectedImage}>
                    {images.map((image, index) => (
                        <FastImage
                            key={index}
                            source={{ uri: image }}
                            style={styles.productImage}
                            resizeMode={FastImage.resizeMode.cover}
                        />
                    ))}
                </Swiper>

                {/* Header Overlay */}
                <View style={styles.headerOverlay}>
                    <TouchableOpacity
                        style={styles.backButton}
                        onPress={() => navigation.goBack()}>
                        <Icon name="arrow-back" size={24} color="#FFFFFF" />
                    </TouchableOpacity>

                    <View style={styles.headerActions}>
                        <TouchableOpacity
                            style={styles.iconButton}
                            onPress={() => navigation.navigate('Cart')}>
                            <Icon name="shopping-cart" size={24} color="#FFFFFF" />
                            {cart.length > 0 && (
                                <View style={styles.badge}>
                                    <Text style={styles.badgeText}>{cart.length}</Text>
                                </View>
                            )}
                        </TouchableOpacity>
                    </View>
                </View>

                {/* Wishlist FAB */}
                <TouchableOpacity
                    style={[
                        styles.wishlistFab,
                        isInWishlist && styles.wishlistFabActive,
                    ]}
                    onPress={handleToggleWishlist}>
                    <Icon
                        name={isInWishlist ? 'favorite' : 'favorite-border'}
                        size={24}
                        color={isInWishlist ? '#FFFFFF' : theme.colors.error}
                    />
                </TouchableOpacity>

                {/* Discount Badge */}
                {product.discount > 0 && (
                    <View style={styles.discountBadge}>
                        <Text style={styles.discountText}>{product.discount}% OFF</Text>
                    </View>
                )}
            </View>

            {/* Product Info */}
            <ScrollView style={styles.contentContainer} showsVerticalScrollIndicator={false}>
                {/* Title Section */}
                <View style={styles.titleSection}>
                    <View style={styles.titleRow}>
                        <Text style={styles.productName}>{product.name}</Text>
                    </View>

                    <Text style={styles.productWeight}>{product.weight || '1 unit'}</Text>

                    {/* Rating */}
                    <View style={styles.ratingContainer}>
                        <Icon name="star" size={18} color={theme.colors.warning} />
                        <Text style={styles.rating}>{product.rating || '4.5'}</Text>
                        <Text style={styles.reviews}>({product.reviews || '100'} reviews)</Text>
                    </View>
                </View>

                <Divider style={styles.divider} />

                {/* Price Section */}
                <View style={styles.priceSection}>
                    <View style={styles.priceRow}>
                        <Text style={styles.price}>₹{product.price}</Text>
                        {product.originalPrice && (
                            <Text style={styles.originalPrice}>₹{product.originalPrice}</Text>
                        )}
                        {product.discount > 0 && (
                            <View style={styles.savingsBadge}>
                                <Text style={styles.savingsText}>
                                    Save ₹{(product.originalPrice - product.price).toFixed(2)}
                                </Text>
                            </View>
                        )}
                    </View>

                    {product.stock > 0 ? (
                        <View style={styles.stockContainer}>
                            <Icon name="check-circle" size={16} color={theme.colors.success} />
                            <Text style={styles.stockText}>In Stock ({product.stock} available)</Text>
                        </View>
                    ) : (
                        <View style={styles.stockContainer}>
                            <Icon name="cancel" size={16} color={theme.colors.error} />
                            <Text style={[styles.stockText, styles.outOfStock]}>Out of Stock</Text>
                        </View>
                    )}
                </View>

                <Divider style={styles.divider} />

                {/* Quantity Selector */}
                {product.stock > 0 && (
                    <>
                        <View style={styles.quantitySection}>
                            <Text style={styles.sectionTitle}>Quantity</Text>
                            <View style={styles.quantitySelector}>
                                <TouchableOpacity
                                    style={[
                                        styles.quantityButton,
                                        quantity === 1 && styles.quantityButtonDisabled,
                                    ]}
                                    onPress={decrementQuantity}
                                    disabled={quantity === 1}>
                                    <Icon name="remove" size={20} color={theme.colors.text} />
                                </TouchableOpacity>

                                <Text style={styles.quantityText}>{quantity}</Text>

                                <TouchableOpacity
                                    style={[
                                        styles.quantityButton,
                                        quantity >= product.stock && styles.quantityButtonDisabled,
                                    ]}
                                    onPress={incrementQuantity}
                                    disabled={quantity >= product.stock}>
                                    <Icon name="add" size={20} color={theme.colors.text} />
                                </TouchableOpacity>
                            </View>
                        </View>
                        <Divider style={styles.divider} />
                    </>
                )}

                {/* Description */}
                <View style={styles.descriptionSection}>
                    <Text style={styles.sectionTitle}>Product Description</Text>
                    <Text style={styles.description}>
                        {product.description ||
                            'Premium quality product sourced from trusted suppliers. This product is fresh, safe, and carefully packaged for your convenience.'}
                    </Text>
                </View>

                <Divider style={styles.divider} />

                {/* Features */}
                <View style={styles.featuresSection}>
                    <Text style={styles.sectionTitle}>Features</Text>
                    {(product.features || [
                        '100% Fresh and Natural',
                        'Carefully Packaged',
                        'Fast Delivery in 10 Minutes',
                        'Quality Guaranteed',
                    ]).map((feature, index) => (
                        <View key={index} style={styles.featureItem}>
                            <Icon name="check-circle" size={16} color={theme.colors.success} />
                            <Text style={styles.featureText}>{feature}</Text>
                        </View>
                    ))}
                </View>

                <Divider style={styles.divider} />

                {/* Delivery Info */}
                <Card style={styles.deliveryCard}>
                    <Card.Content>
                        <View style={styles.deliveryItem}>
                            <Icon name="local-shipping" size={24} color={theme.colors.primary} />
                            <View style={styles.deliveryInfo}>
                                <Text style={styles.deliveryTitle}>Express Delivery</Text>
                                <Text style={styles.deliveryText}>Get it in 10 minutes</Text>
                            </View>
                        </View>

                        <View style={styles.deliveryItem}>
                            <Icon name="autorenew" size={24} color={theme.colors.primary} />
                            <View style={styles.deliveryInfo}>
                                <Text style={styles.deliveryTitle}>Easy Returns</Text>
                                <Text style={styles.deliveryText}>7 days return policy</Text>
                            </View>
                        </View>
                    </Card.Content>
                </Card>

                {/* Bottom Spacing */}
                <View style={{ height: 100 }} />
            </ScrollView>

            {/* Bottom Bar */}
            {product.stock > 0 && (
                <View style={styles.bottomBar}>
                    <View style={styles.totalSection}>
                        <Text style={styles.totalLabel}>Total Amount</Text>
                        <Text style={styles.totalAmount}>₹{totalPrice}</Text>
                    </View>

                    <Button
                        mode="contained"
                        onPress={handleAddToCart}
                        style={styles.addToCartButton}
                        contentStyle={styles.buttonContent}
                        icon={isInCart ? 'check' : 'cart-plus'}>
                        {isInCart ? 'Added to Cart' : 'Add to Cart'}
                    </Button>
                </View>
            )}
        </View>
    );
};

const styles = StyleSheet.create({
    container: {
        flex: 1,
        backgroundColor: '#FFFFFF',
    },
    imageContainer: {
        height: height * 0.4,
        position: 'relative',
    },
    productImage: {
        width: width,
        height: height * 0.4,
    },
    pagination: {
        bottom: 10,
    },
    dot: {
        backgroundColor: 'rgba(255, 255, 255, 0.5)',
    },
    activeDot: {
        backgroundColor: '#FFFFFF',
    },
    headerOverlay: {
        position: 'absolute',
        top: 0,
        left: 0,
        right: 0,
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        paddingTop: 40,
        paddingHorizontal: 16,
        paddingBottom: 12,
        backgroundColor: 'rgba(0, 0, 0, 0.3)',
    },
    backButton: {
        backgroundColor: 'rgba(0, 0, 0, 0.3)',
        borderRadius: 20,
        padding: 8,
    },
    headerActions: {
        flexDirection: 'row',
    },
    iconButton: {
        backgroundColor: 'rgba(0, 0, 0, 0.3)',
        borderRadius: 20,
        padding: 8,
        marginLeft: 8,
        position: 'relative',
    },
    badge: {
        position: 'absolute',
        top: 0,
        right: 0,
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
    wishlistFab: {
        position: 'absolute',
        bottom: 20,
        right: 20,
        width: 56,
        height: 56,
        borderRadius: 28,
        backgroundColor: '#FFFFFF',
        justifyContent: 'center',
        alignItems: 'center',
        elevation: 4,
        shadowColor: '#000',
        shadowOffset: { width: 0, height: 2 },
        shadowOpacity: 0.25,
        shadowRadius: 4,
    },
    wishlistFabActive: {
        backgroundColor: theme.colors.error,
    },
    discountBadge: {
        position: 'absolute',
        top: 60,
        left: 16,
        backgroundColor: theme.colors.error,
        borderRadius: 6,
        paddingHorizontal: 12,
        paddingVertical: 6,
    },
    discountText: {
        color: '#FFFFFF',
        fontSize: 12,
        fontWeight: 'bold',
    },
    contentContainer: {
        flex: 1,
    },
    titleSection: {
        padding: 16,
    },
    titleRow: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'flex-start',
    },
    productName: {
        fontSize: 22,
        fontWeight: 'bold',
        color: theme.colors.text,
        flex: 1,
    },
    productWeight: {
        fontSize: 14,
        color: theme.colors.darkGray,
        marginTop: 4,
    },
    ratingContainer: {
        flexDirection: 'row',
        alignItems: 'center',
        marginTop: 8,
    },
    rating: {
        fontSize: 14,
        fontWeight: '600',
        color: theme.colors.text,
        marginLeft: 4,
    },
    reviews: {
        fontSize: 13,
        color: theme.colors.darkGray,
        marginLeft: 4,
    },
    divider: {
        marginHorizontal: 16,
    },
    priceSection: {
        padding: 16,
    },
    priceRow: {
        flexDirection: 'row',
        alignItems: 'center',
        marginBottom: 8,
    },
    price: {
        fontSize: 28,
        fontWeight: 'bold',
        color: theme.colors.primary,
    },
    originalPrice: {
        fontSize: 18,
        color: theme.colors.darkGray,
        textDecorationLine: 'line-through',
        marginLeft: 12,
    },
    savingsBadge: {
        backgroundColor: theme.colors.success,
        borderRadius: 4,
        paddingHorizontal: 8,
        paddingVertical: 4,
        marginLeft: 8,
    },
    savingsText: {
        color: '#FFFFFF',
        fontSize: 12,
        fontWeight: '600',
    },
    stockContainer: {
        flexDirection: 'row',
        alignItems: 'center',
    },
    stockText: {
        fontSize: 14,
        color: theme.colors.success,
        marginLeft: 6,
        fontWeight: '500',
    },
    outOfStock: {
        color: theme.colors.error,
    },
    quantitySection: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        padding: 16,
    },
    sectionTitle: {
        fontSize: 16,
        fontWeight: '600',
        color: theme.colors.text,
    },
    quantitySelector: {
        flexDirection: 'row',
        alignItems: 'center',
    },
    quantityButton: {
        width: 36,
        height: 36,
        borderRadius: 18,
        backgroundColor: '#F5F5F5',
        justifyContent: 'center',
        alignItems: 'center',
    },
    quantityButtonDisabled: {
        opacity: 0.4,
    },
    quantityText: {
        fontSize: 18,
        fontWeight: '600',
        color: theme.colors.text,
        marginHorizontal: 20,
    },
    descriptionSection: {
        padding: 16,
    },
    description: {
        fontSize: 14,
        color: theme.colors.darkGray,
        lineHeight: 22,
        marginTop: 8,
    },
    featuresSection: {
        padding: 16,
    },
    featureItem: {
        flexDirection: 'row',
        alignItems: 'center',
        marginTop: 12,
    },
    featureText: {
        fontSize: 14,
        color: theme.colors.text,
        marginLeft: 10,
    },
    deliveryCard: {
        margin: 16,
        elevation: 2,
    },
    deliveryItem: {
        flexDirection: 'row',
        alignItems: 'center',
        marginVertical: 8,
    },
    deliveryInfo: {
        marginLeft: 12,
    },
    deliveryTitle: {
        fontSize: 14,
        fontWeight: '600',
        color: theme.colors.text,
    },
    deliveryText: {
        fontSize: 12,
        color: theme.colors.darkGray,
        marginTop: 2,
    },
    bottomBar: {
        position: 'absolute',
        bottom: 0,
        left: 0,
        right: 0,
        backgroundColor: '#FFFFFF',
        borderTopWidth: 1,
        borderTopColor: theme.colors.border,
        padding: 16,
        paddingBottom: 20,
        elevation: 8,
    },
    totalSection: {
        marginBottom: 12,
    },
    totalLabel: {
        fontSize: 12,
        color: theme.colors.darkGray,
    },
    totalAmount: {
        fontSize: 24,
        fontWeight: 'bold',
        color: theme.colors.primary,
    },
    addToCartButton: {
        borderRadius: 8,
    },
    buttonContent: {
        paddingVertical: 8,
    },
});

export default ProductDetailScreen;
