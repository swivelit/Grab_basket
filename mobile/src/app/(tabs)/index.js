import React, { useMemo, useState } from 'react';
import { Image } from 'expo-image';
import {
  RefreshControl,
  ScrollView,
  StatusBar,
  StyleSheet,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useBottomTabBarHeight } from '@react-navigation/bottom-tabs';
import { Ionicons } from '@expo/vector-icons';
import { useRouter } from 'expo-router';

import { BrandPalette, createShadow } from '@/constants/theme';
import { buildApiUrl } from '../../config';
import { useGrabBasket } from '../../../App';

const BRAND_LOGO = require('../../../assets/images/consumer-native-icon.png');
const PALETTE = BrandPalette;

const SERVICE_TABS = [
  { key: 'food', label: 'Food', icon: 'fast-food-outline' },
  { key: 'warehouse', label: 'Instamart', icon: 'basket-outline' },
  { key: 'eatout', label: 'Dineout', icon: 'restaurant-outline' },
  { key: 'scenes', label: 'Scenes', icon: 'sparkles-outline' },
];

const SERVICE_SKINS = {
  food: {
    page: '#F8F2FF',
    hero: '#2B0550',
    heroCard: '#6E11D8',
    heroMuted: '#C8B0F4',
    heroAccent: '#8B2CFF',
    heroBadge: '#FFD23C',
    textOnHero: '#FFFFFF',
    searchPlaceholder: "Search for 'Cake'",
    etaFallback: '7 mins',
    headline: 'CRAVEATHON',
    subheadline: 'FLAT ₹200 OFF & MORE',
    cta: 'ORDER NOW',
    collectionBg: '#F6D92F',
    bodyCard: '#FFFFFF',
    chipBg: '#F0E7FF',
    chipText: '#58229E',
    softSection: '#EFE1FF',
    badgeBg: '#FFF3C7',
    badgeText: '#8C5A00',
  },
  warehouse: {
    page: '#EEF4FF',
    hero: '#081B4C',
    heroCard: '#0D3D97',
    heroMuted: '#AFC2FF',
    heroAccent: '#1C5DDD',
    heroBadge: '#FFFFFF',
    textOnHero: '#FFFFFF',
    searchPlaceholder: 'Search for Cold drinks',
    etaFallback: '7 mins',
    headline: 'Stock up in minutes',
    subheadline: 'Daily staples, instant snacks & gifting needs',
    cta: 'SHOP NOW',
    collectionBg: '#123D96',
    bodyCard: '#FFFFFF',
    chipBg: '#DDE8FF',
    chipText: '#0D3D97',
    softSection: '#D8E7FF',
    badgeBg: '#E5EEFF',
    badgeText: '#0D3D97',
  },
  eatout: {
    page: '#EAF9F5',
    hero: '#033C37',
    heroCard: '#08A58A',
    heroMuted: '#C3F3E8',
    heroAccent: '#18C7A6',
    heroBadge: '#DFFF55',
    textOnHero: '#FFFFFF',
    searchPlaceholder: 'Search for restaurants',
    etaFallback: 'Discover',
    headline: 'BILL HALF PARTY FULL',
    subheadline: 'Book tables, split bills better and unlock premium perks',
    cta: 'BOOK NOW',
    collectionBg: '#DFFF55',
    bodyCard: '#FFFFFF',
    chipBg: '#D8F7EE',
    chipText: '#096856',
    softSection: '#CFF7EA',
    badgeBg: '#F1FFB5',
    badgeText: '#4D5B00',
  },
  scenes: {
    page: '#FFF4ED',
    hero: '#FF6900',
    heroCard: '#FF7E26',
    heroMuted: '#FFD7BD',
    heroAccent: '#FF9D59',
    heroBadge: '#FFFFFF',
    textOnHero: '#FFFFFF',
    searchPlaceholder: 'Search experiences, events & plans',
    etaFallback: 'Today',
    headline: 'One app for food, grocery, dining and more in mins!',
    subheadline: 'Use Scenes for curated events, group plans and weekend escapes.',
    cta: 'EXPLORE',
    collectionBg: '#FFF1E8',
    bodyCard: '#FFFFFF',
    chipBg: '#FFE6D5',
    chipText: '#B34E00',
    softSection: '#FFF1E8',
    badgeBg: '#FFF1E8',
    badgeText: '#B34E00',
  },
};

const FOOD_FILTERS = [
  { key: 'biryani', label: 'Biryani' },
  { key: 'pizza', label: 'Pizza' },
  { key: 'cake', label: 'Cakes' },
  { key: 'healthy', label: 'Healthy' },
  { key: 'budget', label: 'Budget meals' },
];

const MART_FILTERS = [
  { key: 'all', label: 'All' },
  { key: 'fresh', label: 'Fresh' },
  { key: 'snacks', label: 'Instant snacks' },
  { key: 'gifting', label: 'Gifting' },
  { key: 'daily', label: 'Daily needs' },
];

const DINEOUT_FILTERS = [
  { key: 'offers', label: 'Flat 50% off' },
  { key: 'family', label: 'Family-friendly' },
  { key: 'cafes', label: 'Cafes & quick bites' },
  { key: 'freebies', label: 'Exciting freebies' },
];

const SCENE_FILTERS = [
  { key: 'today', label: 'Today' },
  { key: 'weekend', label: 'Weekend' },
  { key: 'music', label: 'Music' },
  { key: 'comedy', label: 'Comedy' },
  { key: 'workshops', label: 'Workshops' },
];

const FOOD_PROMO_CARDS = [
  { key: 'binge', title: 'BINGE WORTHY', value: 'Flat ₹200', caption: 'OFF & more', icon: 'tv-outline' },
  { key: 'summer', title: 'SUMMER CARNIVAL', value: 'Up to 60%', caption: 'OFF & more', icon: 'sunny-outline' },
  { key: 'eatright', title: 'EATRIGHT', value: 'Win up to ₹300', caption: 'FREE CASH', icon: 'nutrition-outline' },
  { key: 'gift', title: 'GIFT COUPON', value: '₹150', caption: 'partner reward', icon: 'gift-outline' },
];

