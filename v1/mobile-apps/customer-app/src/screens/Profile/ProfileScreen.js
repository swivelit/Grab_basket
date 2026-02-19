import React from 'react';
import {
    View,
    StyleSheet,
    ScrollView,
    TouchableOpacity,
    Image,
    StatusBar,
} from 'react-native';
import {
    Text,
    Card,
    List,
    Divider,
    Avatar,
    Button,
} from 'react-native-paper';
import { useDispatch, useSelector } from 'react-redux';
import Icon from 'react-native-vector-icons/MaterialIcons';
import LinearGradient from 'react-native-linear-gradient';

import { logout } from '../../store/slices/authSlice';
import theme from '../../config/theme';

const ProfileScreen = ({ navigation }) => {
    const dispatch = useDispatch();
    const { user } = useSelector(state => state.auth);
    const { orders } = useSelector(state => state.orders || { orders: [] });

    const handleLogout = () => {
        dispatch(logout());
    };

    const menuItems = [
        {
            title: 'My Orders',
            icon: 'receipt-long',
            route: 'Orders',
            badge: orders.length,
        },
        {
            title: 'Wishlist',
            icon: 'favorite-border',
            route: 'Wishlist',
        },
        {
            title: 'Saved Addresses',
            icon: 'location-on',
            route: 'Addresses',
        },
        {
            title: 'Payment Methods',
            icon: 'payment',
            route: 'PaymentMethods',
        },
        {
            title: 'Notifications',
            icon: 'notifications',
            route: 'Notifications',
        },
        {
            title: 'Help & Support',
            icon: 'help-outline',
            route: 'Support',
        },
        {
            title: 'Settings',
            icon: 'settings',
            route: 'Settings',
        },
    ];

    return (
        <View style={styles.container}>
            <StatusBar barStyle="light-content" backgroundColor={theme.colors.primary} />

            {/* Header with Gradient */}
            <LinearGradient
                colors={[theme.colors.primary, theme.colors.accent]}
                style={styles.header}
                start={{ x: 0, y: 0 }}
                end={{ x: 1, y: 1 }}>
                <View style={styles.headerContent}>
                    {/* Profile Picture */}
                    <TouchableOpacity
                        style={styles.avatarContainer}
                        onPress={() => navigation.navigate('EditProfile')}>
                        {user?.profilePicture ? (
                            <Avatar.Image
                                source={{ uri: user.profilePicture }}
                                size={80}
                            />
                        ) : (
                            <Avatar.Text
                                label={user?.name?.substring(0, 2).toUpperCase() || 'GB'}
                                size={80}
                                style={styles.avatar}
                            />
                        )}
                        <View style={styles.editBadge}>
                            <Icon name="edit" size={16} color="#FFFFFF" />
                        </View>
                    </TouchableOpacity>

                    {/* User Info */}
                    <Text style={styles.userName}>{user?.name || 'Guest User'}</Text>
                    <Text style={styles.userEmail}>{user?.email || user?.phone || ''}</Text>

                    {/* Stats */}
                    <View style={styles.statsContainer}>
                        <View style={styles.statItem}>
                            <Text style={styles.statValue}>{orders.length}</Text>
                            <Text style={styles.statLabel}>Orders</Text>
                        </View>
                        <View style={styles.statDivider} />
                        <View style={styles.statItem}>
                            <Text style={styles.statValue}>₹0</Text>
                            <Text style={styles.statLabel}>Wallet</Text>
                        </View>
                        <View style={styles.statDivider} />
                        <View style={styles.statItem}>
                            <Text style={styles.statValue}>0</Text>
                            <Text style={styles.statLabel}>Rewards</Text>
                        </View>
                    </View>
                </View>
            </LinearGradient>

            {/* Menu Items */}
            <ScrollView style={styles.menu} showsVerticalScrollIndicator={false}>
                <Card style={styles.menuCard}>
                    {menuItems.map((item, index) => (
                        <React.Fragment key={item.title}>
                            <List.Item
                                title={item.title}
                                left={props => (
                                    <View style={styles.iconContainer}>
                                        <Icon name={item.icon} size={24} color={theme.colors.primary} />
                                    </View>
                                )}
                                right={props => (
                                    <View style={styles.rightContent}>
                                        {item.badge !== undefined && item.badge > 0 && (
                                            <View style={styles.badge}>
                                                <Text style={styles.badgeText}>{item.badge}</Text>
                                            </View>
                                        )}
                                        <Icon name="chevron-right" size={24} color={theme.colors.darkGray} />
                                    </View>
                                )}
                                onPress={() => navigation.navigate(item.route)}
                                style={styles.menuItem}
                            />
                            {index < menuItems.length - 1 && <Divider />}
                        </React.Fragment>
                    ))}
                </Card>

                {/* About Section */}
                <Card style={styles.aboutCard}>
                    <Card.Content>
                        <Text style={styles.aboutTitle}>About GrabBaskets</Text>
                        <Text style={styles.aboutText}>
                            Get fresh groceries delivered to your doorstep in just 10 minutes!
                        </Text>
                        <View style={styles.aboutLinks}>
                            <TouchableOpacity style={styles.linkButton}>
                                <Text style={styles.linkText}>Terms & Conditions</Text>
                            </TouchableOpacity>
                            <TouchableOpacity style={styles.linkButton}>
                                <Text style={styles.linkText}>Privacy Policy</Text>
                            </TouchableOpacity>
                            <TouchableOpacity style={styles.linkButton}>
                                <Text style={styles.linkText}>About Us</Text>
                            </TouchableOpacity>
                        </View>
                        <Text style={styles.version}>Version 1.0.0</Text>
                    </Card.Content>
                </Card>

                {/* Logout Button */}
                <Button
                    mode="outlined"
                    onPress={handleLogout}
                    style={styles.logoutButton}
                    contentStyle={styles.logoutButtonContent}
                    textColor={theme.colors.error}
                    icon="logout">
                    Logout
                </Button>

                <View style={{ height: 40 }} />
            </ScrollView>
        </View>
    );
};

