# Floating Category Button - Hide/Show Feature

## Overview
Added hide/show functionality to the floating category button (FAB) specifically for mobile view, improving mobile UX by giving users control over screen real estate.

## Features Implemented

### 1. **Hide Button**
- **Desktop**: Appears on hover over the FAB
- **Mobile**: Appears on long press (500ms)
- Small "✕" button above the main FAB
- Gradient red styling for clear visibility

### 2. **Show Button**
- Appears when FAB is hidden
- Green gradient with eye emoji (👁️) icon
- Pulsing animation to draw attention
- Fixed position at bottom-right (above mobile nav on mobile)

### 3. **LocalStorage Persistence**
- Automatically saves hide/show state
- State persists across page reloads
- Mobile-only feature (localStorage only on mobile)
- Key: `fabHidden` (true/false)

### 4. **Smooth Animations**
- FAB slides out with transform: translateX(150px)
- Opacity fade: 1 → 0
- 300ms transition duration
- Show button pulses to indicate interactive element

### 5. **Mobile Responsiveness**
- FAB positioned above mobile bottom nav (90px from bottom)
- Desktop keeps standard position (20px from bottom)
- Media query at 768px breakpoint
- Touch-optimized for mobile devices

## Technical Implementation

### Files Modified
- `resources/views/index.blade.php` (Commit: fa97c91f)

### HTML Structure
```html
<!-- Main FAB Container -->
<div id="floatingActionsContainer" class="floating-actions">
  <button class="fab-main" id="fabMainBtn">🛍️</button>
  <button class="fab-hide-btn" id="fabHideBtn">✕</button>
  <div id="floatingMenu"><!-- Category popup --></div>
</div>

<!-- Show Button (hidden by default) -->
<button id="showFabBtn">👁️</button>
```

### JavaScript Functions

#### `hideFloatingButton(saveState = true)`
- Closes popup menu if open
- Animates FAB off-screen (translateX: 150px)
- Fades out (opacity: 0)
- Shows the show button after 300ms
- Saves state to localStorage (mobile only)

#### `showFloatingButton()`
- Hides show button
- Displays FAB container
- Animates FAB back (translateX: 0)
- Fades in (opacity: 1)
- Saves state to localStorage (mobile only)

#### `DOMContentLoaded` Enhancements
- Checks localStorage for saved state
- Applies hidden state if fabHidden === 'true'
- Sets up event listeners for hide button
- Desktop: hover to show hide button
- Mobile: long press (500ms) to show hide button

### CSS Styling

#### Pulse Animation
```css
@keyframes pulse {
  0%, 100% {
    transform: scale(1);
    box-shadow: 0 4px 15px rgba(40,167,69,0.3);
  }
  50% {
    transform: scale(1.05);
    box-shadow: 0 6px 20px rgba(40,167,69,0.5);
  }
}
```

#### Mobile Positioning
```css
@media (max-width: 768px) {
  #floatingActionsContainer {
    bottom: 90px !important; /* Above mobile nav */
  }
  
  #showFabBtn {
    bottom: 90px !important; /* Above mobile nav */
  }
}
```

## User Experience

### Desktop Flow
1. Hover over FAB → Hide button appears
2. Click hide button → FAB slides out
3. Show button appears with pulse animation
4. Click show button → FAB slides back in

### Mobile Flow
1. Long press FAB (500ms) → Hide button appears
2. Tap hide button → FAB slides out
3. Show button appears with pulse animation
4. Tap show button → FAB slides back in
5. State persists across page reloads

## Z-Index Hierarchy
- Mobile Bottom Nav: `z-index: 1000`
- Show Button: `z-index: 1199`
- FAB Container: `z-index: 1200`
- Proper stacking ensures no overlap issues

## Benefits

### For Users
- ✅ Control over mobile screen real estate
- ✅ One-tap access to hide/show
- ✅ State persists across sessions
- ✅ Clear visual feedback with animations
- ✅ Non-intrusive when hidden

### For Mobile UX
- ✅ More screen space for content
- ✅ Reduces visual clutter
- ✅ Optional feature (can ignore if desired)
- ✅ Quick access to categories when needed
- ✅ Above mobile nav (no conflicts)

## Testing Checklist

- [ ] Desktop: Hide button appears on hover
- [ ] Desktop: FAB hides and shows smoothly
- [ ] Mobile: Long press shows hide button
- [ ] Mobile: FAB positioned above bottom nav (90px)
- [ ] Mobile: State persists after reload
- [ ] Animations smooth (300ms transitions)
- [ ] Show button pulses when FAB hidden
- [ ] Category popup works after show/hide
- [ ] No z-index conflicts with mobile nav
- [ ] LocalStorage saves/loads correctly

## Browser Compatibility
- Modern browsers (Chrome, Firefox, Safari, Edge)
- Requires localStorage support (99%+ coverage)
- CSS transitions and transforms (universal support)
- Touch events for mobile (standard)

## Future Enhancements
- [ ] Swipe gesture to hide (in addition to button)
- [ ] Customizable hide/show animations
- [ ] User preference in account settings
- [ ] Analytics tracking for hide/show usage
- [ ] A/B testing for optimal placement

## Related Files
- `resources/views/index.blade.php` - Main implementation
- Previous commits:
  - 26a7fbc3 - Wishlist & mobile nav fixes
  - 557e5ee0 - Enhanced store card design
  - 3035c26e - Store search feature
  - 1d14842b - Deals/Trending redesign

## Commit Hash
`fa97c91f` - Add hideable floating button for mobile view with localStorage persistence

---
**Status**: ✅ Complete
**Date**: 2024
**Priority**: High (Mobile UX improvement)