const MART_COLLECTIONS = [
  { key: 'biryani', title: 'Biryani & feasting', icon: 'restaurant-outline', caption: 'Festive staples' },
  { key: 'snacks', title: 'Instant snacks & drinks', icon: 'wine-outline', caption: 'Quick cravings' },
  { key: 'gifting', title: 'Gifting needs', icon: 'gift-outline', caption: 'Grab and go' },
  { key: 'fruits', title: 'Dates, fruits & more', icon: 'leaf-outline', caption: 'Fresh picks' },
];

const DINEOUT_COLLECTIONS = [
  { key: 'half', title: 'FLAT 50% OFF', icon: 'pricetag-outline', size: 'large' },
  { key: 'hall', title: 'GIRF Hall Of Fame', icon: 'trophy-outline' },
  { key: 'family', title: 'Family-Friendly Spots', icon: 'people-outline' },
  { key: 'cafe', title: 'Cafes & Quick Bites', icon: 'cafe-outline' },
  { key: 'freebies', title: 'Exciting Freebies', icon: 'ice-cream-outline' },
];

const SCENE_EVENTS = [
  {
    id: 'scene-1',
    title: 'Acoustic Rooftop Night',
    subtitle: 'Panampilly Nagar · 7:30 PM',
    tag: 'Music',
    icon: 'musical-notes-outline',
  },
  {
    id: 'scene-2',
    title: 'Comedy Club Friday',
    subtitle: 'Kakkanad · 8:00 PM',
    tag: 'Comedy',
    icon: 'mic-outline',
  },
  {
    id: 'scene-3',
    title: 'Clay & Coffee Workshop',
    subtitle: 'Kadavanthra · 4:00 PM',
    tag: 'Workshop',
    icon: 'color-palette-outline',
  },
];

function money(value) {
  return `₹${Number(value || 0).toFixed(0)}`;
}

function isAbsoluteUrl(value = '') {
  return /^https?:\/\//i.test(String(value || '').trim());
}

function resolveMediaUrl(value = '') {
  const raw = String(value || '').trim();
  if (!raw) return '';
  if (isAbsoluteUrl(raw)) return raw;

  try {
    return buildApiUrl(raw.startsWith('/') ? raw : `/${raw}`);
  } catch {
    return raw;
  }
}

function initials(name = '') {
  return String(name || '')
    .split(' ')
    .filter(Boolean)
    .map((part) => part[0])
    .join('')
    .slice(0, 2)
    .toUpperCase();
}

function getRating(vendor) {
  const value = Number(vendor?.avg_rating);
  if (Number.isFinite(value) && value > 0) return value.toFixed(1);
  return 'New';
}

function getEta(vendor, service) {
  const eta = Number(vendor?.estimated_delivery_time_min);
  if (service === 'eatout') return 'Book now';
  if (service === 'scenes') return 'Instant confirmation';
  if (Number.isFinite(eta) && eta > 0) {
    if (eta <= 15) return `${Math.round(eta)} mins`;
    return `${Math.max(10, Math.round(eta - 5))}-${Math.round(eta)} mins`;
  }
  if (service === 'warehouse') return '7-15 mins';
  return '20-30 mins';
}

function getEtaValue(vendor) {
  const eta = Number(vendor?.estimated_delivery_time_min);
  return Number.isFinite(eta) && eta > 0 ? eta : null;
}

function getFastestEta(vendors, fallback) {
  const values = (Array.isArray(vendors) ? vendors : [])
    .map((item) => getEtaValue(item))
    .filter((value) => value !== null);

  if (!values.length) return fallback;
  return `${Math.max(5, Math.min(...values))} mins`;
}

function getCuisineLine(vendor) {
  return String(vendor?.cuisine_tags || vendor?.description || vendor?.address || '')
    .split(/[,|·]/)
    .map((item) => String(item || '').trim())
    .filter(Boolean)
    .slice(0, 3)
    .join(' · ');
}

function getDistance(vendor) {
  const distance = Number(vendor?.distance_km);
  if (!Number.isFinite(distance) || distance <= 0) return '';
  return `${Math.round(distance * 10) / 10} km`;
}

function getVendorOffer(vendor, service) {
  if (vendor?.open_now === false) return 'Closed now';
  if (service === 'warehouse') return 'Free delivery on quick carts';
  if (service === 'eatout') return 'Flat 50% off on dining bills';
  if (service === 'scenes') return 'Seats filling fast';
  if (Number(vendor?.total_ratings || 0) >= 100) return 'Top rated around you';
  return 'Free delivery on first order';
}

function SectionHeading({ title, actionLabel }) {
  return (
    <View style={styles.sectionHeading}>
      <Text style={styles.sectionTitle}>{title}</Text>
      {actionLabel ? <Text style={styles.sectionAction}>{actionLabel}</Text> : null}
    </View>
  );
}

function HeaderBlock({ skin, activeService, etaText, onOpenAccount }) {
  return (
    <View style={styles.headerBlock}>
      <View style={{ flex: 1, paddingRight: 14 }}>
        <Text style={[styles.etaText, { color: skin.textOnHero }]}>{etaText}</Text>
        <View style={styles.locationLine}>
          <Ionicons
            name={activeService === 'warehouse' ? 'time-outline' : 'location-outline'}
            size={15}
            color={skin.textOnHero}
          />
          <Text numberOfLines={1} style={[styles.locationTitle, { color: skin.textOnHero }]}>Kochi</Text>
          <Ionicons name="chevron-down" size={14} color={skin.textOnHero} />
        </View>
        <Text numberOfLines={1} style={[styles.locationSubtitle, { color: skin.heroMuted }]}>Vidya Nagar Rd, Panampally Nagar</Text>
      </View>

      <TouchableOpacity activeOpacity={0.92} onPress={onOpenAccount} style={styles.avatarButton}>
        <Ionicons name="person" size={20} color={skin.hero} />
      </TouchableOpacity>
    </View>
  );
}

