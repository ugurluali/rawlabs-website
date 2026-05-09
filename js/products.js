/* ============================================
   RAWLABS - Gerçek Ürün Verileri (17 Ürün)
   5 Tam Mama + 12 Ödül Maması
   ============================================ */

const PRODUCTS = [
  // ───── TAM MAMA (5) ─────
  {
    id: 1,
    slug: "tavuk-balik-kedi-mamasi-300g",
    name: "Tavuk & Balık Kedi Maması 300g",
    shortName: "Tavuk & Balık Kedi",
    category: "tam-mama",
    categoryLabel: "Kedi Tam Mama",
    petType: "kedi",
    weight: "300g",
    price: 1070,
    salePrice: 963,
    image: "img/products/tavuk-balik-kedi-mamasi-300g.webp",
    originalImageUrl: "https://rawlabs.com.tr/wp-content/uploads/2025/04/Kedi-Tavuk-Balik-kopya.png",
    sourceUrl: "https://rawlabs.com.tr/product/freeze-dried-kedi-mamasi-300g-tavuk-balik-dogal-katkisiz/",
    shortDescription: "Tavuk eti ve hamsi ile zenginleştirilmiş, katkı maddesi içermeyen freeze dried kedi maması. Sindirimi kolay, çiğ beslenmeye geçişlerde önerilir.",
    longDescription: "Freeze dried kedi maması arayanlar için özel olarak geliştirilen bu ürün, tavuk eti ve omega-3 kaynağı hamsi ile hazırlanmış yüksek proteinli ve tamamen doğal bir beslenme sunar. Çiğ beslenme prensibine uygun olarak üretilen bu mama, besin değerleri korunarak dondurularak kurutulmuştur.",
    ingredients: ["Tavuk Göğüs Eti", "Tavuk Yürek", "Tavuk Ciğer", "Hamsi", "Yumurta", "Zeytinyağı", "Yumurta Kabuğu Tozu", "Kabak Çekirdeği", "Keten Tohumu", "Kabak", "Kefir", "Zerdeçal", "Karabiber"],
    benefits: ["Yüksek protein ile kas gelişimini destekler", "Omega-3 sayesinde deri ve tüy sağlığını destekler", "Sindirimi kolaydır, hassas kediler için uygundur", "Doğal içeriklerle bağışıklık sistemine katkı sağlar", "Yoğun aroması sayesinde iştahı artırır"],
    nutrition: { metabolicEnergy: "4769 kcal", protein: 43.6, fat: 38.0, moisture: 2.9, ash: 6.5, fiber: 4.21 },
    usageGuide: "Günlük tüketim miktarı kedinizin kilosuna göre ayarlanmalıdır. İsteğe bağlı olarak su ile nemlendirilerek verilebilir.",
    feedingGuide: "Yetişkin kediler için günlük porsiyon kiloya göre ayarlanmalıdır. 1000 gr çiğ içerikten yaklaşık 300 gr ürün elde edilir. Her parça ortalama 2 gram olup kolay porsiyonlama sağlar.",
    storage: "Serin ve kuru ortamda, ağzı kapalı şekilde muhafaza ediniz. Açıldıktan sonra 1 ay içinde tüketilmesi önerilir. Ambalaj içerisinde oksijen tutucu bulunabilir.",
    warning: null,
    badge: "%10 İndirim",
    rating: 0,
    reviewCount: 0
  },
  {
    id: 2,
    slug: "hindi-balik-kedi-mamasi-300g",
    name: "Hindi & Balık Kedi Maması 300g",
    shortName: "Hindi & Balık Kedi",
    category: "tam-mama",
    categoryLabel: "Kedi Tam Mama",
    petType: "kedi",
    weight: "300g",
    price: 1386,
    salePrice: 1247.40,
    image: "img/products/hindi-balik-kedi-mamasi-300g.webp",
    originalImageUrl: "https://rawlabs.com.tr/wp-content/uploads/2025/04/kedi-hindi-balik-1.png",
    sourceUrl: "https://rawlabs.com.tr/product/freeze-dried-kedi-mamasi-hindi-balik-300g/",
    shortDescription: "Hindi & balık içeriğiyle hazırlanan bu freeze dried kedi maması, tavuk ve dana alerjisi olan kediler için ideal bir beslenme sunar.",
    longDescription: "Freeze dried kedi maması arayan ve özellikle hassas bünyeye sahip kediler için alternatif protein kaynağı arayanlar için geliştirilen bu ürün, hindi eti ve omega-3 kaynağı hamsi ile hazırlanmış yüksek proteinli ve tamamen doğal bir beslenme sunar. Tavuk ve dana eti alerjisi olan kediler için ideal bir seçenektir.",
    ingredients: ["Hindi Göğüs Eti", "Hindi Yürek", "Hindi Ciğer", "Hamsi", "Kabak", "Yumurta", "Zeytinyağı", "Yumurta Kabuğu Tozu", "Kabak Çekirdeği", "Pancar Turşusu", "Lor", "Yoğurt"],
    benefits: ["Hindi eti ile alternatif protein kaynağı", "Tavuk ve dana alerjisi olan kediler için uygun", "Yüksek protein oranı (%60,5)", "Omega-3 desteği (hamsi)", "Kolay sindirilebilir"],
    nutrition: { metabolicEnergy: "4613 kcal", protein: 60.5, fat: 26.44, moisture: 4.2, ash: 6.68, fiber: null },
    usageGuide: "Günlük tüketim miktarı kedinizin kilosuna göre ayarlanmalıdır. İsteğe bağlı olarak su ile nemlendirilerek verilebilir.",
    feedingGuide: "1000 gr çiğ ürün → 300 gr'a düşer. Her parça ortalama 2 gram olup kolay porsiyonlama sunar.",
    storage: "Serin ve kuru ortamda saklayınız. Açıldıktan sonra 1 ay içinde tüketilmesi önerilir. Ambalaj içerisinde oksijen tutucu bulunabilir.",
    warning: null,
    badge: "%10 İndirim",
    rating: 0,
    reviewCount: 0
  },
  {
    id: 3,
    slug: "tavuk-balik-kopek-mamasi-300g",
    name: "Tavuk & Balık Köpek Maması 300g",
    shortName: "Tavuk & Balık Köpek",
    category: "tam-mama",
    categoryLabel: "Köpek Tam Mama",
    petType: "kopek",
    weight: "300g",
    price: 1070,
    salePrice: 963,
    image: "img/products/tavuk-balik-kopek-mamasi-300g.webp",
    originalImageUrl: "https://rawlabs.com.tr/wp-content/uploads/2025/04/Kopek-Tavuk-Balik-kopya.png",
    sourceUrl: "https://rawlabs.com.tr/product/freeze-dried-tavuk-balik-kopek-mamasi-300g/",
    shortDescription: "%100 doğal içerik, yüksek protein, katkısız formül ile köpekler için sağlıklı ve lezzetli tam mama.",
    longDescription: "Freeze dried köpek maması arayanlar için özel olarak geliştirilen bu ürün, tavuk ve balık içeriğiyle yüksek proteinli ve tamamen doğal bir beslenme sunar. Tavuk eti ve hamsi ile zenginleştirilmiş bu mama, köpeğinizin doğal beslenme ihtiyaçlarına uygun olarak geliştirilmiştir.",
    ingredients: ["Tavuk Göğüs Eti", "Tavuk Yürek", "Tavuk Ciğer", "Hamsi", "Yumurta", "Zeytinyağı", "Yumurta Kabuğu Tozu", "Kabak Çekirdeği", "Keten Tohumu", "Kabak", "Kırmızı Lahana", "Elma", "Kefir", "Zerdeçal", "Karabiber"],
    benefits: ["%100 doğal içerik", "Yüksek protein oranı (%41,9)", "Omega-3 kaynağı (hamsi)", "Kolay sindirilebilir", "Enerji ve kas gelişimini destekler"],
    nutrition: { metabolicEnergy: "4932 kcal", protein: 41.9, fat: 39.0, moisture: 6.4, ash: 6.1, fiber: 2.55 },
    usageGuide: "Günlük tüketim miktarı köpeğinizin kilosuna göre ayarlanmalıdır. İsteğe bağlı olarak su ile nemlendirilerek verilebilir.",
    feedingGuide: "1000 gr çiğ içerikten yaklaşık 300 gr besin değeri yoğun ürün elde edilir. Her parça ortalama 2 gr olup kolay porsiyonlanabilir yapıdadır.",
    storage: "Serin ve kuru ortamda, ağzı kapalı şekilde muhafaza ediniz. Açıldıktan sonra 1 ay içinde tüketilmesi önerilir. Ambalaj içerisinde oksijen tutucu bulunabilir.",
    warning: null,
    badge: "%10 İndirim",
    rating: 0,
    reviewCount: 0
  },
  {
    id: 4,
    slug: "hindi-balik-kopek-mamasi-300g",
    name: "Hindi & Balık Köpek Maması 300g",
    shortName: "Hindi & Balık Köpek",
    category: "tam-mama",
    categoryLabel: "Köpek Tam Mama",
    petType: "kopek",
    weight: "300g",
    price: 1386,
    salePrice: 1247.40,
    image: "img/products/hindi-balik-kopek-mamasi-300g.webp",
    originalImageUrl: "https://rawlabs.com.tr/wp-content/uploads/2025/04/Kopek-Hindi-Balik-kopya.png",
    sourceUrl: "https://rawlabs.com.tr/product/freeze-dried-hindi-balik-kopek-mamasi-300g/",
    shortDescription: "Tavuk ve dana alerjisi olan köpekler için özel olarak geliştirilen hindi & balık içerikli freeze dried köpek maması.",
    longDescription: "Freeze dried köpek maması arayan ve özellikle hassas bünyeye sahip köpekler için alternatif protein kaynağı arayanlar için geliştirilen bu ürün, hindi eti ve omega-3 kaynağı hamsi ile hazırlanmış yüksek proteinli ve tamamen doğal bir beslenme sunar.",
    ingredients: ["Hindi Göğüs Eti", "Hindi Yürek", "Hindi Ciğer", "Hamsi", "Elma", "Kabak", "Yumurta", "Zeytinyağı", "Yumurta Kabuğu Tozu", "Kabak Çekirdeği", "Pancar Turşusu", "Lor", "Yoğurt"],
    benefits: ["Hindi eti ile alternatif protein kaynağı", "Tavuk ve dana alerjisi olan köpekler için uygun", "Yüksek protein oranı (%68,3)", "Omega-3 desteği (hamsi)", "Sindirimi kolaydır"],
    nutrition: { metabolicEnergy: "4158 kcal", protein: 68.3, fat: 14.1, moisture: 4.9, ash: 7.5, fiber: 0.5 },
    usageGuide: "Günlük tüketim miktarı köpeğinizin kilosuna göre ayarlanmalıdır. İsteğe bağlı olarak su ile nemlendirilerek verilebilir.",
    feedingGuide: "1000 gr çiğ ürün → 300 gr'a düşer. Her parça ortalama 2 gram olup kolay porsiyonlama sunar.",
    storage: "Serin ve kuru ortamda saklayınız. Açıldıktan sonra 1 ay içinde tüketilmesi önerilir. Ambalaj içerisinde oksijen tutucu bulunabilir.",
    warning: null,
    badge: "%10 İndirim",
    rating: 0,
    reviewCount: 0
  },
  {
    id: 5,
    slug: "dana-balik-kopek-mamasi-300g",
    name: "Dana & Balık Köpek Maması 300g",
    shortName: "Dana & Balık Köpek",
    category: "tam-mama",
    categoryLabel: "Köpek Tam Mama",
    petType: "kopek",
    weight: "300g",
    price: 1377,
    salePrice: 1239.30,
    image: "img/products/dana-balik-kopek-mamasi-300g.webp",
    originalImageUrl: "https://rawlabs.com.tr/wp-content/uploads/2025/04/Kopek-Dana-Balik-kopya.png",
    sourceUrl: "https://rawlabs.com.tr/product/freeze-dried-kopek-mamasi-dana-balik-300g/",
    shortDescription: "Freeze dried köpek maması arayanlar için doğal dana & balık içerikli, yüksek proteinli ve katkısız köpek maması.",
    longDescription: "Dana eti temel mineraller olan magnezyum ve potasyum yanı sıra temel amino asitlerin doğal bir kaynağıdır. Kas gelişimini, sinir sistemi ve kan sağlığını destekler. Tüm ürün içeriklerinde Omega 3 kaynağı Hamsi yer almaktadır.",
    ingredients: ["Dana Eti", "Dana Yürek", "Tavuk Ciğeri", "Hamsi", "Yumurta", "Zeytinyağı", "Yumurta Kabuğu Tozu", "Kabak Çekirdeği", "Keten Tohumu", "Kabak", "Kırmızı Lahana", "Elma", "Kefir", "Zerdeçal", "Karabiber"],
    benefits: ["Dana eti ile yüksek biyolojik değerli protein", "Magnezyum ve potasyum kaynağı", "Kas gelişimini ve sinir sistemini destekler", "Omega-3 desteği (hamsi)", "Katkı maddesi içermez"],
    nutrition: { metabolicEnergy: "4850 kcal", protein: 42.5, fat: 38.7, moisture: 5.3, ash: 5.6, fiber: 3.44 },
    usageGuide: "Günlük tüketim miktarı köpeğinizin kilosuna göre ayarlanmalıdır. Kuru halde veya ılık su ekleyerek yumuşatarak servis edebilirsiniz.",
    feedingGuide: "1000 gr çiğ içerikten yaklaşık 300 gr besin değeri yoğun ürün elde edilir. Her parça ortalama 2 gr olup kolay porsiyonlanabilir yapıdadır.",
    storage: "Serin ve kuru ortamda, ağzı kapalı şekilde muhafaza ediniz. Açıldıktan sonra 1 ay içinde tüketilmesi önerilir. Ambalaj içerisinde oksijen tutucu bulunabilir.",
    warning: null,
    badge: "%10 İndirim",
    rating: 0,
    reviewCount: 0
  },

  // ───── ÖDÜL MAMASI (12) ─────
  {
    id: 6,
    slug: "dana-dalak-odulu-40g",
    name: "Dana Dalak Ödülü 40g",
    shortName: "Dana Dalak",
    category: "odul",
    categoryLabel: "Kedi & Köpek Ödül",
    petType: "kedi-kopek",
    weight: "40g",
    price: 350,
    salePrice: 315,
    image: "img/products/dana-dalak-odulu-40g.webp",
    originalImageUrl: "https://rawlabs.com.tr/wp-content/uploads/2025/04/1.png",
    sourceUrl: "https://rawlabs.com.tr/product/freeze-dried-dana-dalak-kedi-kopek-odul-mamasi/",
    shortDescription: "%100 dana dalaktan üretilen freeze dried ödül maması. Demir kaynağı, tryptophan desteği sağlar.",
    longDescription: "Freeze dried ödül maması arayanlar için geliştirilen bu ürün, %100 dana dalaktan üretilmiştir. Yüksek demir içeriği ile enerji seviyesini desteklerken, tryptophan sayesinde stres ve denge üzerinde olumlu etkiler sağlar.",
    ingredients: ["%100 Dana Dalak"],
    benefits: ["Yüksek demir içeriği ile enerji seviyesini destekler", "Tryptophan sayesinde stres ve dengeyi destekler", "Tek içerikli, katkısız formül", "Kedi ve köpekler için uygun"],
    nutrition: { metabolicEnergy: "3778 kcal", protein: 79.1, fat: 8.0, moisture: 4.5, ash: 6.4, fiber: 4.5 },
    usageGuide: "Doğrudan kuru olarak verilebilir. İsteğe bağlı su ile yumuşatılabilir.",
    feedingGuide: "Ana öğünün yanında tamamlayıcı olarak verin. Günlük miktarı hayvanınızın kilosuna göre ayarlayın.",
    storage: "Serin ve kuru ortamda saklayınız. Açıldıktan sonra 1 ay içinde tüketilmesi önerilir.",
    warning: null,
    badge: "%10 İndirim",
    rating: 0,
    reviewCount: 0
  },

  {
    id: 7,
    slug: "dana-yurek-odulu-40g",
    name: "Dana Yürek Ödülü 40g",
    shortName: "Dana Yürek",
    category: "odul",
    categoryLabel: "Kedi & Köpek Ödül",
    petType: "kedi-kopek",
    weight: "40g",
    price: 409,
    salePrice: 368.10,
    image: "img/products/dana-yurek-odulu-40g.webp",
    originalImageUrl: "https://rawlabs.com.tr/wp-content/uploads/2025/04/5.png",
    sourceUrl: "https://rawlabs.com.tr/product/freeze-dried-dana-yurek-odulu-kedi-kopek-odulu/",
    shortDescription: "%100 dana yürekten üretilen freeze dried ödül maması. Doğal taurin ve B1 vitamini kaynağı.",
    longDescription: "Dana yürek, doğal taurin ve B1 vitamini kaynağıdır. Kalp sağlığını destekler ve yüksek protein içeriğiyle enerji sağlar.",
    ingredients: ["%100 Dana Yürek"],
    benefits: ["Doğal taurin kaynağı", "B1 vitamini içerir", "Kalp sağlığını destekler", "Yüksek protein"],
    nutrition: { metabolicEnergy: "4042 kcal/kg", protein: 76.4, fat: 10.5, moisture: 3.3, ash: 5.1, fiber: 0.0 },
    usageGuide: "Doğrudan kuru olarak verilebilir. İsteğe bağlı su ile yumuşatılabilir.",
    feedingGuide: "Ana öğünün yanında tamamlayıcı olarak verin.",
    storage: "Serin ve kuru ortamda saklayınız. Açıldıktan sonra 1 ay içinde tüketilmesi önerilir.",
    warning: null, badge: "%10 İndirim", rating: 0, reviewCount: 0
  },
  {
    id: 8,
    slug: "dana-ciger-odulu-40g",
    name: "Dana Ciğer Ödülü 40g",
    shortName: "Dana Ciğer",
    category: "odul",
    categoryLabel: "Kedi & Köpek Ödül",
    petType: "kedi-kopek",
    weight: "40g",
    price: 425,
    salePrice: 382.50,
    image: "img/products/dana-ciger-odulu-40g.webp",
    originalImageUrl: "https://rawlabs.com.tr/wp-content/uploads/2025/04/4.png",
    sourceUrl: "https://rawlabs.com.tr/product/freeze-dried-dana-ciger-kedi-kopek-odul-mamasi/",
    shortDescription: "%100 dana ciğerden üretilen freeze dried ödül maması. A ve B vitaminleri kaynağı, iştah açıcı.",
    longDescription: "Dana ciğer, A ve B vitaminleri açısından zengindir. İştah açıcı etkisiyle beslenme güçlüğü çeken hayvanlarda faydalıdır.",
    ingredients: ["%100 Dana Ciğer"],
    benefits: ["A ve B vitaminleri kaynağı", "İştah açıcı etki", "Yüksek besin değeri", "Tek içerikli formül"],
    nutrition: { metabolicEnergy: "3360 kcal", protein: 62.7, fat: 5.5, moisture: 6.3, ash: 4.5, fiber: 0.06 },
    usageGuide: "Doğrudan kuru olarak verilebilir.",
    feedingGuide: "Ana öğünün yanında tamamlayıcı olarak verin.",
    storage: "Serin ve kuru ortamda saklayınız. Açıldıktan sonra 1 ay içinde tüketilmesi önerilir.",
    warning: "Yüksek A vitamini içeriği nedeniyle kontrollü tüketim önerilir.",
    badge: "%10 İndirim", rating: 0, reviewCount: 0
  },
  {
    id: 9,
    slug: "dana-girtlak-odulu-40g",
    name: "Dana Gırtlak Ödülü 40g",
    shortName: "Dana Gırtlak",
    category: "odul",
    categoryLabel: "Köpek Ödül",
    petType: "kopek",
    weight: "40g",
    price: 320,
    salePrice: 288,
    image: "img/products/dana-girtlak-odulu-40g.webp",
    originalImageUrl: "https://rawlabs.com.tr/wp-content/uploads/2025/04/2.png",
    sourceUrl: "https://rawlabs.com.tr/product/freeze-dried-dana-girtlak-kopek-mamasi-eklem-dis-sagligi/",
    shortDescription: "%100 dana gırtlaktan üretilen freeze dried köpek ödülü. Glukozamin & kondroitin ile eklem ve diş sağlığını destekler.",
    longDescription: "Dana gırtlak, doğal glukozamin ve kondroitin kaynağıdır. Eklem sağlığını desteklerken kemirme aktivitesiyle diş sağlığına da katkıda bulunur.",
    ingredients: ["%100 Dana Gırtlak"],
    benefits: ["Doğal glukozamin & kondroitin", "Eklem sağlığını destekler", "Diş sağlığına katkı", "Kemirme ödülü"],
    nutrition: { metabolicEnergy: "4297 kcal", protein: 63.5, fat: 15.8, moisture: 12.8, ash: 0.8, fiber: 0.6 },
    usageGuide: "Kemirme ödülü olarak verin.",
    feedingGuide: "Günlük miktarı köpeğinizin kilosuna göre ayarlayın.",
    storage: "Serin ve kuru ortamda saklayınız. Açıldıktan sonra 1 ay içinde tüketilmesi önerilir.",
    warning: null, badge: "%10 İndirim", rating: 0, reviewCount: 0
  },
  {
    id: 10,
    slug: "dana-billur-odulu-40g",
    name: "Dana Billur Ödülü 40g",
    shortName: "Dana Billur",
    category: "odul",
    categoryLabel: "Kedi & Köpek Ödül",
    petType: "kedi-kopek",
    weight: "40g",
    price: 330,
    salePrice: 297,
    image: "img/products/dana-billur-odulu-40g.webp",
    originalImageUrl: "https://rawlabs.com.tr/wp-content/uploads/2025/04/3.png",
    sourceUrl: "https://rawlabs.com.tr/product/freeze-dried-dana-billur-odulu-kedi-kopek-odulu/",
    shortDescription: "%100 dana billurdan üretilen freeze dried ödül maması. Demir kaynağı, enerji desteği sağlar.",
    longDescription: "Dana billur, yüksek demir içeriğiyle enerji seviyesini destekler. Tek içerikli ve katkısız formülüyle güvenle verilebilir.",
    ingredients: ["%100 Dana Billur"],
    benefits: ["Demir kaynağı", "Enerji desteği", "Tek içerikli formül", "Kedi ve köpekler için uygun"],
    nutrition: { metabolicEnergy: "4087 kcal/kg", protein: 76.0, fat: 12.6, moisture: 6.8, ash: 6.6, fiber: 0.7 },
    usageGuide: "Doğrudan kuru olarak verilebilir.",
    feedingGuide: "Ana öğünün yanında tamamlayıcı olarak verin.",
    storage: "Serin ve kuru ortamda saklayınız. Açıldıktan sonra 1 ay içinde tüketilmesi önerilir.",
    warning: null, badge: "%10 İndirim", rating: 0, reviewCount: 0
  },
  {
    id: 11,
    slug: "hindi-gogus-odulu-40g",
    name: "Freeze Dried Hindi Göğüs Ödülü 40g",
    shortName: "Hindi Göğüs",
    category: "odul",
    categoryLabel: "Kedi & Köpek Ödül",
    petType: "kedi-kopek",
    weight: "40g",
    price: 380,
    salePrice: 342,
    image: "img/products/hindi-gogus-odulu-40g.webp",
    originalImageUrl: "https://rawlabs.com.tr/wp-content/uploads/2025/04/8.png",
    sourceUrl: "https://rawlabs.com.tr/product/freeze-dried-hindi-gogus-odul-mamasi/",
    shortDescription: "%100 hindi göğüs etinden üretilen freeze dried ödül. En yüksek protein oranı, alerjik hayvanlar için uygun.",
    longDescription: "Hindi göğüs eti, tüm ödül ürünleri arasında en yüksek protein oranına sahiptir (%88,1). Alerjik hayvanlar için alternatif protein kaynağı sunar.",
    ingredients: ["%100 Hindi Göğüs Eti"],
    benefits: ["En yüksek protein oranı (%88,1)", "Alerjik hayvanlar için uygun", "Düşük yağ oranı", "Tek içerikli formül"],
    nutrition: { metabolicEnergy: "3814 kcal/kg", protein: 88.1, fat: 2.7, moisture: 5.5, ash: 5.0, fiber: 0.0 },
    usageGuide: "Doğrudan kuru olarak verilebilir. İsteğe bağlı su ile yumuşatılabilir.",
    feedingGuide: "Ana öğünün yanında tamamlayıcı olarak verin.",
    storage: "Serin ve kuru ortamda saklayınız. Açıldıktan sonra 1 ay içinde tüketilmesi önerilir.",
    warning: null, badge: "%10 İndirim", rating: 0, reviewCount: 0
  },
  {
    id: 12,
    slug: "hindi-yurek-odulu-40g",
    name: "Hindi Yürek Ödülü 40g",
    shortName: "Hindi Yürek",
    category: "odul",
    categoryLabel: "Kedi & Köpek Ödül",
    petType: "kedi-kopek",
    weight: "40g",
    price: 330,
    salePrice: 297,
    image: "img/products/hindi-yurek-odulu-40g.webp",
    originalImageUrl: "https://rawlabs.com.tr/wp-content/uploads/2025/04/9.png",
    sourceUrl: "https://rawlabs.com.tr/product/freeze-dried-hindi-yurek-kedi-kopek-odul-mamasi/",
    shortDescription: "%100 hindi yürekten üretilen freeze dried ödül. Doğal taurin ve B12 vitamini kaynağı.",
    longDescription: "Hindi yürek, doğal taurin ve B12 vitamini kaynağıdır. Kalp sağlığını destekler.",
    ingredients: ["%100 Hindi Yürek"],
    benefits: ["Doğal taurin kaynağı", "B12 vitamini", "Kalp sağlığını destekler", "Yüksek protein"],
    nutrition: { metabolicEnergy: "4204 kcal", protein: 68.2, fat: 20.7, moisture: 3.7, ash: 5.6, fiber: 0.2 },
    usageGuide: "Doğrudan kuru olarak verilebilir.",
    feedingGuide: "Ana öğünün yanında tamamlayıcı olarak verin.",
    storage: "Serin ve kuru ortamda saklayınız. Açıldıktan sonra 1 ay içinde tüketilmesi önerilir.",
    warning: null, badge: "%10 İndirim", rating: 0, reviewCount: 0
  },
  {
    id: 13,
    slug: "hindi-ciger-odulu-40g",
    name: "Hindi Ciğer Ödülü 40g",
    shortName: "Hindi Ciğer",
    category: "odul",
    categoryLabel: "Kedi & Köpek Ödül",
    petType: "kedi-kopek",
    weight: "40g",
    price: 414,
    salePrice: 372.60,
    image: "img/products/hindi-ciger-odulu-40g.webp",
    originalImageUrl: "https://rawlabs.com.tr/wp-content/uploads/2025/04/7.png",
    sourceUrl: "https://rawlabs.com.tr/product/freeze-dried-hindi-ciger-kedi-kopek-odul-mamasi/",
    shortDescription: "%100 hindi ciğerden üretilen freeze dried ödül. B12 vitamini ve çinko kaynağı, iştah açıcı.",
    longDescription: "Hindi ciğer, B12 vitamini ve çinko açısından zengindir. İştah açıcı etkiye sahiptir.",
    ingredients: ["%100 Hindi Ciğer"],
    benefits: ["B12 vitamini kaynağı", "Çinko içerir", "İştah açıcı", "Yüksek protein"],
    nutrition: { metabolicEnergy: "4027 kcal", protein: 75.9, fat: 5.3, moisture: 4.2, ash: 0.2, fiber: 0.0 },
    usageGuide: "Doğrudan kuru olarak verilebilir.",
    feedingGuide: "Ana öğünün yanında tamamlayıcı olarak verin.",
    storage: "Serin ve kuru ortamda saklayınız. Açıldıktan sonra 1 ay içinde tüketilmesi önerilir.",
    warning: "Yüksek besin yoğunluğu — kontrollü tüketim önerilir.",
    badge: "%10 İndirim", rating: 0, reviewCount: 0
  },
  {
    id: 14,
    slug: "kuzu-girtlak-odulu-40g",
    name: "Kuzu Gırtlak Ödülü 40g",
    shortName: "Kuzu Gırtlak",
    category: "odul",
    categoryLabel: "Köpek Ödül",
    petType: "kopek",
    weight: "40g",
    price: 324,
    salePrice: 291.60,
    image: "img/products/kuzu-girtlak-odulu-40g.webp",
    originalImageUrl: "https://rawlabs.com.tr/wp-content/uploads/2025/04/10.png",
    sourceUrl: "https://rawlabs.com.tr/product/freeze-dried-kuzu-girtlak-kopek-odul-mamasi/",
    shortDescription: "%100 kuzu gırtlaktan üretilen freeze dried köpek ödülü. Glukozamin & kondroitin ile eklem ve diş sağlığını destekler.",
    longDescription: "Kuzu gırtlak, doğal glukozamin ve kondroitin kaynağıdır. Eklem sağlığını desteklerken kemirme aktivitesiyle diş sağlığına katkıda bulunur.",
    ingredients: ["%100 Kuzu Gırtlak"],
    benefits: ["Doğal glukozamin & kondroitin", "Eklem sağlığını destekler", "Diş sağlığına katkı", "Kemirme ödülü"],
    nutrition: { metabolicEnergy: "4207 kcal/kg", protein: 53.0, fat: 33.4, moisture: 5.5, ash: 0.2, fiber: 1.6 },
    usageGuide: "Kemirme ödülü olarak verin.",
    feedingGuide: "Günlük miktarı köpeğinizin kilosuna göre ayarlayın.",
    storage: "Serin ve kuru ortamda saklayınız. Açıldıktan sonra 1 ay içinde tüketilmesi önerilir.",
    warning: null, badge: "%10 İndirim", rating: 0, reviewCount: 0
  },
  {
    id: 15,
    slug: "tavuk-gogus-odulu-40g",
    name: "Tavuk Göğüs Ödülü 40g",
    shortName: "Tavuk Göğüs",
    category: "odul",
    categoryLabel: "Kedi & Köpek Ödül",
    petType: "kedi-kopek",
    weight: "40g",
    price: 355,
    salePrice: 319.50,
    image: "img/products/tavuk-gogus-odulu-40g.webp",
    originalImageUrl: "https://rawlabs.com.tr/wp-content/uploads/2025/04/12.png",
    sourceUrl: "https://rawlabs.com.tr/product/freeze-dried-tavuk-gogus-kedi-kopek-odulu/",
    shortDescription: "%100 tavuk göğüs etinden üretilen freeze dried ödül. Yüksek protein, tüm yaş grupları için uygun.",
    longDescription: "Tavuk göğüs eti, yüksek proteinli ve kolay sindirilebilir yapısıyla tüm yaş gruplarındaki kedi ve köpekler için uygundur.",
    ingredients: ["%100 Tavuk Göğüs Eti"],
    benefits: ["Yüksek protein", "Tüm yaş grupları için uygun", "Kolay sindirilebilir", "Tek içerikli formül"],
    nutrition: { metabolicEnergy: null, protein: 51.3, fat: 2.45, moisture: 0.52, ash: 4.13, fiber: null },
    usageGuide: "Doğrudan kuru olarak verilebilir.",
    feedingGuide: "Ana öğünün yanında tamamlayıcı olarak verin.",
    storage: "Serin ve kuru ortamda saklayınız. Açıldıktan sonra 1 ay içinde tüketilmesi önerilir.",
    warning: null, badge: "%10 İndirim", rating: 0, reviewCount: 0
  },
  {
    id: 16,
    slug: "tavuk-ciger-odulu-40g",
    name: "Tavuk Ciğer Ödülü 40g",
    shortName: "Tavuk Ciğer",
    category: "odul",
    categoryLabel: "Kedi & Köpek Ödül",
    petType: "kedi-kopek",
    weight: "40g",
    price: 310,
    salePrice: 279,
    image: "img/products/tavuk-ciger-odulu-40g.webp",
    originalImageUrl: "https://rawlabs.com.tr/wp-content/uploads/2025/04/11.png",
    sourceUrl: "https://rawlabs.com.tr/product/freeze-dried-tavuk-ciger-kedi-kopek-odul-mamasi/",
    shortDescription: "%100 tavuk ciğerden üretilen freeze dried ödül. A ve B vitaminleri kaynağı, iştah açıcı.",
    longDescription: "Tavuk ciğer, A ve B vitaminleri açısından zengindir. İştah açıcı etkisiyle beslenme güçlüğü çeken hayvanlarda faydalıdır.",
    ingredients: ["%100 Tavuk Ciğer"],
    benefits: ["A ve B vitaminleri kaynağı", "İştah açıcı etki", "Yüksek protein", "Tek içerikli formül"],
    nutrition: { metabolicEnergy: "3308 kcal/kg", protein: 63.2, fat: 18.4, moisture: 3.5, ash: 0.2, fiber: 1.6 },
    usageGuide: "Doğrudan kuru olarak verilebilir.",
    feedingGuide: "Ana öğünün yanında tamamlayıcı olarak verin.",
    storage: "Serin ve kuru ortamda saklayınız. Açıldıktan sonra 1 ay içinde tüketilmesi önerilir.",
    warning: null, badge: "%10 İndirim", rating: 0, reviewCount: 0
  },
  {
    id: 17,
    slug: "hamsi-odulu-40g",
    name: "Freeze Dried Hamsi Ödülü 40g",
    shortName: "Hamsi",
    category: "odul",
    categoryLabel: "Kedi & Köpek Ödül",
    petType: "kedi-kopek",
    weight: "40g",
    price: 355,
    salePrice: 319.50,
    image: "img/products/hamsi-odulu-40g.webp",
    originalImageUrl: "https://rawlabs.com.tr/wp-content/uploads/2022/03/6.png",
    sourceUrl: "https://rawlabs.com.tr/product/freeze-dried-hamsi-odulu-kedi-kopek-odulu/",
    shortDescription: "%100 hamsiden üretilen freeze dried ödül. Omega-3 kaynağı, deri ve tüy sağlığını destekler.",
    longDescription: "Hamsi, doğal Omega-3 yağ asitleri açısından zengindir. Deri ve tüy sağlığını desteklerken, yüksek enerji değeri sunar.",
    ingredients: ["%100 Hamsi"],
    benefits: ["Omega-3 kaynağı", "Deri ve tüy sağlığını destekler", "Yüksek enerji değeri", "Tek içerikli formül"],
    nutrition: { metabolicEnergy: "4859 kcal/kg", protein: 48.3, fat: 37.2, moisture: 6.0, ash: 8.3, fiber: 0.0 },
    usageGuide: "Doğrudan kuru olarak verilebilir.",
    feedingGuide: "Ana öğünün yanında tamamlayıcı olarak verin.",
    storage: "Serin ve kuru ortamda saklayınız. Açıldıktan sonra 1 ay içinde tüketilmesi önerilir.",
    warning: null, badge: "%10 İndirim", rating: 0, reviewCount: 0
  }
];

