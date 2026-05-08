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
    description: "Freeze dry teknolojisiyle hazırlanan, %100 doğal tavuk ve taze sebzelerle zenginleştirilmiş premium köpek maması.",
    longDescription: "Rawlabs Tavuk & Sebzeli Köpek Maması, insan tüketimine uygun kalitede taze tavuk göğsü ve mevsim sebzeleriyle hazırlanır. Freeze dry teknolojisi sayesinde besin değerlerinin %97'si korunurken, doğal tat ve aroma aynen muhafaza edilir.\n\nHiçbir katkı maddesi, koruyucu veya yapay aroma içermez. Tahılsız formülü sayesinde hassas sindirim sistemine sahip köpekler için de uygundur. Yüksek protein oranı aktif köpeklerin kas gelişimini ve enerji ihtiyacını karşılar.",
    usageGuide: "Günde 2 öğün olarak verin. Kuru halde veya ılık su ekleyerek yumuşatarak servis edebilirsiniz. Yanında her zaman taze su bulundurun. Yeni mamaya geçişte 7-10 gün boyunca mevcut mama ile karıştırarak kademeli geçiş yapın."
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
    description: "Hassas mideli köpekler için özel formül. Kuzu eti ve pirinçle hazırlanan besleyici tam mama.",
    longDescription: "Rawlabs Kuzu & Pirinçli Köpek Maması, hassas sindirim sistemine sahip köpekler düşünülerek formüle edilmiştir. Kuzu eti yüksek biyolojik değerli protein kaynağı olarak kas gelişimini desteklerken, pirinç kolay sindirilebilir karbonhidrat sağlar.\n\nIspanak ve elma gibi doğal antioksidan kaynakları bağışıklık sistemini güçlendirir. Tüy parlaklığını ve deri sağlığını destekleyen besinlerle zenginleştirilmiştir.",
    usageGuide: "Günde 2 öğün olarak verin. Hassas mideli köpeklerde ilk kullanımda küçük porsiyonlarla başlayın. Ilık su ekleyerek yumuşatmak sindirim konforunu artırır. Taze su her zaman yanında bulunmalıdır."
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
    description: "Omega yağ asitleri açısından zengin somon ve tatlı patatesli, tüy ve eklem sağlığını destekleyen mama.",
    longDescription: "Rawlabs Somon & Tatlı Patatesli Köpek Maması, Omega 3 ve Omega 6 yağ asitleri açısından zengin premium bir formüldür. Taze somon tüy parlaklığını ve eklem sağlığını desteklerken, tatlı patates uzun süreli enerji sağlar.\n\nYaban mersini güçlü bir antioksidan kaynağıdır, keten tohumu ise sindirim sağlığını destekler. Tahılsız yapısıyla gıda hassasiyeti olan köpekler için idealdir.",
    usageGuide: "Günde 2 öğün olarak verin. Kuru veya ılık su ile servis edebilirsiniz. Eklem sorunları olan köpeklerde düzenli kullanımda 4-6 hafta içinde olumlu sonuçlar gözlemlenir. Taze su her zaman yanında bulunmalıdır."
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
    description: "Kedilerin doğal beslenme ihtiyacına uygun, tavuk ve ciğerle zenginleştirilmiş freeze dry mama.",
    longDescription: "Rawlabs Tavuk & Ciğerli Kedi Maması, kedilerin doğal avcı beslenme ihtiyacını karşılamak için yüksek protein içerikli formülüyle öne çıkar. Tavuk göğsü birincil protein kaynağı olarak kas yapısını desteklerken, tavuk ciğeri doğal taurin ve demir sağlar.\n\nKabak ve brokoli lif kaynağı olarak sindirim sağlığını düzenler. Freeze dry teknolojisi ile besin değerlerinin %97'si korunur, hiçbir katkı maddesi içermez.",
    usageGuide: "Günde 2 öğün olarak verin. Kuru halde veya ılık su ekleyerek servis edebilirsiniz. Yetişkin kediler için günlük porsiyon kiloya göre 25-40g arasındadır. Taze su her zaman yanında bulunmalıdır."
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
    description: "Balık seven kediler için özel, somon ve ton balığıyla hazırlanan premium freeze dry mama.",
    longDescription: "Rawlabs Somon & Ton Balıklı Kedi Maması, balık seven kediler için Omega 3 açısından zengin premium formüldür. Taze somon ve ton balığı tüy sağlığını ve parlaklığını desteklerken, doğal taurin içeriği kalp ve göz sağlığı için gerekli besini sağlar.\n\nKeten tohumu sindirim sağlığını, kabak ise bağırsak düzenini destekler. Tahılsız ve glütensiz yapısıyla hassas kediler için uygundur.",
    usageGuide: "Günde 2 öğün olarak verin. Balık aroması kedilerin iştahını artırır. Ilık su ekleyerek aromasını güçlendirebilirsiniz. Günlük porsiyon kiloya göre 25-35g arasındadır."
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
    description: "Tek içerikli, %100 saf tavuk göğsünden yapılan freeze dry kedi ödül maması.",
    longDescription: "Sadece %100 saf tavuk göğsünden üretilen bu ödül maması, kedilerin en sevdiği doğal lezzeti sunar. Tek içerikli yapısı sayesinde alerjen riski minimumdur ve hassas kediler için güvenle kullanılabilir.\n\nEğitim sırasında veya ödüllendirme amaçlı idealdir. Küçük parçalar halinde kolayca bölünebilir.",
    usageGuide: "Günlük ana öğünün yanında atıştırmalık olarak verin. Günde 5-10g'ı geçmemeye özen gösterin. Eğitim sırasında küçük parçalara bölerek kullanabilirsiniz."
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
    description: "Taze ton balığından freeze dry teknolojisiyle hazırlanan doğal kedi ödülü.",
    longDescription: "Taze ton balığının doğal lezzetini freeze dry teknolojisiyle saklayan bu ödül maması, Omega 3 yağ asitleri açısından zengindir. Kedilerin tüy sağlığını desteklerken, protein ihtiyacını da karşılar.\n\nTek içerikli yapısıyla katkısız ve güvenlidir. Balık seven kedilerin favorisi olmaya adaydır.",
    usageGuide: "Ara öğün veya ödül olarak günde 5-10g verin. Kuru halde doğrudan verebilir ya da ana öğünün üzerine serperek çekiciliği artırabilirsiniz."
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
    description: "Küp küp kesilmiş tavuk ciğerinden hazırlanan, kedilerin bayıldığı freeze dry ödül.",
    longDescription: "Taze tavuk ciğerinden küp küp kesilmiş ve freeze dry teknolojisiyle kurutulmuş bu ödül maması, doğal demir ve A vitamini kaynağıdır. Kedilerin ciğer aromasına olan doğal ilgisi sayesinde iştah açıcı etkiye sahiptir.\n\nTek içerikli ve katkısız yapısıyla güvenle her gün verilebilir.",
    usageGuide: "Günlük ödül olarak 5-8g verin. Küp formunda olduğu için kolayca porsiyonlanabilir. İştahsız kedilerde ana öğünün üzerine ekleyerek iştah açıcı olarak kullanılabilir."
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
    description: "Taze karidesten freeze dry teknolojisiyle üretilen özel kedi ödül maması.",
    longDescription: "Premium kalite taze karidesten üretilen bu özel ödül maması, düşük kalorili ve yüksek proteinli yapısıyla kilo kontrolü gerektiren kediler için idealdir. Omega 3 içeriği tüy ve deri sağlığını destekler.\n\nKarides aroması kedilerin dikkatini anında çeker, eğitim ve ödüllendirmede etkili sonuç verir.",
    usageGuide: "Özel lezzet olarak günde 3-5g verin. Düşük kalorili yapısı sayesinde diyet uygulayan kedilere de verilebilir. Kuru halde doğrudan sunun."
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
    description: "Alerji dostu, yağsız hindi göğsünden yapılan freeze dry kedi ödülü.",
    longDescription: "Hindi göğsü, tavuğa alternatif düşük yağlı ve hipoalerjenik bir protein kaynağıdır. Gıda alerjisi veya hassasiyeti olan kediler için güvenle tercih edilebilir.\n\nYüksek protein, düşük yağ oranıyla kilo yönetimi gereken kediler için de uygundur.",
    usageGuide: "Günlük ödül olarak 5-10g verin. Alerjik kedilerde eliminasyon diyeti sırasında güvenle kullanılabilir. Küçük parçalara bölerek eğitimde kullanın."
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
    description: "Farklı lezzet arayanlar için, saf ördek etinden freeze dry kedi ödül maması.",
    longDescription: "Ördek eti, kedilerin alışık olmadığı farklı bir protein kaynağı olarak hem lezzet çeşitliliği sağlar hem de gıda alerjisi riskini azaltır. Hipoalerjenik yapısıyla hassas kediler için uygundur.\n\nAlternatif protein arayanlar için mükemmel bir seçenektir.",
    usageGuide: "Lezzet değişikliği ve ödül olarak günde 5-10g verin. Farklı lezzetleri dönüşümlü olarak sunmak kedilerin iştahını canlı tutar."
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
    description: "Köpek eğitiminde mükemmel, %100 saf tavuk göğsünden freeze dry ödül maması.",
    longDescription: "%100 saf tavuk göğsünden üretilen bu ödül maması, köpek eğitiminde en etkili doğal motivasyon aracıdır. Tek içerikli yapısıyla katkısız ve güvenlidir.\n\nKolay kırılabilir yapısı sayesinde küçük ırk köpekler için de uygundur. Yüksek protein içeriği aktif köpekleri destekler.",
    usageGuide: "Eğitim ve ödül olarak günde 10-15g verin. Küçük parçalara bölerek eğitim seanslarında kullanın. Ana öğünün yerine geçmez, tamamlayıcı olarak verin."
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
    description: "Premium dana etinden freeze dry teknolojisiyle hazırlanan köpek ödül maması.",
    longDescription: "Premium kalite dana etinden üretilen bu ödül maması, yüksek protein ve doğal demir içeriğiyle köpeklerin kas ve kan sağlığını destekler. Yoğun et aroması köpeklerin en sevdiği lezzettir.\n\nFreeze dry teknolojisi sayesinde tazelik ve besin değeri korunur.",
    usageGuide: "Ödül ve atıştırmalık olarak günde 10-15g verin. Büyük ırk köpeklerde 20g'a kadar çıkabilirsiniz. Kuru halde doğrudan verin."
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
    description: "Kuzu ciğerinin doğal lezzetiyle köpeklerin favorisi olan freeze dry ödül.",
    longDescription: "Kuzu ciğerinin yoğun ve doğal lezzeti, köpeklerin en sevdiği tatlardan biridir. Doğal A vitamini ve demir kaynağı olarak bağışıklık sistemini ve kan sağlığını destekler.\n\nFreeze dry teknolojisiyle tüm besin değerleri korunarak sunulur.",
    usageGuide: "Günlük ödül olarak 10-15g verin. Ciğerin yoğun aroması iştahsız köpeklerin ilgisini çekmek için ana öğünün üzerine de eklenebilir."
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
    description: "Taze somondan freeze dry edilmiş, tüy ve eklem sağlığını destekleyen köpek ödülü.",
    longDescription: "Taze somondan freeze dry teknolojisiyle hazırlanan bu ödül maması, Omega 3 ve Omega 6 yağ asitleri açısından zengindir. Tüy parlaklığını, deri sağlığını ve eklem esnekliğini destekler.\n\nÖzellikle yaşlı köpekler ve eklem sorunları olan dostlar için önerilir.",
    usageGuide: "Ödül olarak günde 8-12g verin. Eklem sağlığı desteği için düzenli kullanımda 4-6 hafta içinde olumlu sonuçlar gözlemlenir. Kuru halde doğrudan sunun."
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
    description: "Alerji dostu hindi etinden üretilen, hassas köpekler için ideal freeze dry ödül.",
    longDescription: "Hindi eti, düşük yağlı ve hipoalerjenik yapısıyla gıda hassasiyeti olan köpekler için ideal bir alternatiftir. Kolay sindirilebilir yapısı hassas mideli köpeklerde rahat sindirim sağlar.\n\nEğitim ve ödüllendirmede güvenle kullanılabilir.",
    usageGuide: "Günlük ödül olarak 10-15g verin. Hassas mideli veya alerjik köpeklerde güvenle kullanılabilir. Eğitim sırasında küçük parçalara bölün."
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
    description: "Ördek etinin eşsiz lezzetiyle hazırlanan, doğal freeze dry köpek ödül maması.",
    longDescription: "Ördek eti, köpekler için farklı ve çekici bir protein kaynağıdır. Hipoalerjenik yapısıyla gıda alerjisi olan köpeklerde güvenle kullanılabilir. Lezzeti sayesinde seçici köpeklerin bile ilgisini çeker.\n\nAlternatif protein arayışındaki köpek sahipleri için mükemmel bir seçenektir.",
    usageGuide: "Lezzet çeşitliliği ve ödül olarak günde 10-15g verin. Farklı ödül maması lezzetlerini dönüşümlü sunarak köpeğinizin ilgisini c anlı tutun."
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
          <a href="urun.html?slug=${product.slug}" class="btn-incele">Ürünü İncele</a>
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

// Slug ile ürün bul
function getProductBySlug(slug) {
  return PRODUCTS.find(p => p.slug === slug) || null;
}
