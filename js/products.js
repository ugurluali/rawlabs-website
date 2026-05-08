/* ============================================
   RAWLABS - Ürün Verileri (17 Ürün)
   ============================================ */

const PRODUCTS = [
  // ───── KÖPEK TAM MAMA (3) ─────
  {
    id: 1,
    name: "Freeze Dry Tavuk & Sebzeli Köpek Maması",
    shortName: "Tavuk & Sebze Köpek",
    slug: "tavuk-sebze-kopek-tam-mama",
    petType: "kopek",
    category: "tam-mama",
    categoryLabel: "Köpek Tam Mama",
    price: 349,
    weight: "500g",
    rating: 4.9,
    reviewCount: 127,
    badge: "En Çok Satan",
    image: "img/product-dog-food.png",
    ingredients: ["Tavuk Göğsü", "Havuç", "Bezelye", "Tatlı Patates", "Brokoli"],
    benefits: ["Yüksek Protein", "Tahılsız", "Doğal Vitamin", "Kolay Sindirim"],
    description: "Freeze dry teknolojisiyle hazırlanan, %100 doğal tavuk ve taze sebzelerle zenginleştirilmiş premium köpek maması."
  },
  {
    id: 2,
    name: "Freeze Dry Kuzu & Pirinçli Köpek Maması",
    shortName: "Kuzu & Pirinç Köpek",
    slug: "kuzu-pirinc-kopek-tam-mama",
    petType: "kopek",
    category: "tam-mama",
    categoryLabel: "Köpek Tam Mama",
    price: 379,
    weight: "500g",
    rating: 4.8,
    reviewCount: 98,
    badge: "Yeni",
    image: "img/product-dog-food.png",
    ingredients: ["Kuzu Eti", "Pirinç", "Kabak", "Ispanak", "Elma"],
    benefits: ["Hassas Mide", "Yüksek Protein", "Parlak Tüy", "Enerji"],
    description: "Hassas mideli köpekler için özel formül. Kuzu eti ve pirinçle hazırlanan besleyici tam mama."
  },
  {
    id: 3,
    name: "Freeze Dry Somon & Tatlı Patatesli Köpek Maması",
    shortName: "Somon Köpek",
    slug: "somon-tatli-patates-kopek-tam-mama",
    petType: "kopek",
    category: "tam-mama",
    categoryLabel: "Köpek Tam Mama",
    price: 399,
    weight: "500g",
    rating: 4.7,
    reviewCount: 85,
    badge: null,
    image: "img/product-dog-food.png",
    ingredients: ["Somon", "Tatlı Patates", "Yaban Mersini", "Keten Tohumu"],
    benefits: ["Omega 3-6", "Parlak Tüy", "Eklem Sağlığı", "Antioksidan"],
    description: "Omega yağ asitleri açısından zengin somon ve tatlı patatesli, tüy ve eklem sağlığını destekleyen mama."
  },

  // ───── KEDİ TAM MAMA (2) ─────
  {
    id: 4,
    name: "Freeze Dry Tavuk & Ciğerli Kedi Maması",
    shortName: "Tavuk & Ciğer Kedi",
    slug: "tavuk-ciger-kedi-tam-mama",
    petType: "kedi",
    category: "tam-mama",
    categoryLabel: "Kedi Tam Mama",
    price: 299,
    weight: "300g",
    rating: 4.9,
    reviewCount: 156,
    badge: "En Çok Satan",
    image: "img/product-cat-food.png",
    ingredients: ["Tavuk Göğsü", "Tavuk Ciğeri", "Kabak", "Brokoli"],
    benefits: ["Yüksek Protein", "Taurin", "Parlak Tüy", "İdrar Sağlığı"],
    description: "Kedilerin doğal beslenme ihtiyacına uygun, tavuk ve ciğerle zenginleştirilmiş freeze dry mama."
  },
  {
    id: 5,
    name: "Freeze Dry Somon & Ton Balıklı Kedi Maması",
    shortName: "Somon & Ton Kedi",
    slug: "somon-ton-kedi-tam-mama",
    petType: "kedi",
    category: "tam-mama",
    categoryLabel: "Kedi Tam Mama",
    price: 329,
    weight: "300g",
    rating: 4.8,
    reviewCount: 112,
    badge: null,
    image: "img/product-cat-food.png",
    ingredients: ["Somon", "Ton Balığı", "Kabak", "Keten Tohumu"],
    benefits: ["Omega 3", "Taurin", "Tüy Sağlığı", "Bağışıklık"],
    description: "Balık seven kediler için özel, somon ve ton balığıyla hazırlanan premium freeze dry mama."
  },

  // ───── KEDİ ÖDÜL MAMASI (6) ─────
  {
    id: 6,
    name: "Freeze Dry Tavuk Göğsü - Kedi Ödül Maması",
    shortName: "Tavuk Göğsü Kedi",
    slug: "tavuk-gogsu-kedi-odul",
    petType: "kedi",
    category: "odul",
    categoryLabel: "Kedi Ödül Maması",
    price: 89,
    weight: "50g",
    rating: 4.9,
    reviewCount: 203,
    badge: "En Çok Satan",
    image: "img/product-cat-food.png",
    ingredients: ["%100 Tavuk Göğsü"],
    benefits: ["Tek İçerik", "Katkısız", "Eğitim İçin İdeal"],
    description: "Tek içerikli, %100 saf tavuk göğsünden yapılan freeze dry kedi ödül maması."
  },
  {
    id: 7,
    name: "Freeze Dry Ton Balığı - Kedi Ödül Maması",
    shortName: "Ton Balığı Kedi",
    slug: "ton-baligi-kedi-odul",
    petType: "kedi",
    category: "odul",
    categoryLabel: "Kedi Ödül Maması",
    price: 99,
    weight: "50g",
    rating: 4.8,
    reviewCount: 178,
    badge: null,
    image: "img/product-cat-food.png",
    ingredients: ["%100 Ton Balığı"],
    benefits: ["Omega 3", "Tek İçerik", "Katkısız"],
    description: "Taze ton balığından freeze dry teknolojisiyle hazırlanan doğal kedi ödülü."
  },
  {
    id: 8,
    name: "Freeze Dry Ciğer Küpleri - Kedi Ödül Maması",
    shortName: "Ciğer Küpleri Kedi",
    slug: "ciger-kupleri-kedi-odul",
    petType: "kedi",
    category: "odul",
    categoryLabel: "Kedi Ödül Maması",
    price: 79,
    weight: "50g",
    rating: 4.7,
    reviewCount: 145,
    badge: "Uygun Fiyat",
    image: "img/product-cat-food.png",
    ingredients: ["%100 Tavuk Ciğeri"],
    benefits: ["Demir Kaynağı", "Vitamin A", "Katkısız"],
    description: "Küp küp kesilmiş tavuk ciğerinden hazırlanan, kedilerin bayıldığı freeze dry ödül."
  },
  {
    id: 9,
    name: "Freeze Dry Karides - Kedi Ödül Maması",
    shortName: "Karides Kedi",
    slug: "karides-kedi-odul",
    petType: "kedi",
    category: "odul",
    categoryLabel: "Kedi Ödül Maması",
    price: 119,
    weight: "40g",
    rating: 4.9,
    reviewCount: 92,
    badge: "Premium",
    image: "img/product-cat-food.png",
    ingredients: ["%100 Karides"],
    benefits: ["Düşük Kalori", "Yüksek Protein", "Omega 3"],
    description: "Taze karidesten freeze dry teknolojisiyle üretilen özel kedi ödül maması."
  },
  {
    id: 10,
    name: "Freeze Dry Hindi Göğsü - Kedi Ödül Maması",
    shortName: "Hindi Kedi",
    slug: "hindi-gogsu-kedi-odul",
    petType: "kedi",
    category: "odul",
    categoryLabel: "Kedi Ödül Maması",
    price: 89,
    weight: "50g",
    rating: 4.6,
    reviewCount: 67,
    badge: null,
    image: "img/product-cat-food.png",
    ingredients: ["%100 Hindi Göğsü"],
    benefits: ["Düşük Yağ", "Yüksek Protein", "Hipoalerjenik"],
    description: "Alerji dostu, yağsız hindi göğsünden yapılan freeze dry kedi ödülü."
  },
  {
    id: 11,
    name: "Freeze Dry Ördek - Kedi Ödül Maması",
    shortName: "Ördek Kedi",
    slug: "ordek-kedi-odul",
    petType: "kedi",
    category: "odul",
    categoryLabel: "Kedi Ödül Maması",
    price: 99,
    weight: "50g",
    rating: 4.7,
    reviewCount: 54,
    badge: "Yeni",
    image: "img/product-cat-food.png",
    ingredients: ["%100 Ördek Eti"],
    benefits: ["Alternatif Protein", "Hipoalerjenik", "Lezzetli"],
    description: "Farklı lezzet arayanlar için, saf ördek etinden freeze dry kedi ödül maması."
  },

  // ───── KÖPEK ÖDÜL MAMASI (6) ─────
  {
    id: 12,
    name: "Freeze Dry Tavuk Göğsü - Köpek Ödül Maması",
    shortName: "Tavuk Göğsü Köpek",
    slug: "tavuk-gogsu-kopek-odul",
    petType: "kopek",
    category: "odul",
    categoryLabel: "Köpek Ödül Maması",
    price: 99,
    weight: "70g",
    rating: 4.8,
    reviewCount: 189,
    badge: "En Çok Satan",
    image: "img/product-dog-food.png",
    ingredients: ["%100 Tavuk Göğsü"],
    benefits: ["Eğitim İçin İdeal", "Tek İçerik", "Katkısız"],
    description: "Köpek eğitiminde mükemmel, %100 saf tavuk göğsünden freeze dry ödül maması."
  },
  {
    id: 13,
    name: "Freeze Dry Dana Eti - Köpek Ödül Maması",
    shortName: "Dana Eti Köpek",
    slug: "dana-eti-kopek-odul",
    petType: "kopek",
    category: "odul",
    categoryLabel: "Köpek Ödül Maması",
    price: 119,
    weight: "70g",
    rating: 4.9,
    reviewCount: 143,
    badge: "Premium",
    image: "img/product-dog-food.png",
    ingredients: ["%100 Dana Eti"],
    benefits: ["Yüksek Protein", "Demir Kaynağı", "Lezzetli"],
    description: "Premium dana etinden freeze dry teknolojisiyle hazırlanan köpek ödül maması."
  },
  {
    id: 14,
    name: "Freeze Dry Kuzu Ciğeri - Köpek Ödül Maması",
    shortName: "Kuzu Ciğeri Köpek",
    slug: "kuzu-cigeri-kopek-odul",
    petType: "kopek",
    category: "odul",
    categoryLabel: "Köpek Ödül Maması",
    price: 109,
    weight: "70g",
    rating: 4.7,
    reviewCount: 98,
    badge: null,
    image: "img/product-dog-food.png",
    ingredients: ["%100 Kuzu Ciğeri"],
    benefits: ["Vitamin A", "Demir", "Doğal Lezzet"],
    description: "Kuzu ciğerinin doğal lezzetiyle köpeklerin favorisi olan freeze dry ödül."
  },
  {
    id: 15,
    name: "Freeze Dry Somon - Köpek Ödül Maması",
    shortName: "Somon Köpek",
    slug: "somon-kopek-odul",
    petType: "kopek",
    category: "odul",
    categoryLabel: "Köpek Ödül Maması",
    price: 129,
    weight: "60g",
    rating: 4.8,
    reviewCount: 76,
    badge: null,
    image: "img/product-dog-food.png",
    ingredients: ["%100 Somon"],
    benefits: ["Omega 3-6", "Parlak Tüy", "Eklem Sağlığı"],
    description: "Taze somondan freeze dry edilmiş, tüy ve eklem sağlığını destekleyen köpek ödülü."
  },
  {
    id: 16,
    name: "Freeze Dry Hindi - Köpek Ödül Maması",
    shortName: "Hindi Köpek",
    slug: "hindi-kopek-odul",
    petType: "kopek",
    category: "odul",
    categoryLabel: "Köpek Ödül Maması",
    price: 99,
    weight: "70g",
    rating: 4.6,
    reviewCount: 58,
    badge: "Yeni",
    image: "img/product-dog-food.png",
    ingredients: ["%100 Hindi Eti"],
    benefits: ["Düşük Yağ", "Hipoalerjenik", "Kolay Sindirim"],
    description: "Alerji dostu hindi etinden üretilen, hassas köpekler için ideal freeze dry ödül."
  },
  {
    id: 17,
    name: "Freeze Dry Ördek - Köpek Ödül Maması",
    shortName: "Ördek Köpek",
    slug: "ordek-kopek-odul",
    petType: "kopek",
    category: "odul",
    categoryLabel: "Köpek Ödül Maması",
    price: 109,
    weight: "70g",
    rating: 4.7,
    reviewCount: 45,
    badge: null,
    image: "img/product-dog-food.png",
    ingredients: ["%100 Ördek Eti"],
    benefits: ["Alternatif Protein", "Yeni Lezzet", "Doğal"],
    description: "Ördek etinin eşsiz lezzetiyle hazırlanan, doğal freeze dry köpek ödül maması."
  }
];