function ServiceTabs({ activeService, onChange }) {
  return (
    <View style={styles.serviceTabsRow}>
      {SERVICE_TABS.map((item) => {
        const active = item.key === activeService;
        return (
          <TouchableOpacity
            key={item.key}
            activeOpacity={0.94}
            onPress={() => onChange(item.key)}
            style={[styles.serviceTab, active && styles.serviceTabActive]}>
            <View style={[styles.serviceTabIconWrap, active && styles.serviceTabIconWrapActive]}>
              <Ionicons name={item.icon} size={18} color={active ? '#FFFFFF' : '#D6C8F3'} />
            </View>
            <Text style={[styles.serviceTabLabel, active && styles.serviceTabLabelActive]}>{item.label}</Text>
          </TouchableOpacity>
        );
      })}
    </View>
  );
}

function SearchBar({ value, onChangeText, onSubmit, placeholder }) {
  return (
    <View style={styles.searchRow}>
      <View style={styles.searchBar}>
        <Ionicons name="search-outline" size={20} color="#747474" />
        <TextInput
          value={value}
          onChangeText={onChangeText}
          onSubmitEditing={onSubmit}
          style={styles.searchInput}
          placeholder={placeholder}
          placeholderTextColor="#8A8A8A"
          returnKeyType="search"
        />
        <Ionicons name="mic-outline" size={20} color={PALETTE.primary} />
      </View>
      <TouchableOpacity activeOpacity={0.92} style={styles.searchAction}>
        <Ionicons name="receipt-outline" size={20} color="#5F5F5F" />
      </TouchableOpacity>
    </View>
  );
}

function FilterRow({ items, activeKey, onChange, skin }) {
  return (
    <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.filterRow}>
      {items.map((item) => {
        const active = item.key === activeKey;
        return (
          <TouchableOpacity
            key={item.key}
            activeOpacity={0.92}
            onPress={() => onChange(item.key, item.label)}
            style={[
              styles.filterPill,
              { backgroundColor: active ? skin.heroBadge : 'rgba(255,255,255,0.14)' },
            ]}>
            <Text
              style={[
                styles.filterPillText,
                { color: active ? skin.hero : '#FFFFFF' },
              ]}>
              {item.label}
            </Text>
          </TouchableOpacity>
        );
      })}
    </ScrollView>
  );
}

function PromoHero({ skin, activeService }) {
  const showMetrics = activeService === 'food' || activeService === 'warehouse';

  return (
    <View style={[styles.promoHero, { backgroundColor: skin.heroCard }]}>
      <View style={[styles.promoGlowLarge, { backgroundColor: skin.heroAccent }]} />
      <View style={styles.promoBadge}>
        <Image source={BRAND_LOGO} style={styles.promoBadgeLogo} contentFit="contain" />
        <Text style={[styles.promoBadgeText, { color: skin.hero }]}>{activeService === 'eatout' ? 'DINEOUT' : 'GRABBASKET'}</Text>
      </View>

      <Text style={styles.promoTitle}>{skin.headline}</Text>
      <Text style={[styles.promoSubtitle, { color: skin.heroMuted }]}>{skin.subheadline}</Text>

      <View style={styles.promoFooter}>
        <TouchableOpacity activeOpacity={0.92} style={styles.ctaButton}>
          <Text style={styles.ctaButtonText}>{skin.cta}</Text>
        </TouchableOpacity>

        {showMetrics ? (
          <View style={styles.heroMetricRow}>
            <View style={styles.heroMetricCard}>
              <Text style={styles.heroMetricLabel}>Best offers</Text>
              <Text style={styles.heroMetricValue}>{activeService === 'food' ? 'Up to 60%' : '12 brands live'}</Text>
            </View>
            <View style={styles.heroMetricCard}>
              <Text style={styles.heroMetricLabel}>Fastest</Text>
              <Text style={styles.heroMetricValue}>{activeService === 'warehouse' ? '7 mins' : '₹99+ meals'}</Text>
            </View>
          </View>
        ) : null}
      </View>
    </View>
  );
}

function BasketBanner({ cartCount, cartTotal, onPress, skin }) {
  if (!cartCount) return null;

  return (
    <TouchableOpacity activeOpacity={0.94} onPress={onPress} style={styles.basketBanner}>
      <View style={[styles.basketIconWrap, { backgroundColor: skin.softSection }]}>
        <Ionicons name="bag-handle-outline" size={18} color={skin.heroCard} />
      </View>
      <View style={{ flex: 1 }}>
        <Text style={styles.basketBannerTitle}>View cart</Text>
        <Text style={styles.basketBannerSubtitle}>{cartCount} items · {money(cartTotal)}</Text>
      </View>
      <Ionicons name="chevron-forward" size={18} color={skin.heroCard} />
    </TouchableOpacity>
  );
}

function FoodPromoGrid() {
  return (
    <View style={styles.foodPromoGrid}>
      {FOOD_PROMO_CARDS.map((item) => (
        <TouchableOpacity key={item.key} activeOpacity={0.94} style={styles.foodPromoCard}>
          <View style={styles.foodPromoIcon}>
            <Ionicons name={item.icon} size={18} color="#7A3900" />
          </View>
          <Text style={styles.foodPromoCardTitle}>{item.title}</Text>
          <Text style={styles.foodPromoCardValue}>{item.value}</Text>
          <Text style={styles.foodPromoCardCaption}>{item.caption}</Text>
        </TouchableOpacity>
      ))}
    </View>
  );
}

function WarehouseCollectionRow() {
  return (
    <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.collectionRail}>
      {MART_COLLECTIONS.map((item) => (
        <TouchableOpacity key={item.key} activeOpacity={0.94} style={styles.collectionCardBlue}>
          <View style={styles.collectionIconBubbleBlue}>
            <Ionicons name={item.icon} size={18} color="#FFFFFF" />
          </View>
          <Text style={styles.collectionCardBlueTitle}>{item.title}</Text>
          <Text style={styles.collectionCardBlueCaption}>{item.caption}</Text>
        </TouchableOpacity>
      ))}
    </ScrollView>
  );
}

