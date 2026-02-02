import React from 'react';
import {
    View,
    StyleSheet,
    FlatList,
    TouchableOpacity,
    Image,
    StatusBar,
} from 'react-native';
import {
    Text,
    Button,
    Card,
    Divider,
    IconButton,
} from 'react-native-paper';
import { useDispatch, useSelector } from 'react-redux';
import Icon from 'react-native-vector-icons/MaterialIcons';
import FastImage from 'react-native-fast-image';

import {
    removeFromCart,
    updateQuantity,
    clearCart,
} from '../../store/slices/productsSlice';
import theme from '../../config/theme';

const CartScreen = ({ navigation }) => {
    const dispatch = useDispatch();
    const { cart } = useSelector(state => state.products);

    const calculateTotal = () => {
        return cart.reduce((total, item) => total + item.price * item.quantity, 0);
    };

    const handleRemoveItem = (itemId) => {
        dispatch(removeFromCart(itemId));
    };

    const handleUpdateQuantity = (itemId, newQuantity) => {
        if (newQuantity > 0) {
            dispatch(updateQuantity({ id: itemId, quantity: newQuantity }));
        } else {
            handleRemoveItem(itemId);
        }
    };

    const handleCheckout = () => {
        if (cart.length > 0) {
            navigation.navigate('Checkout');
        }
    };

    const renderCartItem = ({ item }) => (
        <Card style={styles.cartItem}>
            <View style={styles.itemContent}>
                {/* Product Image */}
                <FastImage
                    source={{ uri: item.image }}
                    style={styles.itemImage}
                    resizeMode={FastImage.resizeMode.cover}
                />

                {/* Product Info */}
                <View style={styles.itemInfo}>
                    <Text style={styles.itemName} numberOfLines={2}>
                        {item.name}
                    </Text>
                    <Text style={styles.itemWeight}>{item.weight || '1 unit'}</Text>

                    <View style={styles.priceRow}>
                        <Text style={styles.itemPrice}>₹{item.price}</Text>
                        {item.originalPrice && (
                            <Text style={styles.itemOriginalPrice}>₹{item.originalPrice}</Text>
                        )}
                    </View>

                    {/* Quantity Controls */}
                    <View style={styles.quantityControls}>
                        <TouchableOpacity
                            style={styles.quantityButton}
                            onPress={() => handleUpdateQuantity(item.id, item.quantity - 1)}>
                            <Icon name="remove" size={18} color={theme.colors.text} />
                        </TouchableOpacity>

                        <Text style={styles.quantityText}>{item.quantity}</Text>

                        <TouchableOpacity
                            style={styles.quantityButton}
                            onPress={() => handleUpdateQuantity(item.id, item.quantity + 1)}>
                            <Icon name="add" size={18} color={theme.colors.text} />
                        </TouchableOpacity>
                    </View>
                </View>

                {/* Total Price & Delete */}
                <View style={styles.itemActions}>
                    <Text style={styles.itemTotal}>
                        ₹{(item.price * item.quantity).toFixed(2)}
                    </Text>
                    <IconButton
                        icon="delete-outline"
                        iconColor={theme.colors.error}
                        size={24}
                        onPress={() => handleRemoveItem(item.id)}
                    />
                </View>
            </View>
        </Card>
    );

    const subtotal = calculateTotal();
    const deliveryFee = subtotal > 0 ? (subtotal >= 500 ? 0 : 40) : 0;
    const tax = (subtotal * 0.05).toFixed(2);
    const total = (parseFloat(subtotal) + parseFloat(deliveryFee) + parseFloat(tax)).toFixed(2);

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

                <Text style={styles.headerTitle}>My Cart ({cart.length})</Text>

                {cart.length > 0 && (
                    <TouchableOpacity
                        style={styles.clearButton}
                        onPress={() => dispatch(clearCart())}>
                        <Text style={styles.clearButtonText}>Clear All</Text>
                    </TouchableOpacity>
                )}
            </View>

            {cart.length === 0 ? (
                <View style={styles.emptyContainer}>
                    <Icon name="shopping-cart" size={100} color={theme.colors.border} />
                    <Text style={styles.emptyText}>Your cart is empty</Text>
                    <Text style={styles.emptySubtext}>
                        Add products to get started!
                    </Text>
                    <Button
                        mode="contained"
                        onPress={() => navigation.navigate('Home')}
                        style={styles.shopNowButton}>
                        Shop Now
                    </Button>
                </View>
            ) : (
                <>
                    {/* Cart Items */}
                    <FlatList
                        data={cart}
                        renderItem={renderCartItem}
                        keyExtractor={item => item.id.toString()}
                        contentContainerStyle={styles.listContent}
                        showsVerticalScrollIndicator={false}
                    />

                    {/* Delivery Option Card */}
                    <Card style={styles.deliveryCard}>
                        <Card.Content>
                            <View style={styles.deliveryOption}>
                                <View style={styles.deliveryIconContainer}>
                                    <Icon name="bolt" size={24} color={theme.colors.warning} />
                                </View>
                                <View style={styles.deliveryInfo}>
                                    <Text style={styles.deliveryTitle}>⚡ Express Delivery</Text>
                                    <Text style={styles.deliveryText}>Get it in 10 minutes</Text>
                                </View>
                                <Icon name="check-circle" size={24} color={theme.colors.success} />
                            </View>
                        </Card.Content>
                    </Card>

                    {/* Bill Summary */}
                    <Card style={styles.billCard}>
                        <Card.Content>
                            <Text style={styles.billTitle}>Bill Summary</Text>

                            <View style={styles.billRow}>
                                <Text style={styles.billLabel}>Subtotal</Text>
                                <Text style={styles.billValue}>₹{subtotal.toFixed(2)}</Text>
                            </View>

                            <View style={styles.billRow}>
                                <Text style={styles.billLabel}>Delivery Fee</Text>
                                <Text style={styles.billValue}>
                                    {deliveryFee === 0 ? (
                                        <Text style={styles.freeText}>FREE</Text>
                                    ) : (
                                        `₹${deliveryFee}`
                                    )}
                                </Text>
                            </View>

                            <View style={styles.billRow}>
                                <Text style={styles.billLabel}>Tax (5%)</Text>
                                <Text style={styles.billValue}>₹{tax}</Text>
                            </View>

                            {subtotal < 500 && (
                                <Text style={styles.freeDeliveryNote}>
                                    Add ₹{(500 - subtotal).toFixed(2)} more for FREE delivery
                                </Text>
                            )}

                            <Divider style={styles.divider} />

                            <View style={styles.billRow}>
                                <Text style={styles.totalLabel}>Total Amount</Text>
                                <Text style={styles.totalValue}>₹{total}</Text>
                            </View>
                        </Card.Content>
                    </Card>

                    {/* Checkout Button */}
                    <View style={styles.footer}>
                        <Button
                            mode="contained"
                            onPress={handleCheckout}
                            style={styles.checkoutButton}
                            contentStyle={styles.checkoutButtonContent}
                            icon="arrow-right">
                            Proceed to Checkout
                        </Button>
                    </View>
                </>
            )}
        </View>
    );
};

