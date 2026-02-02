import React from 'react';
import {createStackNavigator} from '@react-navigation/stack';
import {createBottomTabNavigator} from '@react-navigation/bottom-tabs';
import Icon from 'react-native-vector-icons/MaterialIcons';

// Screens
import HomeScreen from '../screens/Home/HomeScreen';
import CategoryScreen from '../screens/Home/CategoryScreen';
import SearchScreen from '../screens/Home/SearchScreen';
import ProductListScreen from '../screens/Product/ProductListScreen';
import ProductDetailScreen from '../screens/Product/ProductDetailScreen';
import ReviewsScreen from '../screens/Product/ReviewsScreen';
import CartScreen from '../screens/Cart/CartScreen';
import CheckoutScreen from '../screens/Cart/CheckoutScreen';
import PaymentScreen from '../screens/Cart/PaymentScreen';
import OrdersScreen from '../screens/Orders/OrdersScreen';
import OrderDetailScreen from '../screens/Orders/OrderDetailScreen';
import TrackingScreen from '../screens/Orders/TrackingScreen';
import ProfileScreen from '../screens/Profile/ProfileScreen';
import AddressScreen from '../screens/Profile/AddressScreen';
import WishlistScreen from '../screens/Profile/WishlistScreen';

const Stack = createStackNavigator();
const Tab = createBottomTabNavigator();

// Home Stack
const HomeStack = () => (
  <Stack.Navigator>
    <Stack.Screen 
      name="HomeMain" 
      component={HomeScreen} 
      options={{headerShown: false}} 
    />
    <Stack.Screen 
      name="Category" 
      component={CategoryScreen}
      options={{title: 'Categories'}}
    />
    <Stack.Screen 
      name="Search" 
      component={SearchScreen}
      options={{title: 'Search Products'}}
    />
    <Stack.Screen 
      name="ProductList" 
      component={ProductListScreen}
      options={{title: 'Products'}}
    />
    <Stack.Screen 
      name="ProductDetail" 
      component={ProductDetailScreen}
      options={{title: 'Product Details'}}
    />
    <Stack.Screen 
      name="Reviews" 
      component={ReviewsScreen}
      options={{title: 'Reviews'}}
    />
  </Stack.Navigator>
);

// Cart Stack
const CartStack = () => (
  <Stack.Navigator>
    <Stack.Screen 
      name="CartMain" 
      component={CartScreen}
      options={{title: 'My Cart'}}
    />
    <Stack.Screen 
      name="Checkout" 
      component={CheckoutScreen}
      options={{title: 'Checkout'}}
    />
    <Stack.Screen 
      name="Payment" 
      component={PaymentScreen}
      options={{title: 'Payment'}}
    />
  </Stack.Navigator>
);

// Orders Stack
const OrdersStack = () => (
  <Stack.Navigator>
    <Stack.Screen 
      name="OrdersMain" 
      component={OrdersScreen}
      options={{title: 'My Orders'}}
    />
    <Stack.Screen 
      name="OrderDetail" 
      component={OrderDetailScreen}
      options={{title: 'Order Details'}}
    />
    <Stack.Screen 
      name="Tracking" 
      component={TrackingScreen}
      options={{title: 'Track Order'}}
    />
  </Stack.Navigator>
);

// Profile Stack
const ProfileStack = () => (
  <Stack.Navigator>
    <Stack.Screen 
      name="ProfileMain" 
      component={ProfileScreen}
      options={{title: 'Profile'}}
    />
    <Stack.Screen 
      name="Address" 
      component={AddressScreen}
      options={{title: 'Addresses'}}
    />
    <Stack.Screen 
      name="Wishlist" 
      component={WishlistScreen}
      options={{title: 'Wishlist'}}
    />
  </Stack.Navigator>
);

// Main Tab Navigator
const MainNavigator = () => {
  return (
    <Tab.Navigator
      screenOptions={({route}) => ({
        tabBarIcon: ({focused, color, size}) => {
          let iconName;

          switch (route.name) {
            case 'Home':
              iconName = 'home';
              break;
            case 'Cart':
              iconName = 'shopping-cart';
              break;
            case 'Orders':
              iconName = 'receipt';
              break;
            case 'Profile':
              iconName = 'person';
              break;
            default:
              iconName = 'home';
          }

          return <Icon name={iconName} size={size} color={color} />;
        },
        tabBarActiveTintColor: '#FF6B00',
        tabBarInactiveTintColor: 'gray',
        tabBarStyle: {
          backgroundColor: '#fff',
          borderTopWidth: 1,
          borderTopColor: '#e0e0e0',
          height: 60,
          paddingBottom: 5,
        },
        headerShown: false,
      })}>
      <Tab.Screen name="Home" component={HomeStack} />
      <Tab.Screen name="Cart" component={CartStack} />
      <Tab.Screen name="Orders" component={OrdersStack} />
      <Tab.Screen name="Profile" component={ProfileStack} />
    </Tab.Navigator>
  );
};

export default MainNavigator;