import React, { useState, useEffect } from 'react';
import {
    View,
    StyleSheet,
    FlatList,
    TouchableOpacity,
    StatusBar,
} from 'react-native';
import {
    Text,
    Card,
    Chip,
    ActivityIndicator,
    Button,
} from 'react-native-paper';
import { useDispatch, useSelector } from 'react-redux';
import Icon from 'react-native-vector-icons/MaterialIcons';
import moment from 'moment';

import { fetchOrders } from '../../store/slices/ordersSlice';
import theme from '../../config/theme';

const OrdersScreen = ({ navigation }) => {
    const [selectedFilter, setSelectedFilter] = useState('all');
    const dispatch = useDispatch();
    const { orders, loading } = useSelector(state => state.orders || { orders: [], loading: false });

    useEffect(() => {
        dispatch(fetchOrders());
    }, []);

    const filters = [
        { key: 'all', label: 'All Orders' },
        { key: 'pending', label: 'Pending' },
        { key: 'delivered', label: 'Delivered' },
        { key: 'cancelled', label: 'Cancelled' },
    ];

    const getStatusColor = (status) => {
        switch (status?.toLowerCase()) {
            case 'delivered':
                return theme.colors.success;
            case 'pending':
            case 'processing':
                return theme.colors.warning;
            case 'cancelled':
                return theme.colors.error;
            default:
                return theme.colors.primary;
        }
    };

    const getStatusIcon = (status) => {
        switch (status?.toLowerCase()) {
            case 'delivered':
                return 'check-circle';
            case 'pending':
                return 'schedule';
            case 'processing':
                return 'local-shipping';
            case 'cancelled':
                return 'cancel';
            default:
                return 'info';
        }
    };

    const getFilteredOrders = () => {
        if (selectedFilter === 'all') return orders;
        return orders.filter(order =>
            order.status.toLowerCase() === selectedFilter
        );
    };

    const renderOrder = ({ item }) => (
        <Card
            style={styles.orderCard}
            onPress={() => navigation.navigate('OrderDetail', { order: item })}>
            <Card.Content>
                {/* Order Header */}
                <View style={styles.orderHeader}>
                    <View>
                        <Text style={styles.orderId}>Order #{item.id}</Text>
                        <Text style={styles.orderDate}>
                            {moment(item.createdAt).format('MMM DD, YYYY • hh:mm A')}
                        </Text>
                    </View>
                    <Chip
                        mode="outlined"
                        textStyle={{ color: getStatusColor(item.status), fontSize: 12 }}
                        style={{ borderColor: getStatusColor(item.status) }}>
                        {item.status}
                    </Chip>
                </View>

                {/* Order Items */}
                <View style={styles.orderItems}>
                    <Icon name="shopping-basket" size={20} color={theme.colors.darkGray} />
                    <Text style={styles.itemsText}>
                        {item.items?.length || 0} items • ₹{item.total}
                    </Text>
                </View>

                {/* Delivery Status */}
                {item.status.toLowerCase() !== 'cancelled' && (
                    <View style={styles.deliveryStatus}>
                        <Icon
                            name={getStatusIcon(item.status)}
                            size={20}
                            color={getStatusColor(item.status)}
                        />
                        <Text style={[styles.statusText, { color: getStatusColor(item.status) }]}>
                            {item.status === 'Delivered'
                                ? `Delivered on ${moment(item.deliveredAt).format('MMM DD, hh:mm A')}`
                                : item.status === 'Pending'
                                    ? 'Preparing your order...'
                                    : 'Out for delivery'}
                        </Text>
                    </View>
                )}

                {/* Action Buttons */}
                <View style={styles.actions}>
                    {item.status.toLowerCase() === 'delivered' && (
                        <Button
                            mode="text"
                            textColor={theme.colors.primary}
                            onPress={() => navigation.navigate('OrderDetail', { order: item })}>
                            View Details
                        </Button>
                    )}
                    {item.status.toLowerCase() === 'pending' && (
                        <>
                            <Button
                                mode="text"
                                textColor={theme.colors.primary}
                                onPress={() => navigation.navigate('Tracking', { orderId: item.id })}>
                                Track Order
                            </Button>
                            <Button
                                mode="text"
                                textColor={theme.colors.error}
                                onPress={() => console.log('Cancel order')}>
                                Cancel
                            </Button>
                        </>
                    )}
                    {item.status.toLowerCase() === 'delivered' && (
                        <Button
                            mode="contained"
                            compact
                            onPress={() => console.log('Reorder')}>
                            Reorder
                        </Button>
                    )}
                </View>
            </Card.Content>
        </Card>
    );

    const filteredOrders = getFilteredOrders();

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

                <Text style={styles.headerTitle}>My Orders</Text>

                <TouchableOpacity style={styles.searchButton}>
                    <Icon name="search" size={24} color={theme.colors.text} />
                </TouchableOpacity>
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

            {/* Orders List */}
            {loading ? (
                <View style={styles.loadingContainer}>
                    <ActivityIndicator size="large" color={theme.colors.primary} />
                    <Text style={styles.loadingText}>Loading orders...</Text>
                </View>
            ) : filteredOrders.length === 0 ? (
                <View style={styles.emptyContainer}>
                    <Icon name="shopping-bag" size={100} color={theme.colors.border} />
                    <Text style={styles.emptyText}>No orders found</Text>
                    <Text style={styles.emptySubtext}>
                        {selectedFilter === 'all'
                            ? 'Start shopping to see your orders here'
                            : `No ${selectedFilter} orders`}
                    </Text>
                    {selectedFilter === 'all' && (
                        <Button
                            mode="contained"
                            onPress={() => navigation.navigate('Home')}
                            style={styles.shopButton}>
                            Start Shopping
                        </Button>
                    )}
                </View>
            ) : (
                <FlatList
                    data={filteredOrders}
                    renderItem={renderOrder}
                    keyExtractor={item => item.id.toString()}
                    contentContainerStyle={styles.listContent}
                    showsVerticalScrollIndicator={false}
                />
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
    searchButton: {
        padding: 8,
    },
    filterContainer: {
        paddingLeft: 16,
        paddingVertical: 12,
        backgroundColor: '#FFFFFF',
        borderBottomWidth: 1,
        borderBottomColor: theme.colors.border,
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
        marginBottom: 30,
    },
    shopButton: {
        borderRadius: 25,
        paddingHorizontal: 20,
    },
    listContent: {
        padding: 16,
    },
    orderCard: {
        marginBottom: 12,
        elevation: 2,
    },
    orderHeader: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'flex-start',
        marginBottom: 12,
    },
    orderId: {
        fontSize: 16,
        fontWeight: '600',
        color: theme.colors.text,
    },
    orderDate: {
        fontSize: 12,
        color: theme.colors.darkGray,
        marginTop: 2,
    },
    orderItems: {
        flexDirection: 'row',
        alignItems: 'center',
        marginBottom: 12,
    },
    itemsText: {
        fontSize: 14,
        color: theme.colors.text,
        marginLeft: 8,
    },
    deliveryStatus: {
        flexDirection: 'row',
        alignItems: 'center',
        backgroundColor: '#F5F5F5',
        borderRadius: 8,
        padding: 12,
        marginBottom: 12,
    },
    statusText: {
        fontSize: 13,
        fontWeight: '500',
        marginLeft: 8,
        flex: 1,
    },
    actions: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
    },
});

export default OrdersScreen;