const styles = StyleSheet.create({
    container: {
        flex: 1,
        backgroundColor: '#F5F5F5',
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
    clearButton: {
        paddingHorizontal: 12,
        paddingVertical: 6,
    },
    clearButtonText: {
        color: theme.colors.error,
        fontSize: 14,
        fontWeight: '600',
    },
    emptyContainer: {
        flex: 1,
        justifyContent: 'center',
        alignItems: 'center',
        paddingHorizontal: 40,
    },
    emptyText: {
        fontSize: 20,
        fontWeight: '600',
        color: theme.colors.text,
        marginTop: 20,
    },
    emptySubtext: {
        fontSize: 14,
        color: theme.colors.darkGray,
        textAlign: 'center',
        marginTop: 8,
        marginBottom: 30,
    },
    shopNowButton: {
        borderRadius: 25,
        paddingHorizontal: 20,
    },
    listContent: {
        padding: 16,
    },
    cartItem: {
        marginBottom: 12,
        elevation: 2,
    },
    itemContent: {
        flexDirection: 'row',
        padding: 12,
    },
    itemImage: {
        width: 80,
        height: 80,
        borderRadius: 8,
    },
    itemInfo: {
        flex: 1,
        marginLeft: 12,
    },
    itemName: {
        fontSize: 14,
        fontWeight: '600',
        color: theme.colors.text,
        marginBottom: 4,
    },
    itemWeight: {
        fontSize: 12,
        color: theme.colors.darkGray,
        marginBottom: 6,
    },
    priceRow: {
        flexDirection: 'row',
        alignItems: 'center',
        marginBottom: 8,
    },
    itemPrice: {
        fontSize: 16,
        fontWeight: 'bold',
        color: theme.colors.primary,
        marginRight: 6,
    },
    itemOriginalPrice: {
        fontSize: 12,
        color: theme.colors.darkGray,
        textDecorationLine: 'line-through',
    },
    quantityControls: {
        flexDirection: 'row',
        alignItems: 'center',
    },
    quantityButton: {
        width: 30,
        height: 30,
        borderRadius: 15,
        backgroundColor: '#F5F5F5',
        justifyContent: 'center',
        alignItems: 'center',
    },
    quantityText: {
        fontSize: 16,
        fontWeight: '600',
        color: theme.colors.text,
        marginHorizontal: 16,
    },
    itemActions: {
        alignItems: 'flex-end',
        justifyContent: 'space-between',
    },
    itemTotal: {
        fontSize: 16,
        fontWeight: 'bold',
        color: theme.colors.text,
    },
    deliveryCard: {
        marginHorizontal: 16,
        marginBottom: 12,
        elevation: 2,
    },
    deliveryOption: {
        flexDirection: 'row',
        alignItems: 'center',
    },
    deliveryIconContainer: {
        width: 48,
        height: 48,
        borderRadius: 24,
        backgroundColor: '#FFF3E0',
        justifyContent: 'center',
        alignItems: 'center',
    },
    deliveryInfo: {
        flex: 1,
        marginLeft: 12,
    },
    deliveryTitle: {
        fontSize: 16,
        fontWeight: '600',
        color: theme.colors.text,
    },
    deliveryText: {
        fontSize: 13,
        color: theme.colors.darkGray,
        marginTop: 2,
    },
    billCard: {
        marginHorizontal: 16,
        marginBottom: 12,
        elevation: 2,
    },
    billTitle: {
        fontSize: 16,
        fontWeight: '600',
        color: theme.colors.text,
        marginBottom: 12,
    },
    billRow: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        marginBottom: 10,
    },
    billLabel: {
        fontSize: 14,
        color: theme.colors.darkGray,
    },
    billValue: {
        fontSize: 14,
        fontWeight: '500',
        color: theme.colors.text,
    },
    freeText: {
        color: theme.colors.success,
        fontWeight: '600',
    },
    freeDeliveryNote: {
        fontSize: 12,
        color: theme.colors.primary,
        marginTop: 4,
        marginBottom: 8,
    },
    divider: {
        marginVertical: 10,
    },
    totalLabel: {
        fontSize: 16,
        fontWeight: 'bold',
        color: theme.colors.text,
    },
    totalValue: {
        fontSize: 20,
        fontWeight: 'bold',
        color: theme.colors.primary,
    },
    footer: {
        padding: 16,
        backgroundColor: '#FFFFFF',
        borderTopWidth: 1,
        borderTopColor: theme.colors.border,
    },
    checkoutButton: {
        borderRadius: 8,
    },
    checkoutButtonContent: {
        paddingVertical: 8,
    },
});

export default CartScreen;