const styles = StyleSheet.create({
    container: {
        flex: 1,
        backgroundColor: '#F5F5F5',
    },
    header: {
        paddingTop: 40,
        paddingBottom: 30,
    },
    headerContent: {
        alignItems: 'center',
        paddingHorizontal: 20,
    },
    avatarContainer: {
        position: 'relative',
        marginBottom: 16,
    },
    avatar: {
        backgroundColor: 'rgba(255, 255, 255, 0.3)',
    },
    editBadge: {
        position: 'absolute',
        bottom: 0,
        right: 0,
        backgroundColor: theme.colors.primary,
        borderRadius: 16,
        width: 32,
        height: 32,
        justifyContent: 'center',
        alignItems: 'center',
        borderWidth: 3,
        borderColor: '#FFFFFF',
    },
    userName: {
        fontSize: 22,
        fontWeight: 'bold',
        color: '#FFFFFF',
        marginBottom: 4,
    },
    userEmail: {
        fontSize: 14,
        color: 'rgba(255, 255, 255, 0.9)',
        marginBottom: 20,
    },
    statsContainer: {
        flexDirection: 'row',
        alignItems: 'center',
        backgroundColor: 'rgba(255, 255, 255, 0.2)',
        borderRadius: 12,
        paddingVertical: 16,
        paddingHorizontal: 20,
    },
    statItem: {
        flex: 1,
        alignItems: 'center',
    },
    statValue: {
        fontSize: 20,
        fontWeight: 'bold',
        color: '#FFFFFF',
    },
    statLabel: {
        fontSize: 12,
        color: 'rgba(255, 255, 255, 0.9)',
        marginTop: 4,
    },
    statDivider: {
        width: 1,
        height: 40,
        backgroundColor: 'rgba(255, 255, 255, 0.3)',
    },
    menu: {
        flex: 1,
        marginTop: -20,
    },
    menuCard: {
        marginHorizontal: 16,
        marginBottom: 16,
        borderRadius: 12,
        elevation: 2,
    },
    menuItem: {
        paddingVertical: 4,
    },
    iconContainer: {
        width: 40,
        height: 40,
        borderRadius: 20,
        backgroundColor: `${theme.colors.primary}15`,
        justifyContent: 'center',
        alignItems: 'center',
        marginLeft: 8,
    },
    rightContent: {
        flexDirection: 'row',
        alignItems: 'center',
    },
    badge: {
        backgroundColor: theme.colors.error,
        borderRadius: 12,
        minWidth: 24,
        height: 24,
        justifyContent: 'center',
        alignItems: 'center',
        marginRight: 8,
    },
    badgeText: {
        color: '#FFFFFF',
        fontSize: 12,
        fontWeight: 'bold',
    },
    aboutCard: {
        marginHorizontal: 16,
        marginBottom: 16,
        borderRadius: 12,
        elevation: 2,
    },
    aboutTitle: {
        fontSize: 16,
        fontWeight: '600',
        color: theme.colors.text,
        marginBottom: 8,
    },
    aboutText: {
        fontSize: 14,
        color: theme.colors.darkGray,
        lineHeight: 20,
        marginBottom: 16,
    },
    aboutLinks: {
        marginBottom: 12,
    },
    linkButton: {
        paddingVertical: 8,
    },
    linkText: {
        fontSize: 14,
        color: theme.colors.primary,
        fontWeight: '500',
    },
    version: {
        fontSize: 12,
        color: theme.colors.darkGray,
        textAlign: 'center',
        marginTop: 8,
    },
    logoutButton: {
        marginHorizontal: 16,
        borderRadius: 8,
        borderColor: theme.colors.error,
    },
    logoutButtonContent: {
        paddingVertical: 8,
    },
});

export default ProfileScreen;