function DineoutCollectionGrid() {
  return (
    <View style={styles.dineGrid}>
      {DINEOUT_COLLECTIONS.map((item) => (
        <TouchableOpacity
          key={item.key}
          activeOpacity={0.94}
          style={[styles.dineCard, item.size === 'large' && styles.dineCardLarge]}>
          <Ionicons name={item.icon} size={item.size === 'large' ? 28 : 20} color="#204B00" />
          <Text style={[styles.dineCardText, item.size === 'large' && styles.dineCardTextLarge]}>{item.title}</Text>
        </TouchableOpacity>
      ))}
    </View>
  );
}

function SceneList() {
  return (
    <View style={styles.sceneList}>
      {SCENE_EVENTS.map((item) => (
        <TouchableOpacity key={item.id} activeOpacity={0.94} style={styles.sceneCard}>
          <View style={styles.sceneCardIconWrap}>
            <Ionicons name={item.icon} size={20} color={PALETTE.primary} />
          </View>
          <View style={{ flex: 1 }}>
            <View style={styles.sceneTag}>
              <Text style={styles.sceneTagText}>{item.tag}</Text>
            </View>
            <Text style={styles.sceneCardTitle}>{item.title}</Text>
            <Text style={styles.sceneCardSubtitle}>{item.subtitle}</Text>
          </View>
          <Ionicons name="chevron-forward" size={18} color={PALETTE.subtle} />
        </TouchableOpacity>
      ))}
    </View>
  );
}

function ProductDealCard({ skin, item, qty, onAdd, onRemove }) {
  return (
    <View style={styles.productDealCard}>
      <View style={[styles.productDealVisual, { backgroundColor: skin.softSection }]}>
        <Ionicons name="cube-outline" size={22} color={skin.heroCard} />
      </View>
      <Text numberOfLines={1} style={styles.productDealBrand}>{item?.vendorName || 'GrabBasket'}</Text>
      <Text numberOfLines={2} style={styles.productDealName}>{item?.name}</Text>
      <Text style={styles.productDealPrice}>{money(item?.price)}</Text>

      {qty > 0 ? (
        <View style={styles.qtyRow}>
          <TouchableOpacity activeOpacity={0.92} onPress={onRemove} style={styles.qtyButton}>
            <Ionicons name="remove" size={16} color={PALETTE.primary} />
          </TouchableOpacity>
          <Text style={styles.qtyText}>{qty}</Text>
          <TouchableOpacity activeOpacity={0.92} onPress={onAdd} style={styles.qtyButton}>
            <Ionicons name="add" size={16} color={PALETTE.primary} />
          </TouchableOpacity>
        </View>
      ) : (
        <TouchableOpacity activeOpacity={0.92} onPress={onAdd} style={styles.addButton}>
          <Text style={styles.addButtonText}>ADD</Text>
        </TouchableOpacity>
      )}
    </View>
  );
}

function VendorCard({ vendor, service, skin, favorite, onToggleFavorite, onPress }) {
  const coverUri = resolveMediaUrl(vendor?.cover_image_url || vendor?.banner_image_url || vendor?.logo_image_url);
  const cuisineLine = getCuisineLine(vendor);
  const eta = getEta(vendor, service);
  const distance = getDistance(vendor);

  return (
    <TouchableOpacity activeOpacity={0.94} onPress={onPress} style={styles.vendorCard}>
      <View style={styles.vendorImageWrap}>
        {coverUri ? (
          <Image source={{ uri: coverUri }} style={styles.vendorImage} contentFit="cover" transition={180} />
        ) : (
          <View style={[styles.vendorImagePlaceholder, { backgroundColor: skin.softSection }]}>
            <Text style={[styles.vendorImagePlaceholderText, { color: skin.heroCard }]}>{initials(vendor?.name)}</Text>
          </View>
        )}

        <View style={styles.vendorTopBadges}>
          <View style={styles.ratingBadge}>
            <Ionicons name="star" size={12} color="#FFFFFF" />
            <Text style={styles.ratingBadgeText}>{getRating(vendor)}</Text>
          </View>

          <TouchableOpacity activeOpacity={0.9} onPress={onToggleFavorite} style={styles.favoriteButton}>
            <Ionicons name={favorite ? 'heart' : 'heart-outline'} size={16} color={favorite ? '#FF5A67' : '#5A5A5A'} />
          </TouchableOpacity>
        </View>

        <View style={styles.vendorOfferStrip}>
          <Text style={styles.vendorOfferStripText}>{getVendorOffer(vendor, service)}</Text>
        </View>
      </View>

      <View style={styles.vendorCardBody}>
        <View style={styles.vendorCardHeader}>
          <Text numberOfLines={1} style={styles.vendorName}>{vendor?.name}</Text>
          <Text style={styles.vendorEta}>{eta}</Text>
        </View>
        <Text numberOfLines={1} style={styles.vendorMetaLine}>
          {[cuisineLine, distance].filter(Boolean).join(' · ') || 'Fresh picks around you'}
        </Text>
        <Text numberOfLines={1} style={styles.vendorSubMeta}>{vendor?.description || vendor?.address || 'Great quality, quick service'}</Text>
      </View>
    </TouchableOpacity>
  );
}

