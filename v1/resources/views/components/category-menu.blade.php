
@php
  $categories = $categories ?? app('App\Services\CategoryService')->getCategories();
@endphp

<div class="categories-3d-container">
  @foreach($categories as $cat)
    <div class="category-3d-card" data-category="{{ $cat->name }}" style="opacity: 1; transform: translateY(0px); transition: 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275);">
      <a href="{{ route('buyer.productsByCategory', $cat->id) }}" class="category-3d-link">
        <div class="category-3d-content">
          @php
            $emoji = $cat->emoji;
            if (!$emoji) {
              $normalized = strtoupper(trim(preg_replace('/[^A-Z0-9]/', '', $cat->name)));
              $emojiMap = [
                'ELECTRONICS' => '🖥️', 'CLOTHING' => '🧥', 'FASHIONCLOTHING' => '👗', 'BOOKS' => '📖',
                'HOMEKITCHEN' => '🍽️', 'HOME' => '🏠', 'BEAUTY' => '�', 'SPORTS' => '🏀', 'KIDS' => '🧸',
                'FOOD' => '🍕', 'JEWELLERY' => '💍', 'GROCERY' => '🛒', 'TOYS' => '🪁', 'FURNITURE' => '🛋️',
                'MOBILE' => '📱', 'LAPTOPS' => '💻', 'SHOES' => '👟', 'WATCHES' => '⌚', 'BAGS' => '🧳',
                'ACCESSORIES' => '🕶️', 'HEALTH' => '🩺', 'AUTOMOTIVE' => '🚙', 'MENSFASHION' => '👔',
                'WOMENSFASHION' => '👗', 'BEAUTYCARE' => '💄', 'SPORTSNAME' => '🏃‍♂️', 'BOOKSEDUCATION' => '📚',
                'KIDSTOYS' => '🧸', 'HEALTHWELLNESS' => '🏥', 'JEWELRYACCESSORIES' => '💎', 'GROCERYFOOD' => '🥬',
                'GARDENOUTDOOR' => '🌸', 'PETSUPPLIES' => '🐾', 'BABYPRODUCTS' => '👶',
              ];
              $emoji = $emojiMap[$normalized] ?? '🛍️';
            }
          @endphp
          <div class="category-emoji-3d">{{ $emoji }}</div>
          <div class="category-name-3d">{{ strtoupper($cat->name) }}</div>
          <div class="category-glow"></div>
        </div>
      </a>
    </div>
  @endforeach
</div>

<style>
  .category-3d-card {
    position: relative;
    transform-style: preserve-3d;
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    cursor: pointer;
    height: 250px;
  }

  .category-3d-card:hover {
    transform: rotateX(10deg) rotateY(15deg) scale(1.1);
    z-index: 10;
  }

  .category-3d-link {
    display: block;
    text-decoration: none;
    height: 100%;
  }

  .category-3d-content {
    position: relative;
    height: 100%;
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    border-radius: 20px;
    box-shadow: 
      0 10px 30px rgba(0, 0, 0, 0.1),
      0 1px 8px rgba(0, 0, 0, 0.05),
      inset 0 1px 0 rgba(255, 255, 255, 0.8);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 20px;
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    border: 1px solid rgba(255, 255, 255, 0.3);
    backdrop-filter: blur(10px);
  }

  .category-3d-card:hover .category-3d-content {
    background: linear-gradient(135deg, #ff9900 0%, #1CA9C9 50%, #F43397 100%);
    box-shadow: 
      0 20px 60px rgba(255, 153, 0, 0.3),
      0 10px 30px rgba(28, 169, 201, 0.2),
      0 5px 15px rgba(244, 51, 151, 0.1),
      inset 0 1px 0 rgba(255, 255, 255, 0.9);
    border-color: rgba(255, 255, 255, 0.6);
  }

  .category-emoji-3d {
    font-size: 4rem;
    margin-bottom: 15px;
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    transform-style: preserve-3d;
    filter: drop-shadow(0 5px 15px rgba(0, 0, 0, 0.2));
  }

  .category-3d-card:hover .category-emoji-3d {
    transform: rotateY(20deg) rotateX(-10deg) scale(1.2);
    filter: drop-shadow(0 10px 30px rgba(255, 255, 255, 0.8));
  }

  .category-name-3d {
    font-size: 1.2rem;
    font-weight: 700;
    color: #2c3e50;
    text-align: center;
    letter-spacing: 1px;
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    position: relative;
    z-index: 2;
  }

  .category-3d-card:hover .category-name-3d {
    color: #ffffff;
    text-shadow: 
      0 2px 4px rgba(0, 0, 0, 0.3),
      0 0 10px rgba(255, 255, 255, 0.5);
    transform: translateZ(10px);
  }

  .category-glow {
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255, 153, 0, 0.1) 0%, transparent 70%);
    opacity: 0;
    transition: opacity 0.4s ease;
    pointer-events: none;
    animation: rotate-glow 8s linear infinite;
  }

  .category-3d-card:hover .category-glow {
    opacity: 1;
  }

  @keyframes rotate-glow {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
  }

  /* Responsive Design */
  @media (max-width: 768px) {
    .category-3d-card {
      height: 200px;
    }
    
    .category-emoji-3d {
      font-size: 3rem;
      margin-bottom: 10px;
    }
    
    .category-name-3d {
      font-size: 1rem;
    }
    
    .category-3d-content {
      padding: 15px;
    }
  }

  @media (max-width: 576px) {
    .category-3d-card {
      height: 150px;
    }
    
    .category-emoji-3d {
      font-size: 2.5rem;
      margin-bottom: 8px;
    }
    
    .category-name-3d {
      font-size: 0.9rem;
    }
    
    .category-3d-content {
      padding: 10px;
    }
  }
</style>