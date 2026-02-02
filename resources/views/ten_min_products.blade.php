<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>GrabBasket — 10-Minute Products</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
<style>
/* ---------- base (from your css) ---------- */
html, body {
margin: 0;
padding: 0;
overflow-x: hidden;
font-family: Inter, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
background: #f4f6f8;
color: #0b1a12;
}
 
/* Layout and header */
header {
background: linear-gradient(180deg,#ffffff,#fbfdfb);
padding: 12px 16px;
border-bottom: 1px solid rgba(8,10,10,0.03);
position: sticky;
top: 0;
z-index: 200;
box-shadow: 0 1px 0 rgba(0,0,0,0.02);
}
 
.container {
max-width: 1200px;
margin: 0 auto;
padding: 0 16px;
}
.header-row {
display: flex;
align-items: center;
justify-content: space-between;
gap: 12px;
flex-wrap: wrap;
}
 
/* brand + menu button */
.brand {
display: flex;
align-items: center;
gap: 12px;
}
.logo {
width: 44px;
height: 44px;
border-radius: 10px;
background: linear-gradient(135deg,#cff2b6,#ffd86b);
display: flex;
align-items: center;
justify-content: center;
font-weight: 800;
color: #063310;
font-size: 18px;
}
.brand .title { font-weight: 700; font-size: 16px; }
.brand .sub { font-size: 12px; color: #6b7280; }
 
/* hamburger menu (visible on mobile) */
.menu-btn {
display: none;
background: transparent;
border: 0;
padding: 8px;
border-radius: 8px;
cursor: pointer;
font-size: 20px;
}
.menu-btn:focus { outline: 2px solid rgba(47,122,47,0.18); }
 
/* header actions (right side) */
.header-actions {
display: flex;
align-items: center;
gap: 12px;
}
 
/* ---------- SEARCH BAR ---------- */
.search {
display: flex;
align-items: center;
gap: 10px;
background: #fff;
padding: 10px 16px;
border-radius: 14px;
box-shadow: 0 8px 24px rgba(15,23,36,0.08);
width: 100%;
max-width: 400px;
transition: all 0.3s ease;
box-sizing: border-box;
}
.search svg {
opacity: 0.7;
flex-shrink: 0;
font-size: 18px;
}
.search input {
border: 0;
outline: 0;
background: transparent;
width: 100%;
font-size: 14px;
color: #08120a;
padding: 4px 0;
}
.search input::placeholder {
color: #6b7280;
}
.search button {
border: 0;
background: linear-gradient(180deg,#2f7a2f,#2f7a2f);
color: #fff;
padding: 8px 14px;
border-radius: 10px;
cursor: pointer;
font-weight: 700;
box-shadow: 0 6px 18px rgba(47,122,47,0.14);
flex-shrink: 0;
transition: transform 0.15s ease;
}
.search button:hover { transform: translateY(-2px); }
 
/* Categories row */
.categories-row { padding: 18px 0; }
.categories { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; }
.category {
display: flex;
flex-direction: column;
align-items: center;
gap: 8px;
min-width: 86px;
cursor: pointer;
text-align: center;
padding: 8px;
border-radius: 12px;
transition: all .18s;
background: transparent;
}
.category .badge {
width: 68px;
height: 68px;
border-radius: 50%;
display: flex;
align-items: center;
justify-content: center;
font-size: 28px;
background: linear-gradient(135deg,#f1f9ef,#fff);
box-shadow: 0 6px 18px rgba(15,23,36,0.06);
}
.category .label { font-size: 13px; font-weight: 600; color: #0b1a12; }
.category:hover { transform: translateY(-6px); box-shadow: 0 18px 40px rgba(15,23,36,0.09); }
.category.active .badge { background: linear-gradient(135deg,#e6f7e6,#eafbed); box-shadow: 0 18px 40px rgba(39,122,39,0.12); }
 
/* Main layout */
.layout { display: grid; grid-template-columns: 280px 1fr; gap: 22px; max-width: 1200px; margin: 0 auto; padding: 0 16px; }
 
/* Sidebar desktop */
aside.sidebar {
background: #fff;
padding: 18px;
border-radius: 12px;
box-shadow: 0 6px 18px rgba(15,23,36,0.06);
height: calc(100vh - 160px);
position: sticky;
top: 84px;
overflow-y: auto;
transition: transform .28s ease, left .28s ease;
}
 
/* Sidebar items */
.sub-list { display: flex; flex-direction: column; gap: 10px; }
.sub-item {
display: flex;
align-items: center;
gap: 12px;
padding: 10px;
border-radius: 10px;
cursor: pointer;
transition: transform .12s ease, background .12s ease;
background: transparent;
}
.sub-item:hover { transform: translateX(6px); background: linear-gradient(90deg,#fbfff6,#fff); }
.sub-item.active { background: linear-gradient(90deg,#ecffd6,#fff); box-shadow: 0 10px 30px rgba(47,122,47,0.06); }
.sub-item .icon {
width: 44px;
height: 44px;
border-radius: 10px;
display: flex;
align-items: center;
justify-content: center;
font-size: 18px;
background: linear-gradient(135deg,#f1f9ef,#e6f3d8);
color: #2f7a2f;
box-shadow: 0 6px 12px rgba(15,23,36,0.04);
flex-shrink: 0;
}
.sub-item .meta .name { font-weight: 600; }
.sub-item .meta .hint { font-size: 13px; color: #6b7280; }
.sub-item .count { font-size: 13px; color: #6b7280; padding: 6px 8px; border-radius: 8px; background: rgba(15,23,36,0.04); }
 
/* Products */
.products { display: grid; gap: 18px; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); }
.card {
background: #fff;
border-radius: 12px;
padding: 12px;
box-shadow: 0 6px 18px rgba(15,23,36,0.06);
display: flex;
flex-direction: column;
transition: transform .12s ease, box-shadow .12s ease;
position: relative;
}
.card:hover { transform: translateY(-8px); box-shadow: 0 18px 40px rgba(15,23,36,0.09); }
.thumb { width: 100%; height: 150px; border-radius: 10px; background: linear-gradient(180deg,#eef4f7,#f7fbfb); display: flex; align-items: center; justify-content: center; overflow: hidden; }
.thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
.ribbon { position: absolute; top: 12px; right: 12px; background: linear-gradient(180deg,#ff6b6b,#e33434); color: #fff; padding: 6px 8px; border-radius: 8px; font-weight: 700; font-size: 12px; box-shadow:0 8px 22px rgba(227,52,52,0.16); }
.card h4 { margin: 12px 0 6px 0; font-size: 15px; color: #07140a; }
.meta-sub { font-size: 13px; color: #6b7280; }
.price-row { display: flex; align-items: center; gap: 8px; margin-top: 10px; flex-wrap: wrap; }
.price-original { font-size: 13px; color: #6b7280; text-decoration: line-through; }
.price-now { font-weight: 800; color: #2f7a2f; }
.discount-text { font-weight: 700; color: #d8392c; font-size: 13px; margin-left: auto; white-space: nowrap; }
.add-btn { margin-top: 12px; background: linear-gradient(180deg,#2f7a2f,#2f7a2f); color: #fff; border: 0; padding: 10px; border-radius: 10px; font-weight: 700; cursor: pointer; }
.add-btn:active { transform: translateY(1px); }
 
/* MOBILE OVERLAY */
.mobile-overlay { display: none; position: fixed; inset: 0; background: rgba(6,10,8,0.36); z-index: 180; opacity: 0; transition: opacity .22s ease; }
.mobile-overlay.show { display: block; opacity: 1; }
 
/* ==========================
MOBILE SIDEBAR
========================== */
@media (max-width: 900px) {
.menu-btn { display: inline-flex; }
.header-row { align-items: center; }
.container.header-row { padding-left: 12px; padding-right: 12px; }
 
```
.categories { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; align-items: stretch; }
.category { min-width: auto; }
.layout { grid-template-columns: 1fr; gap: 12px; }
 
aside.sidebar {
    position: fixed;
    left: -320px;
    top: 60px;
    bottom: 0;
    width: 280px;
    height: calc(100% - 60px);
    border-radius: 0;
    margin: 0;
    padding: 18px;
    box-shadow: 0 40px 80px rgba(6,10,8,0.18);
    z-index: 190;
    transition: left .28s cubic-bezier(.2,.9,.3,1);
    overflow-y: auto;
    background: #fff;
}
aside.sidebar.open { left: 0; }
 
.products, .product-grid, .product-list { display: grid; grid-template-columns: repeat(2, 1fr) !important; gap: 14px !important; }
```
 
}
 
/* SMALL MOBILE */
@media (max-width: 600px) {
.products, .product-grid, .product-list { grid-template-columns: repeat(2, 1fr) !important; }
.categories { grid-template-columns: repeat(2, 1fr); }
.search input { width: 100%; }
}
 
/* DESKTOP: SEARCH NEXT TO LOGO */
@media (min-width: 901px) {
.header-row {
display: flex;
align-items: center;
gap: 16px;
}
.brand { flex-shrink: 0; }
.search { flex: 1; }
.header-actions { flex-shrink: 0; }
}
 
 
 
</style>
</head>
<body>
 
<header>
  <div class="container header-row">
    <div style="display:flex;align-items:center;gap:12px">
      <button id="menuToggle" class="menu-btn" aria-label="Open menu" title="Menu">☰</button>
      <div class="brand">
        <div class="logo">GB</div>
        <div>
          <div class="title">GrabBasket</div>
          <div class="sub">Fresh & fast — 10-min delivery</div>
        </div>
      </div>
    </div>
 
    <div class="header-actions">
      <div class="search">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <path d="M21 21l-4.35-4.35" stroke="#111" stroke-width="1.6" stroke-linecap="round"/>
          <circle cx="11" cy="11" r="6" stroke="#111" stroke-width="1.6"/>
        </svg>
        <input id="globalSearch" placeholder="Search products" aria-label="Search products">
        <button id="searchBtn">Search</button>
      </div>
    </div>
  </div>
</header>
 
<!-- overlay for mobile when sidebar opens -->
<div id="mobileOverlay" class="mobile-overlay" aria-hidden="true"></div>
 
<section class="categories-row">
  <div class="container">
    <div id="categoriesContainer" class="categories">
      <!-- server-side Blade categories (keeps same) -->
      @foreach($categories as $cat)
        <div class="category {{ $activeCategory && $activeCategory->id === $cat->id ? 'active' : '' }}" data-key="{{ $cat->id }}">
          <div class="badge">{{ $cat->icon ?? '🛒' }}</div>
          <div class="label">{{ $cat->name }}</div>
        </div>
      @endforeach
    </div>
  </div>
</section>
 
<main>
  <div class="layout container">
    <!-- Sidebar -->
    <aside id="sidebar" class="sidebar" aria-hidden="false">
      <h4>Subcategories</h4>
      <div id="subList" class="sub-list">
        <div class="sub-item active" data-sub="All">
          <div class="icon">★</div>
          <div class="meta">
            <div class="name">All</div>
            <div class="hint">All items in {{ $activeCategory->name }}</div>
          </div>
          <div class="count">{{ $activeCategory->tenMinProducts->count() }}</div>
        </div>
 
        @foreach($activeCategory->filteredSubcategories as $sub)
          @php
            $subCount = $activeCategory->tenMinProducts->where('subcategory_id', $sub->id)->count();
          @endphp
          <div class="sub-item" data-sub="{{ $sub->name }}">
            <div class="icon">📦</div>
            <div class="meta">
              <div class="name">{{ $sub->name }}</div>
              <div class="hint">{{ $subCount }} items</div>
            </div>
            <div class="count">{{ $subCount }}</div>
          </div>
        @endforeach
      </div>
    </aside>
 
    <!-- Products -->
    <section>
      <div class="catalog-head">
        <div>
          <h2 id="catalogTitle">{{ $activeCategory->name }}</h2>
          <div style="color:#6b7280;font-size:13px">
            Showing <span id="showCount">{{ $activeCategory->tenMinProducts->count() }}</span> items
          </div>
        </div>
        <div>
          <select id="sortBy">
            <option value="relevance">Sort: Relevance</option>
            <option value="price-asc">Price: Low to High</option>
            <option value="price-desc">Price: High to Low</option>
          </select>
        </div>
      </div>
 
      <div id="productGrid" class="products">
        @foreach($activeCategory->tenMinProducts as $product)
          <div class="card" data-subcat="{{ $product->subcategory?->name ?? 'Other' }}">
            @if($product->discount)
              <div class="ribbon">{{ $product->discount }}% OFF</div>
            @endif
            <div class="thumb">
              <img src="{{ $product->image_url ?? 'https://via.placeholder.com/300' }}" alt="{{ $product->name }}">
            </div>
            <h4>{{ $product->name }}</h4>
            <div class="meta-sub">{{ $product->subcategory?->name ?? 'Other' }}</div>
            <div class="price-row">
              <div class="price-original">₹{{ $product->price + ($product->discount ?? 0) }}</div>
              <div class="price-now">₹{{ $product->price }}</div>
              @if($product->discount)
                <div class="discount-text">-{{ $product->discount }}%</div>
              @endif
            </div>
            <button class="add-btn">Add to cart</button>
          </div>
        @endforeach
      </div>
 
      <div id="noResults" style="display:none;margin-top:18px">
        <div class="empty">
          <h4>No products found</h4>
          <div>Try another subcategory or search</div>
        </div>
      </div>
    </section>
  </div>
</main>
 
<script>
/* ---------- assume server provides categories via Blade @json($jsCategories) ---------- */
const categoriesData = @json($jsCategories);
let activeCategory = categoriesData.find(c => c.id === {{ $activeCategory->id ?? 'null' }}) || categoriesData[0];
let activeSub = 'All';
 
/* DOM refs */
const sidebar = document.getElementById('sidebar');
const mobileOverlay = document.getElementById('mobileOverlay');
const menuToggle = document.getElementById('menuToggle');
const categoriesContainer = document.getElementById('categoriesContainer');
 
/* open/close helpers for mobile sidebar */
function openSidebar() {
    sidebar.classList.add('open');
    mobileOverlay.classList.add('show');
    sidebar.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
}
function closeSidebar() {
    sidebar.classList.remove('open');
    mobileOverlay.classList.remove('show');
    sidebar.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
}
 
/* menu toggle click (hamburger) */
menuToggle.addEventListener('click', () => {
    if (sidebar.classList.contains('open')) closeSidebar();
    else openSidebar();
});
 
/* overlay click closes sidebar */
mobileOverlay.addEventListener('click', closeSidebar);
 
/* Close on ESC */
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeSidebar();
});
 
/* Render functions (same logic you had) */
function renderCategory(cat) {
    activeCategory = cat;
    activeSub = 'All';
    document.getElementById('catalogTitle').textContent = cat.name;
 
    // Sidebar (subList)
    const subList = document.getElementById('subList');
    subList.innerHTML = '';
    const allItem = document.createElement('div');
    allItem.className = 'sub-item active';
    allItem.dataset.sub = 'All';
    allItem.innerHTML = `<div class="icon">★</div><div class="meta"><div class="name">All</div><div class="hint">All items in ${cat.name}</div></div><div class="count">${cat.products.length}</div>`;
    subList.appendChild(allItem);
 
    cat.subcategories.forEach(sub => {
        const subCount = cat.products.filter(p => p.subcategory === sub.name).length;
        const subItem = document.createElement('div');
        subItem.className = 'sub-item';
        subItem.dataset.sub = sub.name;
        subItem.innerHTML = `<div class="icon">📦</div><div class="meta"><div class="name">${sub.name}</div><div class="hint">${subCount} items</div></div><div class="count">${subCount}</div>`;
        subList.appendChild(subItem);
    });
 
    renderProducts();
    attachSubItemEvents();
}
 
/* render products */
function renderProducts() {
    const productGrid = document.getElementById('productGrid');
    productGrid.innerHTML = '';
    const products = activeCategory.products.filter(p => activeSub === 'All' || p.subcategory === activeSub);
 
    if(products.length === 0){
        document.getElementById('noResults').style.display = 'block';
    } else {
        document.getElementById('noResults').style.display = 'none';
        products.forEach(p => {
            const card = document.createElement('div');
            card.className = 'card';
            card.dataset.subcat = p.subcategory;
            card.innerHTML = `
              ${p.discount ? `<div class="ribbon">${p.discount}% OFF</div>` : ''}
              <div class="thumb"><img src="${p.img}" alt="${p.name}"></div>
              <h4>${p.name}</h4>
              <div class="meta-sub">${p.subcategory}</div>
              <div class="price-row">
                <div class="price-original">₹${p.price + (p.discount || 0)}</div>
                <div class="price-now">₹${p.price}</div>
                ${p.discount ? `<div class="discount-text">-${p.discount}%</div>` : ''}
              </div>
              <button class="add-btn">Add to cart</button>
            `;
            productGrid.appendChild(card);
        });
    }
    document.getElementById('showCount').textContent = products.length;
    attachAddBtnEvents();
}
 
/* sub-item click events (in sidebar) */
function attachSubItemEvents(){
    document.querySelectorAll('.sub-item').forEach(item => {
        item.addEventListener('click', () => {
            document.querySelectorAll('.sub-item').forEach(i => i.classList.remove('active'));
            item.classList.add('active');
            activeSub = item.dataset.sub;
            renderProducts();
 
            // On mobile close sidebar automatically after picking subcategory
            if (window.innerWidth <= 900) closeSidebar();
        });
    });
}
 
/* add to cart button interactions */
function attachAddBtnEvents(){
    document.querySelectorAll('.add-btn').forEach(btn=>{
        btn.addEventListener('click',()=>{
            btn.textContent='Added ✓';
            setTimeout(()=>btn.textContent='Add to cart',700);
        });
    });
}
 
/* Category switching (top categories row) */
function attachCategoryButtons() {
    document.querySelectorAll('.category').forEach(catEl=>{
        catEl.addEventListener('click',()=>{
            document.querySelectorAll('.category').forEach(c=>c.classList.remove('active'));
            catEl.classList.add('active');
            const catId = parseInt(catEl.dataset.key);
            const cat = categoriesData.find(c => c.id === catId);
            if (cat) renderCategory(cat);
 
            // close sidebar on mobile when category selected
            if (window.innerWidth <= 900) closeSidebar();
        });
    });
}
 
/* Initial render & attach UI events */
renderCategory(activeCategory);
attachCategoryButtons();
 
/* extra: close sidebar if window resized to desktop so no overlay stuck */
window.addEventListener('resize', () => {
    if (window.innerWidth > 900) {
        closeSidebar(); // ensure overlay hidden
        document.body.style.overflow = '';
    }
});
 
/* optional: simple search button behavior (client-side filter) */
document.getElementById('searchBtn').addEventListener('click', () => {
    const q = document.getElementById('globalSearch').value.trim().toLowerCase();
    if (!q) {
        // reset to category
        activeSub = 'All';
        renderProducts();
        return;
    }
    // filter within activeCategory.products
    const productGrid = document.getElementById('productGrid');
    productGrid.innerHTML = '';
    const matched = activeCategory.products.filter(p => (p.name || '').toLowerCase().includes(q) || (p.subcategory || '').toLowerCase().includes(q));
    if (matched.length === 0) {
        document.getElementById('noResults').style.display = 'block';
    } else {
        document.getElementById('noResults').style.display = 'none';
        matched.forEach(p => {
            const card = document.createElement('div');
            card.className = 'card';
            card.innerHTML = `
              ${p.discount ? `<div class="ribbon">${p.discount}% OFF</div>` : ''}
              <div class="thumb"><img src="${p.img}" alt="${p.name}"></div>
              <h4>${p.name}</h4>
              <div class="meta-sub">${p.subcategory}</div>
              <div class="price-row">
                <div class="price-original">₹${p.price + (p.discount || 0)}</div>
                <div class="price-now">₹${p.price}</div>
                ${p.discount ? `<div class="discount-text">-${p.discount}%</div>` : ''}
              </div>
              <button class="add-btn">Add to cart</button>
            `;
            productGrid.appendChild(card);
        });
    }
    attachAddBtnEvents();
});
</script>
</body>
</html>
 
 