function ServiceBody({
  activeService,
  vendors,
  favorites,
  toggleFavorite,
  onOpenVendor,
  skin,
  homeDeals,
  cart,
  onAddDeal,
  onRemoveDeal,
}) {
  if (activeService === 'food') {
    return (
      <>
        <SectionHeading title="Best deals for you" actionLabel="See all" />
        <FoodPromoGrid />

        <View style={styles.marqueeCard}>
          <View>
            <Text style={styles.marqueeTitle}>Restaurant awards</Text>
            <Text style={styles.marqueeSubtitle}>Vote, share & earn up to ₹600</Text>
          </View>
          <TouchableOpacity activeOpacity={0.92} style={styles.marqueeButton}>
            <Text style={styles.marqueeButtonText}>Get started</Text>
          </TouchableOpacity>
        </View>

        <SectionHeading title="Popular restaurants near you" actionLabel="View all" />
        <View style={styles.vendorList}>
          {vendors.map((vendor) => (
            <VendorCard
              key={vendor.id}
              vendor={vendor}
              service="food"
              skin={skin}
              favorite={Boolean(favorites[vendor.id])}
              onToggleFavorite={() => toggleFavorite(vendor.id)}
              onPress={() => onOpenVendor(vendor)}
            />
          ))}
        </View>
      </>
    );
  }

  if (activeService === 'warehouse') {
    return (
      <>
        <SectionHeading title="GrabBasket specials" actionLabel="Best brands" />
        <WarehouseCollectionRow />

        <SectionHeading title="Most shopped near you" actionLabel="See all" />
        <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.productRail}>
          {homeDeals.map((item) => (
            <ProductDealCard
              key={item.id}
              skin={skin}
              item={item}
              qty={cart.items?.[item.id]?.qty || 0}
              onAdd={() => onAddDeal(item)}
              onRemove={() => onRemoveDeal(item)}
            />
          ))}
        </ScrollView>

        <SectionHeading title="Nearby stores" actionLabel="View all" />
        <View style={styles.vendorList}>
          {vendors.map((vendor) => (
            <VendorCard
              key={vendor.id}
              vendor={vendor}
              service="warehouse"
              skin={skin}
              favorite={Boolean(favorites[vendor.id])}
              onToggleFavorite={() => toggleFavorite(vendor.id)}
              onPress={() => onOpenVendor(vendor)}
            />
          ))}
        </View>
      </>
    );
  }

  if (activeService === 'eatout') {
    return (
      <>
        <SectionHeading title="Dineout picks" actionLabel="GIRF offers" />
        <DineoutCollectionGrid />

        <View style={styles.marqueeCardDark}>
          <View>
            <Text style={styles.marqueeTitleDark}>Get extra ₹250 OFF*</Text>
            <Text style={styles.marqueeSubtitleDark}>On your first dining bill · Book now</Text>
          </View>
          <TouchableOpacity activeOpacity={0.92} style={styles.marqueeButtonDark}>
            <Text style={styles.marqueeButtonDarkText}>Book now</Text>
          </TouchableOpacity>
        </View>

        <SectionHeading title="Restaurants with dining deals" actionLabel="View all" />
        <View style={styles.vendorList}>
          {vendors.map((vendor) => (
            <VendorCard
              key={vendor.id}
              vendor={vendor}
              service="eatout"
              skin={skin}
              favorite={Boolean(favorites[vendor.id])}
              onToggleFavorite={() => toggleFavorite(vendor.id)}
              onPress={() => onOpenVendor(vendor)}
            />
          ))}
        </View>
      </>
    );
  }

  return (
    <>
      <SectionHeading title="Scenes for the weekend" actionLabel="See all" />
      <SceneList />

      <SectionHeading title="Popular experiences" actionLabel="Curated" />
      <View style={styles.vendorList}>
        {vendors.map((vendor) => (
          <VendorCard
            key={vendor.id}
            vendor={vendor}
            service="scenes"
            skin={skin}
            favorite={Boolean(favorites[vendor.id])}
            onToggleFavorite={() => toggleFavorite(vendor.id)}
            onPress={() => onOpenVendor(vendor)}
          />
        ))}
      </View>
    </>
  );
}

