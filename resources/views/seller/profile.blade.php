@extends('seller.layouts.app')

@section('title', 'Seller Profile')

@push('styles')
<style>
    /* Profile Card */
    .profile-header {
        background: linear-gradient(135deg, #0d6efd, #6c63ff);
        color: white;
        border-radius: 10px 10px 0 0;
        padding: 20px;
        text-align: center;
    }

    .profile-header h2 {
        font-weight: 700;
    }

    .profile-card {
        border: none;
        border-radius: 12px;
        overflow: hidden;
    }

    .profile-avatar {
        width: 90px;
        height: 90px;
        border-radius: 50%;
        border: 3px solid white;
        object-fit: cover;
        margin-top: -45px;
    }

    /* Profile Photo Wrapper (WhatsApp/Instagram Style) */
    .profile-photo-wrapper {
        position: relative;
        display: inline-block;
        margin-top: -45px;
    }

    .profile-photo-wrapper:hover .profile-photo-edit-btn {
        opacity: 1;
    }

    .profile-photo-edit-btn {
        position: absolute;
        bottom: 5px;
        right: 5px;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: #0d6efd;
        border: 2px solid white;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        opacity: 0.9;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    }

    .profile-photo-edit-btn:hover {
        background: #0b5ed7;
        transform: scale(1.1);
        opacity: 1;
    }

    .profile-photo-edit-btn i {
        font-size: 14px;
    }

    /* Photo Menu Dropdown */
    .profile-photo-actions {
        position: relative;
    }

    .photo-menu {
        position: absolute;
        bottom: 45px;
        right: 0;
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        padding: 8px;
        min-width: 180px;
        display: none;
        z-index: 1000;
        animation: menuSlideUp 0.2s ease-out;
    }

    .photo-menu.active {
        display: block;
    }

    @keyframes menuSlideUp {
        from {
            transform: translateY(10px);
            opacity: 0;
        }

        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    .photo-menu button {
        width: 100%;
        padding: 10px 15px;
        border: none;
        background: white;
        text-align: left;
        border-radius: 8px;
        cursor: pointer;
        transition: background 0.2s;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 14px;
        color: #333;
    }

    .photo-menu button:hover {
        background: #f0f2f5;
    }

    .photo-menu button i {
        font-size: 18px;
        color: #0d6efd;
    }

    /* Avatar Picker Modal */
    .avatar-picker-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
        gap: 15px;
        margin: 20px 0;
        max-height: 400px;
        overflow-y: auto;
    }

    .avatar-option {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        cursor: pointer;
        border: 3px solid transparent;
        transition: all 0.3s;
        object-fit: cover;
        background: #f8f9fa;
    }

    .avatar-option:hover {
        transform: scale(1.1);
        border-color: #0d6efd;
        box-shadow: 0 4px 12px rgba(13, 110, 253, 0.3);
    }

    .avatar-option.selected {
        border-color: #0d6efd;
        box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.2);
    }

    /* Emoji Picker Modal */
    .emoji-picker-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(60px, 1fr));
        gap: 10px;
        margin: 20px 0;
        max-height: 400px;
        overflow-y: auto;
    }

    .emoji-option {
        width: 60px;
        height: 60px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 32px;
        cursor: pointer;
        border-radius: 12px;
        border: 2px solid transparent;
        transition: all 0.2s;
        background: #f8f9fa;
    }

    .emoji-option:hover {
        transform: scale(1.2);
        background: #e9ecef;
        border-color: #0d6efd;
    }

    .emoji-option.selected {
        background: #e7f1ff;
        border-color: #0d6efd;
    }

    /* Upload Modal Overlay */
    .photo-upload-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        backdrop-filter: blur(5px);
    }

    .photo-upload-overlay.active {
        display: flex;
    }

    .photo-upload-modal {
        background: white;
        border-radius: 20px;
        padding: 30px;
        max-width: 500px;
        width: 90%;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
        animation: modalSlideUp 0.3s ease-out;
    }

    @keyframes modalSlideUp {
        from {
            transform: translateY(50px);
            opacity: 0;
        }

        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    .preview-photo-container {
        width: 200px;
        height: 200px;
        border-radius: 50%;
        overflow: hidden;
        margin: 20px auto;
        border: 3px solid #0d6efd;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f8f9fa;
    }

    .preview-photo-container img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    /* Store info box */
    .info-box {
        background: #f9fafb;
        border: 1px solid #e0e0e0;
        border-radius: 10px;
        padding: 15px;
        transition: 0.3s;
    }

    .info-box:hover {
        background: #f1f5ff;
        border-color: #b3c7ff;
    }

    /* Product Cards */
    .product-card {
        border: none;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .product-card:hover {
        transform: translateY(-5px);
        box-shadow: 0px 6px 16px rgba(0, 0, 0, 0.1);
    }

    .product-card img {
        transition: transform 0.3s;
    }

    .product-card:hover img {
        transform: scale(1.05);
    }
</style>
@endpush

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">

        {{-- Seller Profile --}}
        <div class="card profile-card shadow mb-4">
            <div class="profile-header">
                <h2>Seller Profile</h2>
            </div>
            <div class="card-body text-center">
                @auth
                @php
                $profilePhoto = Auth::user()->profile_picture
                ? Auth::user()->profile_picture
                : "https://ui-avatars.com/api/?name=" . urlencode($seller->name) . "&background=0d6efd&color=fff";
                @endphp
                @else
                @php
                $profilePhoto = "https://ui-avatars.com/api/?name=" . urlencode($seller->name) . "&background=0d6efd&color=fff";
                @endphp
                @endauth

                <!-- Clickable Profile Photo (WhatsApp/Instagram Style) -->
                <div class="profile-photo-wrapper position-relative d-inline-block">
                    <img src="{{ $profilePhoto }}"
                        alt="Avatar" class="profile-avatar shadow" id="profileAvatarImg">

                    @auth
                    @if(Auth::user()->email === $seller->email)
                    <!-- Camera overlay button with dropdown menu (only for own profile) -->
                    <div class="profile-photo-actions">
                        <button type="button" class="profile-photo-edit-btn" onclick="togglePhotoMenu()" title="Change profile photo">
                            <i class="bi bi-camera-fill"></i>
                        </button>

                        <!-- Dropdown menu for photo options -->
                        <div class="photo-menu" id="photoMenu">
                            <button type="button" onclick="document.getElementById('quickProfilePhotoInput').click(); togglePhotoMenu();">
                                <i class="bi bi-camera"></i> Upload Photo
                            </button>
                            <button type="button" onclick="showAvatarPicker(); togglePhotoMenu();">
                                <i class="bi bi-person-circle"></i> Choose Avatar
                            </button>
                            <button type="button" onclick="showEmojiPicker(); togglePhotoMenu();">
                                <i class="bi bi-emoji-smile"></i> Choose Emoji
                            </button>
                        </div>
                    </div>

                    <!-- Hidden file input for quick photo change -->
                    <form id="quickPhotoUploadForm" method="POST" action="{{ route('seller.updateProfile') }}" enctype="multipart/form-data" style="display: none;">
                        @csrf
                        <input type="file" name="profile_photo" id="quickProfilePhotoInput" accept="image/jpeg,image/jpg,image/png,image/gif" onchange="handleQuickPhotoUpload(this)">
                    </form>
                    @endif
                    @endauth
                </div>

                <h4 class="mt-3">{{ $seller->name }}</h4>
                <p class="text-muted">📍 {{ $seller->city }}, {{ $seller->state }}</p>
                <div class="mt-3">
                    <p><i class="bi bi-envelope-fill text-primary"></i> <strong>Email:</strong> {{ $seller->email }}</p>
                    <p><i class="bi bi-telephone-fill text-success"></i> <strong>Phone:</strong> {{ $seller->phone }}</p>
                </div>
            </div>
        </div>

        {{-- Store Information --}}
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="info-box"><strong>Store Name:</strong> {{ $seller->store_name ?? 'N/A' }}</div>
            </div>
            <div class="col-md-6">
                <div class="info-box"><strong>GST Number:</strong> {{ $seller->gst_number ?? 'N/A' }}</div>
            </div>
            <div class="col-md-6">
                <div class="info-box"><strong>Store Address:</strong> {{ $seller->store_address ?? 'N/A' }}</div>
            </div>
            <div class="col-md-6">
                <div class="info-box"><strong>Store Contact:</strong> {{ $seller->store_contact ?? 'N/A' }}</div>
            </div>
        </div>

        {{-- Authenticated Seller Options --}}
        @auth
        @if(Auth::user()->email === $seller->email)
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <a href="{{ route('seller.createProduct') }}" class="btn btn-warning w-100 mb-3 fw-semibold shadow">
                    <i class="bi bi-plus-circle"></i> Add Product
                </a>

                <form method="POST" action="{{ route('seller.updateProfile') }}" class="border rounded p-3 bg-light" enctype="multipart/form-data">
                    @csrf
                    <h5 class="fw-bold mb-3">Update Store Info</h5>

                    <!-- Profile Photo Upload -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Profile Photo</label>
                        @if(Auth::user()->profile_picture)
                        <div class="mb-2">
                            <img src="{{ Auth::user()->profile_picture }}" alt="Current Photo"
                                class="img-thumbnail" style="max-width: 150px; max-height: 150px;">
                            <p class="text-muted small mt-1">Current profile photo</p>
                        </div>
                        @endif
                        <input type="file" name="profile_photo" class="form-control" accept="image/jpeg,image/jpg,image/png,image/gif" id="profilePhotoInput">
                        <small class="text-muted">Accepted formats: JPEG, JPG, PNG, GIF (Max: 2MB)</small>
                        @if($errors->has('profile_photo'))
                        <div class="text-danger small mt-1">{{ $errors->first('profile_photo') }}</div>
                        @endif
                        <!-- Image Preview -->
                        <div id="imagePreview" class="mt-2" style="display: none;">
                            <img id="previewImg" src="" alt="Preview" class="img-thumbnail" style="max-width: 150px; max-height: 150px;">
                            <p class="text-muted small mt-1">New photo preview</p>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Store Name</label>
                        <input type="text" name="store_name" class="form-control"
                            value="{{ old('store_name', $seller->store_name) }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">GST Number (optional)</label>
                        <input type="text" name="gst_number" class="form-control"
                            value="{{ old('gst_number', $seller->gst_number) }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Store Address</label>
                        <input type="text" name="store_address" class="form-control"
                            value="{{ old('store_address', $seller->store_address) }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Store Contact</label>
                        <input type="text" name="store_contact" class="form-control"
                            value="{{ old('store_contact', $seller->store_contact) }}">
                    </div>
                    <button type="submit" class="btn btn-primary w-100 fw-semibold">Update</button>
                </form>
            </div>
        </div>
        @endif
        @endauth

        {{-- Products --}}
        <div class="card shadow">
            <div class="card-body">
                <h4 class="fw-bold text-secondary mb-4">Products</h4>
                @if($products->count())
                <div class="row g-4">
                    @foreach($products->sortByDesc('created_at') as $p)
                    <div class="col-md-6 col-lg-4">
                        <div class="card product-card h-100 shadow-sm">
                            <div class="card-body text-center">
                                @if($p->image || $p->image_data)
                                <img src="{{ $p->image_url }}" class="rounded mb-3 border shadow-sm"
                                    style="width:120px; height:120px; object-fit:cover;" alt="{{ $p->name }}">
                                @else
                                <div class="text-muted fs-1">🖼</div>
                                @endif
                                <h6 class="fw-bold text-primary">{{ $p->name }}</h6>
                                <div class="text-muted small mb-2">{{ optional($p->category)->name }} / {{ optional($p->subcategory)->name }}</div>
                                <div class="fw-bold text-success">₹{{ number_format($p->price, 2) }}</div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <p class="text-center text-muted">No products yet.</p>
                @endif
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
    // Photo Menu Toggle
    function togglePhotoMenu() {
        const menu = document.getElementById('photoMenu');
        menu.classList.toggle('active');
    }

    // Close menu when clicking outside
    document.addEventListener('click', function(e) {
        const menu = document.getElementById('photoMenu');
        const btn = document.querySelector('.profile-photo-edit-btn');
        if (menu && btn && !menu.contains(e.target) && !btn.contains(e.target)) {
            menu.classList.remove('active');
        }
    });

    // Human Avatar Options (Professional, Diverse, Inclusive)
    const humanAvatars = [
        'https://api.dicebear.com/7.x/avataaars/svg?seed=Felix&backgroundColor=b6e3f4',
        'https://api.dicebear.com/7.x/avataaars/svg?seed=Aneka&backgroundColor=c0aede',
        'https://api.dicebear.com/7.x/avataaars/svg?seed=Mittens&backgroundColor=ffd5dc',
        'https://api.dicebear.com/7.x/avataaars/svg?seed=Leo&backgroundColor=ffdfbf',
        'https://api.dicebear.com/7.x/avataaars/svg?seed=Sophia&backgroundColor=d1d4f9',
        'https://api.dicebear.com/7.x/avataaars/svg?seed=John&backgroundColor=c7ceea',
        'https://api.dicebear.com/7.x/avataaars/svg?seed=Emma&backgroundColor=b6e3f4',
        'https://api.dicebear.com/7.x/avataaars/svg?seed=Oliver&backgroundColor=ffd5dc',
        'https://api.dicebear.com/7.x/avataaars/svg?seed=Ava&backgroundColor=c0aede',
        'https://api.dicebear.com/7.x/avataaars/svg?seed=William&backgroundColor=ffdfbf',
        'https://api.dicebear.com/7.x/avataaars/svg?seed=Isabella&backgroundColor=d1d4f9',
        'https://api.dicebear.com/7.x/avataaars/svg?seed=James&backgroundColor=c7ceea',
        'https://api.dicebear.com/7.x/micah/svg?seed=Alex&backgroundColor=b6e3f4',
        'https://api.dicebear.com/7.x/micah/svg?seed=Sam&backgroundColor=c0aede',
        'https://api.dicebear.com/7.x/micah/svg?seed=Jordan&backgroundColor=ffd5dc',
        'https://api.dicebear.com/7.x/micah/svg?seed=Taylor&backgroundColor=ffdfbf',
        'https://api.dicebear.com/7.x/micah/svg?seed=Morgan&backgroundColor=d1d4f9',
        'https://api.dicebear.com/7.x/micah/svg?seed=Riley&backgroundColor=c7ceea',
        'https://api.dicebear.com/7.x/personas/svg?seed=Charlie&backgroundColor=b6e3f4',
        'https://api.dicebear.com/7.x/personas/svg?seed=Dakota&backgroundColor=c0aede',
        'https://api.dicebear.com/7.x/personas/svg?seed=Skyler&backgroundColor=ffd5dc',
        'https://api.dicebear.com/7.x/personas/svg?seed=Cameron&backgroundColor=ffdfbf',
        'https://api.dicebear.com/7.x/personas/svg?seed=Avery&backgroundColor=d1d4f9',
        'https://api.dicebear.com/7.x/personas/svg?seed=Quinn&backgroundColor=c7ceea'
    ];

    // Store & Business Emoji Options
    const storeEmojis = [
        '🏪', '🏬', '🏭', '🏢', '🏛️', '🏗️', '🏚️', '🏘️',
        '🛍️', '🛒', '🛵', '🚚', '📦', '📮', '🎁', '🎀',
        '💼', '💰', '💳', '💎', '💍', '👔', '👗', '👠',
        '🍔', '🍕', '🍜', '🍰', '☕', '🍷', '🥘', '🍱',
        '📱', '💻', '⌚', '📷', '🎮', '🎸', '🎨', '📚',
        '🌟', '⭐', '✨', '🔥', '💫', '🌈', '🎯', '🎪',
        '🏆', '🥇', '🎖️', '🏅', '🎗️', '🎫', '🎉', '🎊',
        '🌸', '🌺', '🌻', '🌹', '🌷', '🌼', '🍀', '🌿'
    ];

    // Show Avatar Picker
    function showAvatarPicker() {
        const overlay = document.createElement('div');
        overlay.className = 'photo-upload-overlay active';
        overlay.innerHTML = `
      <div class="photo-upload-modal" style="max-width: 600px;">
        <div class="text-center">
          <h5 class="mb-3"><i class="bi bi-person-circle"></i> Choose Your Avatar</h5>
          <p class="text-muted small">Select a professional avatar for your profile</p>
          <div class="avatar-picker-grid">
            ${humanAvatars.map((avatar, index) => `
              <img src="${avatar}" 
                   class="avatar-option" 
                   data-avatar="${avatar}"
                   onclick="selectAvatar(this, '${avatar}')"
                   alt="Avatar ${index + 1}">
            `).join('')}
          </div>
          <div class="d-flex gap-2 justify-content-center mt-3">
            <button type="button" class="btn btn-secondary" onclick="closePhotoModal()">
              <i class="bi bi-x-circle"></i> Cancel
            </button>
            <button type="button" class="btn btn-primary" id="confirmAvatarBtn" disabled onclick="confirmAvatar()">
              <i class="bi bi-check-circle"></i> Use This Avatar
            </button>
          </div>
        </div>
      </div>
    `;
        document.body.appendChild(overlay);

        // Close on overlay click
        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) {
                closePhotoModal();
            }
        });
    }

    let selectedAvatarUrl = '';

    function selectAvatar(element, url) {
        // Remove previous selection
        document.querySelectorAll('.avatar-option').forEach(el => el.classList.remove('selected'));

        // Mark as selected
        element.classList.add('selected');
        selectedAvatarUrl = url;

        // Enable confirm button
        document.getElementById('confirmAvatarBtn').disabled = false;
    }

    function confirmAvatar() {
        if (!selectedAvatarUrl) return;

        // Show loading
        const modal = document.querySelector('.photo-upload-modal');
        modal.innerHTML = `
      <div class="text-center py-4">
        <div class="spinner-border text-primary mb-3" role="status">
          <span class="visually-hidden">Updating...</span>
        </div>
        <h5>Updating your avatar...</h5>
        <p class="text-muted">Please wait</p>
      </div>
    `;

        // Update avatar via AJAX
        fetch('{{ route("seller.updateProfile") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    avatar_url: selectedAvatarUrl
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update profile photo
                    const cacheBuster = '?t=' + new Date().getTime();
                    document.getElementById('profileAvatarImg').src = selectedAvatarUrl + cacheBuster;

                    modal.innerHTML = `
          <div class="text-center py-4">
            <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
            <h5 class="mt-3 text-success">Success!</h5>
            <p class="text-muted">Avatar updated successfully</p>
          </div>
        `;

                    setTimeout(() => {
                        closePhotoModal();
                        window.location.reload(true);
                    }, 1500);
                } else {
                    throw new Error(data.message || 'Update failed');
                }
            })
            .catch(error => {
                console.error('Avatar update error:', error);
                modal.innerHTML = `
        <div class="text-center py-4">
          <i class="bi bi-x-circle-fill text-danger" style="font-size: 4rem;"></i>
          <h5 class="mt-3 text-danger">Update Failed</h5>
          <p class="text-muted">${error.message}</p>
          <button class="btn btn-secondary mt-3" onclick="closePhotoModal()">Close</button>
        </div>
      `;
            });
    }

    // Show Emoji Picker
    function showEmojiPicker() {
        const overlay = document.createElement('div');
        overlay.className = 'photo-upload-overlay active';
        overlay.innerHTML = `
      <div class="photo-upload-modal" style="max-width: 600px;">
        <div class="text-center">
          <h5 class="mb-3"><i class="bi bi-emoji-smile"></i> Choose Your Store Emoji</h5>
          <p class="text-muted small">Pick an emoji that represents your business</p>
          <div class="emoji-picker-grid">
            ${storeEmojis.map(emoji => `
              <div class="emoji-option" 
                   data-emoji="${emoji}"
                   onclick="selectEmoji(this, '${emoji}')">
                ${emoji}
              </div>
            `).join('')}
          </div>
          <div class="d-flex gap-2 justify-content-center mt-3">
            <button type="button" class="btn btn-secondary" onclick="closePhotoModal()">
              <i class="bi bi-x-circle"></i> Cancel
            </button>
            <button type="button" class="btn btn-primary" id="confirmEmojiBtn" disabled onclick="confirmEmoji()">
              <i class="bi bi-check-circle"></i> Use This Emoji
            </button>
          </div>
        </div>
      </div>
    `;
        document.body.appendChild(overlay);

        // Close on overlay click
        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) {
                closePhotoModal();
            }
        });
    }

    let selectedEmoji = '';

    function selectEmoji(element, emoji) {
        // Remove previous selection
        document.querySelectorAll('.emoji-option').forEach(el => el.classList.remove('selected'));

        // Mark as selected
        element.classList.add('selected');
        selectedEmoji = emoji;

        // Enable confirm button
        document.getElementById('confirmEmojiBtn').disabled = false;
    }

    function confirmEmoji() {
        if (!selectedEmoji) return;

        // Generate emoji avatar URL (using a service that renders emoji as image)
        const emojiUrl = `https://emojicdn.elk.sh/${selectedEmoji}?style=apple`;

        // Show loading
        const modal = document.querySelector('.photo-upload-modal');
        modal.innerHTML = `
      <div class="text-center py-4">
        <div class="spinner-border text-primary mb-3" role="status">
          <span class="visually-hidden">Updating...</span>
        </div>
        <h5>Updating your emoji...</h5>
        <p class="text-muted">Please wait</p>
      </div>
    `;

        // Update emoji via AJAX
        fetch('{{ route("seller.updateProfile") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    avatar_url: emojiUrl
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update profile photo
                    const cacheBuster = '?t=' + new Date().getTime();
                    document.getElementById('profileAvatarImg').src = emojiUrl + cacheBuster;

                    modal.innerHTML = `
          <div class="text-center py-4">
            <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
            <h5 class="mt-3 text-success">Success!</h5>
            <p class="text-muted">Emoji updated successfully</p>
          </div>
        `;

                    setTimeout(() => {
                        closePhotoModal();
                        window.location.reload(true);
                    }, 1500);
                } else {
                    throw new Error(data.message || 'Update failed');
                }
            })
            .catch(error => {
                console.error('Emoji update error:', error);
                modal.innerHTML = `
        <div class="text-center py-4">
          <i class="bi bi-x-circle-fill text-danger" style="font-size: 4rem;"></i>
          <h5 class="mt-3 text-danger">Update Failed</h5>
          <p class="text-muted">${error.message}</p>
          <button class="btn btn-secondary mt-3" onclick="closePhotoModal()">Close</button>
        </div>
      `;
            });
    }

    // Quick Photo Upload (WhatsApp/Instagram Style)
    function handleQuickPhotoUpload(input) {
        const file = input.files[0];
        if (!file) return;

        // Validate file size (2MB max)
        if (file.size > 2097152) {
            alert('❌ File size must be less than 2MB');
            input.value = '';
            return;
        }

        // Validate file type
        const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        if (!validTypes.includes(file.type)) {
            alert('❌ Please select a valid image file (JPEG, JPG, PNG, or GIF)');
            input.value = '';
            return;
        }

        // Show preview in modal
        const reader = new FileReader();
        reader.onload = function(e) {
            showPhotoPreviewModal(e.target.result, file);
        };
        reader.readAsDataURL(file);
    }

    // Show Photo Preview Modal (Instagram Style)
    function showPhotoPreviewModal(imageData, file) {
        // Create modal overlay
        const overlay = document.createElement('div');
        overlay.className = 'photo-upload-overlay active';
        overlay.innerHTML = `
        <div class="photo-upload-modal">
          <div class="text-center">
            <h5 class="mb-3"><i class="bi bi-image"></i> Update Profile Photo</h5>
            <div class="preview-photo-container">
              <img src="${imageData}" alt="Preview">
            </div>
            <p class="text-muted small mb-3">
              <i class="bi bi-info-circle"></i> ${file.name} (${(file.size / 1024).toFixed(2)} KB)
            </p>
            <div class="d-flex gap-2 justify-content-center">
              <button type="button" class="btn btn-secondary" onclick="closePhotoModal()">
                <i class="bi bi-x-circle"></i> Cancel
              </button>
              <button type="button" class="btn btn-primary" onclick="submitQuickPhoto()">
                <i class="bi bi-check-circle"></i> Update Photo
              </button>
            </div>
          </div>
        </div>
      `;
        document.body.appendChild(overlay);

        // Close on overlay click
        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) {
                closePhotoModal();
            }
        });
    }

    // Close photo modal
    function closePhotoModal() {
        const overlay = document.querySelector('.photo-upload-overlay');
        if (overlay) {
            overlay.classList.remove('active');
            setTimeout(() => overlay.remove(), 300);
        }
        // Reset file input
        document.getElementById('quickProfilePhotoInput').value = '';
    }

    // Submit photo via AJAX
    function submitQuickPhoto() {
        const form = document.getElementById('quickPhotoUploadForm');
        const formData = new FormData(form);

        // Show loading state
        const modal = document.querySelector('.photo-upload-modal');
        modal.innerHTML = `
        <div class="text-center py-4">
          <div class="spinner-border text-primary mb-3" role="status">
            <span class="visually-hidden">Uploading...</span>
          </div>
          <h5>Uploading your photo...</h5>
          <p class="text-muted">Please wait</p>
        </div>
      `;

        console.log('Uploading photo...');

        // Submit form
        fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}'
                }
            })
            .then(response => {
                console.log('Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                if (data.success) {
                    // Update profile photo with cache-busting timestamp
                    const cacheBuster = '?t=' + new Date().getTime();
                    const newPhotoUrl = data.photo_url + cacheBuster;
                    console.log('New photo URL:', newPhotoUrl);
                    document.getElementById('profileAvatarImg').src = newPhotoUrl;

                    // Show success message
                    modal.innerHTML = `
            <div class="text-center py-4">
              <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
              <h5 class="mt-3 text-success">Success!</h5>
              <p class="text-muted">Profile photo updated successfully</p>
            </div>
          `;

                    setTimeout(() => {
                        closePhotoModal();
                        // Force reload to show new photo everywhere (header, sidebar, etc.)
                        window.location.reload(true);
                    }, 1500);
                } else {
                    throw new Error(data.message || 'Upload failed');
                }
            })
            .catch(error => {
                console.error('Upload error:', error);
                modal.innerHTML = `
          <div class="text-center py-4">
            <i class="bi bi-x-circle-fill text-danger" style="font-size: 4rem;"></i>
            <h5 class="mt-3 text-danger">Upload Failed</h5>
            <p class="text-muted">${error.message || 'An error occurred. Please try again.'}</p>
            <button class="btn btn-secondary mt-3" onclick="closePhotoModal()">Close</button>
          </div>
        `;
            });
    }

    // Profile Photo Preview (for form upload)
    const photoInput = document.getElementById('profilePhotoInput');
    const imagePreview = document.getElementById('imagePreview');
    const previewImg = document.getElementById('previewImg');
    const profileAvatarImg = document.getElementById('profileAvatarImg');

    if (photoInput) {
        photoInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Check file size (2MB = 2097152 bytes)
                if (file.size > 2097152) {
                    alert('File size must be less than 2MB');
                    photoInput.value = '';
                    imagePreview.style.display = 'none';
                    return;
                }

                // Check file type
                const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (!validTypes.includes(file.type)) {
                    alert('Please select a valid image file (JPEG, JPG, PNG, or GIF)');
                    photoInput.value = '';
                    imagePreview.style.display = 'none';
                    return;
                }

                // Show preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    imagePreview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                imagePreview.style.display = 'none';
            }
        });
    }
</script>
@endpush