// En çok satanları getir
function getBestSellers(count = 6) {
  return PRODUCTS.filter(p => p.badge || p.reviewCount > 0)
    .sort((a, b) => b.reviewCount - a.reviewCount)
    .slice(0, count);
}

// Kategoriye göre filtrele
function getProductsByCategory(category) {
  return PRODUCTS.filter(p => p.category === category);
}

// Hayvan türüne göre filtrele
function getProductsByPetType(petType) {
  return PRODUCTS.filter(p => p.petType === petType || p.petType === "kedi-kopek");
}

// Ürün kartı HTML render
function renderProductCard(product) {
  const formatPrice = (p) => p.toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  
  let ratingHtml = '';
  if (product.reviewCount > 0) {
    const starsHtml = '★'.repeat(Math.floor(product.rating)) + (product.rating % 1 >= 0.5 ? '½' : '');
    ratingHtml = `
        <div class="product-rating">
          <span class="stars">${starsHtml}</span>
          <span class="rating-count">(${product.reviewCount})</span>
        </div>`;
  } else {
    ratingHtml = `
        <div class="product-rating no-reviews">
          <span class="rating-count">Henüz yorum yok</span>
        </div>`;
  }

  const badgeHtml = product.badge ? `<span class="product-badge">${product.badge}</span>` : '';
  
  const priceHtml = product.salePrice
    ? `<div class="product-price-wrapper">
         <del class="old-price">₺${formatPrice(product.price)}</del>
         <span class="new-price">₺${formatPrice(product.salePrice)}</span>
       </div>`
    : `<div class="product-price-wrapper">
         <span class="new-price">₺${formatPrice(product.price)}</span>
       </div>`;

  return `
    <article class="product-card animate-on-scroll" data-id="${product.id}">
      ${badgeHtml}
      <div class="product-image ${product.category === 'odul' ? 'product-image-odul' : ''}">
        <a href="urun.html?slug=${product.slug}">
          <img src="${product.image}" alt="${product.name}" loading="lazy">
        </a>
      </div>
      <div class="product-info">
        <span class="product-category">${product.categoryLabel}</span>
        <a href="urun.html?slug=${product.slug}" style="text-decoration:none; color:inherit;">
          <h3 class="product-name">${product.name}</h3>
        </a>
        ${ratingHtml}
        <span class="product-weight">${product.weight}</span>
        <div class="product-bottom">
          ${priceHtml}
          <button class="btn-incele" onclick="addToCartAndRedirect('${product.slug}')" style="cursor:pointer; border:none; font-family:inherit;">Satın Al</button>
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