export default function HomeScreen() {
  const router = useRouter();
  const tabBarHeight = useBottomTabBarHeight();
  const [foodFilter, setFoodFilter] = useState('biryani');
  const [martFilter, setMartFilter] = useState('all');
  const [dineFilter, setDineFilter] = useState('offers');
  const [sceneFilter, setSceneFilter] = useState('today');

  const {
    activeService,
    setActiveService,
    setActiveShortcut,
    homeSearch,
    setHomeSearch,
    vendors,
    featuredVendors,
    favorites,
    toggleFavorite,
    cart,
    cartCount,
    cartTotal,
    homeDeals,
    loadVendors,
    refreshing,
    rememberSearch,
    rememberStore,
    addToCart,
    updateQty,
  } = useGrabBasket();

  const skin = SERVICE_SKINS[activeService] || SERVICE_SKINS.food;

  const displayVendors = useMemo(() => {
    const source = Array.isArray(featuredVendors) && featuredVendors.length ? featuredVendors : vendors;
    return Array.isArray(source) ? source.slice(0, 8) : [];
  }, [featuredVendors, vendors]);

  const vendorMap = useMemo(() => {
    const map = new Map();
    displayVendors.forEach((vendor) => {
      map.set(String(vendor?.id), vendor);
      map.set(String(vendor?.name || '').trim().toLowerCase(), vendor);
    });
    return map;
  }, [displayVendors]);

  const displayDeals = useMemo(() => {
    if (Array.isArray(homeDeals) && homeDeals.length) return homeDeals.slice(0, 8);
    const fallbackVendor = displayVendors[0];
    return [
      { id: 'mart-1', vendor_id: fallbackVendor?.id, vendorName: fallbackVendor?.name || 'Daily Basket', name: 'Cold Coffee', price: 49 },
      { id: 'mart-2', vendor_id: fallbackVendor?.id, vendorName: fallbackVendor?.name || 'Daily Basket', name: 'Farm Fresh Eggs', price: 72 },
      { id: 'mart-3', vendor_id: fallbackVendor?.id, vendorName: fallbackVendor?.name || 'Daily Basket', name: 'Chocolate Biscuit', price: 25 },
      { id: 'mart-4', vendor_id: fallbackVendor?.id, vendorName: fallbackVendor?.name || 'Daily Basket', name: 'Banana Chips', price: 35 },
    ];
  }, [homeDeals, displayVendors]);

  const etaText = useMemo(() => getFastestEta(displayVendors, skin.etaFallback), [displayVendors, skin.etaFallback]);

  const topFilters = activeService === 'food'
    ? FOOD_FILTERS
    : activeService === 'warehouse'
      ? MART_FILTERS
      : activeService === 'eatout'
        ? DINEOUT_FILTERS
        : SCENE_FILTERS;

  const activeFilterKey = activeService === 'food'
    ? foodFilter
    : activeService === 'warehouse'
      ? martFilter
      : activeService === 'eatout'
        ? dineFilter
        : sceneFilter;

  const applyTopFilter = (key, label) => {
    if (activeService === 'food') {
      setFoodFilter(key);
      setHomeSearch(label);
      rememberSearch(label);
      return;
    }

    if (activeService === 'warehouse') {
      setMartFilter(key);
      setActiveShortcut(key);
      return;
    }

    if (activeService === 'eatout') {
      setDineFilter(key);
      setHomeSearch(label);
      rememberSearch(label);
      return;
    }

    setSceneFilter(key);
  };

  const handleSearch = () => {
    rememberSearch(homeSearch);
    loadVendors();
  };

  const handleRefresh = () => {
    loadVendors({ pullToRefresh: true });
  };

  const handleOpenVendor = (vendor) => {
    if (!vendor?.id) return;
    rememberStore(vendor.id);
    router.push({ pathname: '/store/[vendorId]', params: { vendorId: String(vendor.id) } });
  };

  const handleServiceChange = (serviceKey) => {
    setActiveService(serviceKey);
    if (serviceKey !== 'warehouse') setActiveShortcut('all');
  };

  const handleAddDeal = (item) => {
    const vendor = vendorMap.get(String(item?.vendor_id))
      || vendorMap.get(String(item?.vendorName || '').trim().toLowerCase());
    if (!vendor || !item?.id) return;
    addToCart(vendor, item);
  };

  const handleRemoveDeal = (item) => {
    if (!item?.id) return;
    const currentQty = Number(cart.items?.[item.id]?.qty || 0);
    updateQty(item.id, currentQty - 1);
  };

  return (
    <SafeAreaView style={[styles.safeArea, { backgroundColor: skin.hero }]} edges={['top']}>
      <StatusBar barStyle="light-content" backgroundColor={skin.hero} />

      <ScrollView
        showsVerticalScrollIndicator={false}
        refreshControl={
          <RefreshControl refreshing={refreshing} onRefresh={handleRefresh} tintColor="#FFFFFF" />
        }
        contentContainerStyle={{ paddingBottom: tabBarHeight + 28, backgroundColor: skin.page }}>
        <View style={[styles.heroWrap, { backgroundColor: skin.hero }]}>
          <HeaderBlock skin={skin} activeService={activeService} etaText={etaText} onOpenAccount={() => router.push('/account')} />
          <ServiceTabs activeService={activeService} onChange={handleServiceChange} />
          <SearchBar
            value={homeSearch}
            onChangeText={setHomeSearch}
            onSubmit={handleSearch}
            placeholder={skin.searchPlaceholder}
          />
          <FilterRow items={topFilters} activeKey={activeFilterKey} onChange={applyTopFilter} skin={skin} />
          <PromoHero skin={skin} activeService={activeService} />
        </View>

        <View style={styles.bodyWrap}>
          <BasketBanner cartCount={cartCount} cartTotal={cartTotal} onPress={() => router.push('/cart')} skin={skin} />
          <ServiceBody
            activeService={activeService}
            vendors={displayVendors}
            favorites={favorites}
            toggleFavorite={toggleFavorite}
            onOpenVendor={handleOpenVendor}
            skin={skin}
            homeDeals={displayDeals}
            cart={cart}
            onAddDeal={handleAddDeal}
            onRemoveDeal={handleRemoveDeal}
          />
        </View>
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: {
    flex: 1,
  },
  heroWrap: {
    paddingHorizontal: 18,
    paddingTop: 8,
    paddingBottom: 26,
    borderBottomLeftRadius: 28,
    borderBottomRightRadius: 28,
  },
  headerBlock: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    marginBottom: 18,
  },
  etaText: {
    fontSize: 40,
    lineHeight: 42,
    fontWeight: '900',
  },
  locationLine: {
    flexDirection: 'row',
    alignItems: 'center',
    marginTop: 2,
    gap: 6,
  },
  locationTitle: {
    fontSize: 17,
    fontWeight: '800',
    flexShrink: 1,
  },
  locationSubtitle: {
    marginTop: 4,
    fontSize: 13,
    lineHeight: 18,
    fontWeight: '600',
  },
  avatarButton: {
    width: 46,
    height: 46,
    borderRadius: 23,
    backgroundColor: '#FFFFFF',
    alignItems: 'center',
    justifyContent: 'center',
    ...createShadow(0.12, 14, 6),
  },
  serviceTabsRow: {
    flexDirection: 'row',
    alignItems: 'stretch',
    justifyContent: 'space-between',
    gap: 8,
    marginBottom: 16,
  },
  serviceTab: {
    flex: 1,
    borderRadius: 22,
    paddingVertical: 12,
    paddingHorizontal: 8,
    backgroundColor: 'rgba(255,255,255,0.08)',
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.12)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  serviceTabActive: {
    backgroundColor: 'rgba(255,255,255,0.18)',
    borderColor: 'rgba(255,255,255,0.28)',
  },
  serviceTabIconWrap: {
    width: 38,
    height: 38,
    borderRadius: 19,
    backgroundColor: 'rgba(255,255,255,0.08)',
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 7,
  },
  serviceTabIconWrapActive: {
    backgroundColor: PALETTE.primary,
  },
  serviceTabLabel: {
    color: '#D6C8F3',
    fontSize: 12,
    fontWeight: '700',
  },
  serviceTabLabelActive: {
    color: '#FFFFFF',
  },
  searchRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    marginBottom: 14,
  },
  searchBar: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    backgroundColor: '#FFFFFF',
    borderRadius: 18,
    paddingHorizontal: 16,
    paddingVertical: 15,
  },
  searchInput: {
    flex: 1,
    fontSize: 16,
    fontWeight: '600',
    color: '#1D1D1D',
  },
  searchAction: {
    width: 50,
    height: 50,
    borderRadius: 18,
    backgroundColor: '#FFFFFF',
    alignItems: 'center',
    justifyContent: 'center',
  },
  filterRow: {
    gap: 10,
    paddingBottom: 4,
    marginBottom: 16,
  },
  filterPill: {
    paddingHorizontal: 14,
    paddingVertical: 9,
    borderRadius: 999,
  },
  filterPillText: {
    fontSize: 12,
    fontWeight: '800',
  },
  promoHero: {
    borderRadius: 28,
    padding: 18,
    overflow: 'hidden',
    ...createShadow(0.14, 18, 10),
  },
  promoGlowLarge: {
    position: 'absolute',
    width: 170,
    height: 170,
    borderRadius: 85,
    right: -30,
    top: -20,
    opacity: 0.28,
  },
  promoBadge: {
    alignSelf: 'flex-start',
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    paddingHorizontal: 12,
    paddingVertical: 8,
    borderRadius: 999,
    backgroundColor: '#FFFFFF',
    marginBottom: 14,
  },
  promoBadgeLogo: {
    width: 18,
    height: 18,
  },
  promoBadgeText: {
    fontSize: 11,
    fontWeight: '900',
    letterSpacing: 0.4,
  },
  promoTitle: {
    color: '#FFFFFF',
    fontSize: 34,
    lineHeight: 36,
    fontWeight: '900',
    marginBottom: 8,
  },
  promoSubtitle: {
    fontSize: 14,
    lineHeight: 19,
    fontWeight: '600',
    maxWidth: '88%',
  },
  promoFooter: {
    marginTop: 18,
    gap: 12,
  },
  ctaButton: {
    alignSelf: 'flex-start',
    backgroundColor: '#FFFFFF',
    borderRadius: 14,
    paddingHorizontal: 16,
    paddingVertical: 10,
  },
  ctaButtonText: {
    color: '#24103B',
    fontSize: 12,
    fontWeight: '900',
    letterSpacing: 0.5,
  },
  heroMetricRow: {
    flexDirection: 'row',
    gap: 10,
  },
  heroMetricCard: {
    flex: 1,
    backgroundColor: 'rgba(255,255,255,0.14)',
    borderRadius: 18,
    paddingHorizontal: 14,
    paddingVertical: 12,
  },
  heroMetricLabel: {
    color: 'rgba(255,255,255,0.75)',
    fontSize: 11,
    fontWeight: '700',
    marginBottom: 4,
  },
  heroMetricValue: {
    color: '#FFFFFF',
    fontSize: 13,
    fontWeight: '800',
  },
  bodyWrap: {
    paddingHorizontal: 18,
    paddingTop: 18,
    gap: 20,
  },
  basketBanner: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    backgroundColor: '#FFFFFF',
    borderRadius: 22,
    padding: 16,
    ...createShadow(0.08, 16, 8),
  },
  basketIconWrap: {
    width: 42,
    height: 42,
    borderRadius: 21,
    alignItems: 'center',
    justifyContent: 'center',
  },
  basketBannerTitle: {
    color: '#171717',
    fontSize: 14,
    fontWeight: '800',
  },
  basketBannerSubtitle: {
    marginTop: 2,
    color: '#707070',
    fontSize: 12,
    fontWeight: '600',
  },
  sectionHeading: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 12,
  },
  sectionTitle: {
    color: '#151515',
    fontSize: 20,
    fontWeight: '900',
  },
  sectionAction: {
    color: PALETTE.primary,
    fontSize: 13,
    fontWeight: '800',
  },
  foodPromoGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 12,
  },
  foodPromoCard: {
    width: '48.2%',
    backgroundColor: '#F6D92F',
    borderRadius: 22,
    padding: 16,
    minHeight: 144,
  },
  foodPromoIcon: {
    width: 36,
    height: 36,
    borderRadius: 18,
    backgroundColor: 'rgba(255,255,255,0.55)',
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 16,
  },
  foodPromoCardTitle: {
    color: '#6B2C00',
    fontSize: 12,
    fontWeight: '800',
    marginBottom: 8,
  },
  foodPromoCardValue: {
    color: '#FFFFFF',
    fontSize: 24,
    lineHeight: 26,
    fontWeight: '900',
  },
  foodPromoCardCaption: {
    color: '#6B2C00',
    fontSize: 12,
    fontWeight: '700',
    marginTop: 6,
  },
  marqueeCard: {
    backgroundColor: '#3B0D15',
    borderRadius: 22,
    padding: 18,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 12,
  },
  marqueeTitle: {
    color: '#FFFFFF',
    fontSize: 20,
    fontWeight: '900',
    marginBottom: 4,
  },
  marqueeSubtitle: {
    color: '#F5CEC9',
    fontSize: 13,
    lineHeight: 18,
    fontWeight: '600',
  },
  marqueeButton: {
    backgroundColor: '#FFFFFF',
    borderRadius: 12,
    paddingHorizontal: 12,
    paddingVertical: 10,
  },
  marqueeButtonText: {
    color: '#3B0D15',
    fontSize: 12,
    fontWeight: '800',
  },
  marqueeCardDark: {
    backgroundColor: '#111111',
    borderRadius: 22,
    padding: 18,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 12,
  },
  marqueeTitleDark: {
    color: '#FFFFFF',
    fontSize: 22,
    fontWeight: '900',
    marginBottom: 4,
  },
  marqueeSubtitleDark: {
    color: '#C6C6C6',
    fontSize: 13,
    lineHeight: 18,
    fontWeight: '600',
  },
  marqueeButtonDark: {
    backgroundColor: '#FF8A34',
    borderRadius: 12,
    paddingHorizontal: 12,
    paddingVertical: 10,
  },
  marqueeButtonDarkText: {
    color: '#FFFFFF',
    fontSize: 12,
    fontWeight: '800',
  },
  collectionRail: {
    gap: 12,
  },
  collectionCardBlue: {
    width: 170,
    backgroundColor: '#17439F',
    borderRadius: 22,
    padding: 16,
  },
  collectionIconBubbleBlue: {
    width: 38,
    height: 38,
    borderRadius: 19,
    backgroundColor: 'rgba(255,255,255,0.14)',
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 18,
  },
  collectionCardBlueTitle: {
    color: '#FFFFFF',
    fontSize: 18,
    lineHeight: 22,
    fontWeight: '800',
    marginBottom: 6,
  },
  collectionCardBlueCaption: {
    color: '#BDD0FF',
    fontSize: 12,
    fontWeight: '600',
  },
  productRail: {
    gap: 12,
  },
  productDealCard: {
    width: 154,
    backgroundColor: '#FFFFFF',
    borderRadius: 22,
    padding: 14,
    ...createShadow(0.08, 14, 8),
  },
  productDealVisual: {
    height: 110,
    borderRadius: 18,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 12,
  },
  productDealBrand: {
    color: '#8B8B8B',
    fontSize: 11,
    fontWeight: '700',
    marginBottom: 4,
  },
  productDealName: {
    color: '#1B1B1B',
    fontSize: 14,
    lineHeight: 18,
    fontWeight: '800',
    minHeight: 36,
    marginBottom: 6,
  },
  productDealPrice: {
    color: '#171717',
    fontSize: 15,
    fontWeight: '900',
    marginBottom: 12,
  },
  addButton: {
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: 12,
    borderWidth: 1,
    borderColor: PALETTE.primary,
    paddingVertical: 9,
  },
  addButtonText: {
    color: PALETTE.primary,
    fontSize: 13,
    fontWeight: '900',
  },
  qtyRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    borderRadius: 12,
    borderWidth: 1,
    borderColor: '#F2C8C8',
    paddingHorizontal: 10,
    paddingVertical: 7,
  },
  qtyButton: {
    width: 24,
    height: 24,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
  },
  qtyText: {
    color: '#171717',
    fontSize: 14,
    fontWeight: '800',
  },
  vendorList: {
    gap: 16,
  },
  vendorCard: {
    backgroundColor: '#FFFFFF',
    borderRadius: 24,
    overflow: 'hidden',
    ...createShadow(0.08, 16, 8),
  },
  vendorImageWrap: {
    height: 188,
    position: 'relative',
  },
  vendorImage: {
    width: '100%',
    height: '100%',
  },
  vendorImagePlaceholder: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  vendorImagePlaceholderText: {
    fontSize: 30,
    fontWeight: '900',
  },
  vendorTopBadges: {
    position: 'absolute',
    top: 12,
    left: 12,
    right: 12,
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  ratingBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 5,
    paddingHorizontal: 9,
    paddingVertical: 6,
    borderRadius: 999,
    backgroundColor: '#18A558',
  },
  ratingBadgeText: {
    color: '#FFFFFF',
    fontSize: 11,
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
  vendorOfferStrip: {
    position: 'absolute',
    left: 12,
    right: 12,
    bottom: 12,
    backgroundColor: 'rgba(17,17,17,0.84)',
    borderRadius: 12,
    paddingHorizontal: 10,
    paddingVertical: 8,
  },
  vendorOfferStripText: {
    color: '#FFFFFF',
    fontSize: 12,
    fontWeight: '800',
  },
  vendorCardBody: {
    padding: 14,
  },
  vendorCardHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 10,
    marginBottom: 6,
  },
  vendorName: {
    flex: 1,
    color: '#121212',
    fontSize: 18,
    fontWeight: '900',
  },
  vendorEta: {
    color: '#121212',
    fontSize: 14,
    fontWeight: '800',
  },
  vendorMetaLine: {
    color: '#6B6B6B',
    fontSize: 13,
    lineHeight: 18,
    fontWeight: '700',
    marginBottom: 4,
  },
  vendorSubMeta: {
    color: '#909090',
    fontSize: 12,
    lineHeight: 17,
    fontWeight: '600',
  },
  dineGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 12,
  },
  dineCard: {
    width: '48.2%',
    minHeight: 112,
    borderRadius: 22,
    padding: 16,
    backgroundColor: '#DFFF55',
    justifyContent: 'space-between',
  },
  dineCardLarge: {
    minHeight: 236,
  },
  dineCardText: {
    color: '#2E3E00',
    fontSize: 17,
    lineHeight: 21,
    fontWeight: '900',
  },
  dineCardTextLarge: {
    fontSize: 26,
    lineHeight: 30,
  },
  sceneList: {
    gap: 12,
  },
  sceneCard: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 14,
    backgroundColor: '#FFFFFF',
    borderRadius: 20,
    padding: 14,
    ...createShadow(0.06, 10, 6),
  },
  sceneCardIconWrap: {
    width: 46,
    height: 46,
    borderRadius: 23,
    backgroundColor: '#FFF1E8',
    alignItems: 'center',
    justifyContent: 'center',
  },
  sceneTag: {
    alignSelf: 'flex-start',
    backgroundColor: '#FFF1E8',
    borderRadius: 999,
    paddingHorizontal: 10,
    paddingVertical: 4,
    marginBottom: 8,
  },
  sceneTagText: {
    color: '#B34E00',
    fontSize: 11,
    fontWeight: '800',
  },
  sceneCardTitle: {
    color: '#121212',
    fontSize: 15,
    fontWeight: '800',
    marginBottom: 4,
  },
  sceneCardSubtitle: {
    color: '#7C7C7C',
    fontSize: 12,
    fontWeight: '600',
  },
});