// En çok satanları getir
function getBestSellers(count = 6) {
  return PRODUCTS.filter(p => p.badge === "En Çok Satan" || p.reviewCount > 100)
    .sort((a, b) => b.reviewCount - a.reviewCount)
    .slice(0, count);
}

// Kategoriye göre filtrele
function getProductsByCategory(category) {
  return PRODUCTS.filter(p => p.category === category);
}

// Hayvan türüne göre filtrele
function getProductsByPetType(petType) {
  return PRODUCTS.filter(p => p.petType === petType);
}

// Ürün kartı HTML render
function renderProductCard(product) {
  const starsHtml = '★'.repeat(Math.floor(product.rating)) + (product.rating % 1 >= 0.5 ? '½' : '');
  const badgeHtml = product.badge ? `<span class="product-badge">${product.badge}</span>` : '';
  
  return `
    <article class="product-card animate-on-scroll" data-id="${product.id}">
      ${badgeHtml}
      <div class="product-image">
        <img src="${product.image}" alt="${product.name}" loading="lazy">
      </div>
      <div class="product-info">
        <span class="product-category">${product.categoryLabel}</span>
        <h3 class="product-name">${product.name}</h3>
        <div class="product-rating">
          <span class="stars">${starsHtml}</span>
          <span class="rating-count">(${product.reviewCount})</span>
        </div>
        <span class="product-weight">${product.weight}</span>
        <div class="product-bottom">
          <span class="product-price">₺${product.price} <span>/ ${product.weight}</span></span>
          <a href="magaza.html?urun=${product.slug}" class="btn-incele">Ürünü İncele</a>
        </div>
      </div>
    </article>
  `;
}

// Ürün listesini render et
function renderProducts(products, containerId) {
  const container = document.getElementById(containerId);
  if (!container) return;
  container.innerHTML = products.map(p => renderProductCard(p)).join('');
}
