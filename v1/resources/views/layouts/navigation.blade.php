<nav class="bg-[#232f3e] text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <div class="flex items-center gap-6">
                <a href="{{ route('home') }}" class="text-2xl font-bold text-[#ff9900]">grabbasket</a>
                <a href="#" class="hidden md:inline-block hover:text-[#ff9900]" data-bs-toggle="modal" data-bs-target="#navbarCategoryMenuModal">Shop</a>
                @auth
                    <a href="{{ route('cart.index') }}" class="hidden md:inline-block hover:text-[#ff9900]">Cart</a>
                @endauth
</nav>

<!-- Navbar Category Modal -->
<div class="modal fade" id="navbarCategoryMenuModal" tabindex="-1" aria-labelledby="navbarCategoryMenuModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border-radius: 16px; overflow: hidden;">
            <div class="modal-header" style="background: linear-gradient(135deg, #8B4513, #A0522D); color: #fff;">
                <h5 class="modal-title" id="navbarCategoryMenuModalLabel">Shop by Category</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3 p-md-4">
                @include('components.category-menu', ['categories' => $categories ?? collect([])])
                @if(!isset($categories) || !$categories->count())
                    <div class="text-center text-muted py-5">No categories available right now.</div>
                @endif
            </div>
        </div>
    </div>
</div>
                @auth
                    <a href="{{ route('cart.index') }}" class="hidden md:inline-block hover:text-[#ff9900]">Cart</a>
                @endauth
            </div>
            <div class="flex-1 max-w-xl mx-4 hidden md:block">
                <form action="{{ route('buyer.dashboard') }}" method="GET" class="flex">
                                        <div class="position-relative w-100" style="max-width:340px;">
                                            <input type="text" name="q" placeholder="Search for products, brands, categories..." class="form-control premium-navbar-search" style="border-radius:32px;background:rgba(255,255,255,0.85);box-shadow:0 2px 12px #ffd70033;border:none;padding-left:2.5rem;font-size:1.05rem;color:#1CA9C9;font-weight:500;" />
                                            <span class="position-absolute top-50 start-0 translate-middle-y ps-3" style="color:#F43397;font-size:1.2rem;">
                                                <i class="bi bi-search"></i>
                                            </span>
                                        </div>
                </form>
            </div>
            <div class="flex items-center gap-4">
                @auth
                    @php
                        $user = Auth::user();
                        $gender = strtolower($user->sex ?? '');
                        $emoji = '👤';
                        if ($gender === 'male' || $gender === 'm') {
                            $emoji = '👨';
                        } elseif ($gender === 'female' || $gender === 'f') {
                            $emoji = '👩';
                        } elseif ($gender === 'other' || $gender === 'nonbinary' || $gender === 'nb') {
                            $emoji = '🧑';
                        }
                    @endphp
                    @if($user)
                        <span class="hidden md:inline user-greeting-interactive">Hello, {{ $user->name ?? 'User' }} <span class="greeting-emoji">{{ $emoji }}</span></span>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="px-3 py-2 bg-white/10 rounded hover:bg-white/20">Logout</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="px-3 py-2 bg-white/10 rounded hover:bg-white/20">Login</a>
                @endauth
            </div>
        </div>
    </div>
</nav>
