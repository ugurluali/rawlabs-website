/* ============================================
   RAWLABS - Main JavaScript
   ============================================ */

document.addEventListener('DOMContentLoaded', () => {
  initHeader();
  initMobileNav();
  initScrollAnimations();
  initBestSellers();
  initCartBadge();
  initMiniCart();
  initScrollToTop();
  initChatbot();
  initCookieBanner();
});

/* ---------- Sticky Header ---------- */
function initHeader() {
  const header = document.querySelector('.header');
  if (!header) return;
  const isHomePage = document.getElementById('hero') !== null;
  
  const onScroll = () => {
    if (isHomePage) {
      header.classList.toggle('scrolled', window.scrollY > 60);
    } else {
      header.classList.add('scrolled');
    }
  };
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();
}

/* ---------- Mobile Navigation ---------- */
function initMobileNav() {
  const hamburger = document.querySelector('.hamburger');
  const nav = document.querySelector('.nav');
  if (!hamburger || !nav) return;

  hamburger.addEventListener('click', () => {
    hamburger.classList.toggle('active');
    nav.classList.toggle('active');
    document.body.style.overflow = nav.classList.contains('active') ? 'hidden' : '';
  });

  nav.querySelectorAll('a').forEach(link => {
    link.addEventListener('click', () => {
      hamburger.classList.remove('active');
      nav.classList.remove('active');
      document.body.style.overflow = '';
    });
  });
}

/* ---------- Scroll Animations ---------- */
function initScrollAnimations() {
  const elements = document.querySelectorAll('.animate-on-scroll');
  if (!elements.length) return;

  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry, index) => {
      if (entry.isIntersecting) {
        setTimeout(() => {
          entry.target.classList.add('visible');
        }, index * 80);
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

  elements.forEach(el => observer.observe(el));
}

/* ---------- Best Sellers ---------- */
function initBestSellers() {
  if (typeof getBestSellers === 'function') {
    const bestSellers = getBestSellers(6);
    renderProducts(bestSellers, 'best-sellers-grid');
    // Re-init animations for dynamically added cards
    setTimeout(() => {
      document.querySelectorAll('.product-card.animate-on-scroll:not(.visible)').forEach(el => {
        const observer = new IntersectionObserver((entries) => {
          entries.forEach(entry => {
            if (entry.isIntersecting) {
              entry.target.classList.add('visible');
              observer.unobserve(entry.target);
            }
          });
        }, { threshold: 0.1 });
        observer.observe(el);
      });
    }, 100);
  }
}

/* ---------- Testimonials Carousel ---------- */
function initTestimonials() {
  const track = document.querySelector('.testimonials-track');
  const prevBtn = document.querySelector('.testimonial-prev');
  const nextBtn = document.querySelector('.testimonial-next');
  if (!track || !prevBtn || !nextBtn) return;

  let position = 0;
  const cards = track.querySelectorAll('.testimonial-card');
  const cardWidth = cards[0]?.offsetWidth + 24 || 400;
  const maxPos = -(cards.length - 3) * cardWidth;

  nextBtn.addEventListener('click', () => {
    position = Math.max(position - cardWidth, maxPos);
    track.style.transform = `translateX(${position}px)`;
  });

  prevBtn.addEventListener('click', () => {
    position = Math.min(position + cardWidth, 0);
    track.style.transform = `translateX(${position}px)`;
  });
}

/* ---------- Cart / Sepet Logic ---------- */
function getCart() {
  try {
    return JSON.parse(localStorage.getItem('rawlabs_cart')) || [];
  } catch (e) {
    return [];
  }
}

function saveCart(cart) {
  localStorage.setItem('rawlabs_cart', JSON.stringify(cart));
  updateCartBadge();
}

function addToCart(slug, quantity = 1) {
  // Orijinal veriden güncel bilgiyi al
  const product = typeof getProductBySlug === 'function' ? getProductBySlug(slug) : null;
  if (!product) return;
  
  const cart = getCart();
  const existing = cart.find(item => item.slug === slug);
  if (existing) {
    existing.quantity += quantity;
  } else {
    cart.push({ slug: slug, quantity: quantity });
  }
  saveCart(cart);
}

function addToCartAndRedirect(slug, quantity = 1) {
  addToCart(slug, quantity);
  openMiniCart(slug);
}

/* ---------- Mini Cart Drawer ---------- */
function initMiniCart() {
  // Overlay
  const overlay = document.createElement('div');
  overlay.className = 'mini-cart-overlay';
  overlay.addEventListener('click', closeMiniCart);
  document.body.appendChild(overlay);

  // Determine path prefix for blog subdirectory support
  const isBlogDir = window.location.pathname.includes('/blog/');
  const prefix = isBlogDir ? '../' : '';

  // Drawer
  const drawer = document.createElement('div');
  drawer.className = 'mini-cart-drawer';
  drawer.setAttribute('role', 'dialog');
  drawer.setAttribute('aria-label', 'Sepet Özeti');
  drawer.innerHTML = `
    <div class="mini-cart-header">
      <h3>🛒 Sepet Özeti</h3>
      <button type="button" class="mini-cart-close" aria-label="Kapat">×</button>
    </div>
    <div class="mini-cart-body">
      <div class="mini-cart-message">✅ Ürün sepete eklendi!</div>
      <div class="mini-cart-product" id="mini-cart-product"></div>
      <div class="mini-cart-summary" id="mini-cart-summary"></div>
    </div>
    <div class="mini-cart-footer">
      <button type="button" class="btn-mini-continue" id="mini-cart-continue">Alışverişe Devam Et</button>
      <a href="${prefix}sepet.html" class="btn-mini-goto">Sepete Git →</a>
    </div>
  `;
  document.body.appendChild(drawer);

  drawer.querySelector('.mini-cart-close').addEventListener('click', closeMiniCart);
  document.getElementById('mini-cart-continue').addEventListener('click', closeMiniCart);
}

function openMiniCart(addedSlug) {
  const drawer = document.querySelector('.mini-cart-drawer');
  const overlay = document.querySelector('.mini-cart-overlay');
  if (!drawer || !overlay) return;

  const product = typeof getProductBySlug === 'function' ? getProductBySlug(addedSlug) : null;
  const cart = getCart();
  const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);

  // Calculate total from product data
  let totalAmount = 0;
  cart.forEach(item => {
    const p = typeof getProductBySlug === 'function' ? getProductBySlug(item.slug) : null;
    if (p) {
      const price = p.salePrice ? p.salePrice : p.price;
      totalAmount += price * item.quantity;
    }
  });

  const formatPrice = (n) => n.toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

  // Product info
  const productEl = document.getElementById('mini-cart-product');
  if (productEl && product) {
    productEl.innerHTML = `
      <div class="mini-cart-item">
        <img src="${product.image}" alt="${product.name}">
        <div class="mini-cart-item-info">
          <span class="mini-cart-item-name">${product.name}</span>
          <span class="mini-cart-item-weight">📦 ${product.weight}</span>
          <span class="mini-cart-item-price">₺${formatPrice(product.salePrice || product.price)}</span>
        </div>
      </div>
    `;
  }

  // Summary
  const summaryEl = document.getElementById('mini-cart-summary');
  if (summaryEl) {
    summaryEl.innerHTML = `
      <div class="mini-cart-row">
        <span>Toplam Ürün Adedi</span>
        <span><strong>${totalItems} adet</strong></span>
      </div>
      <div class="mini-cart-row mini-cart-total">
        <span>Sepet Toplamı</span>
        <span><strong>₺${formatPrice(totalAmount)}</strong></span>
      </div>
    `;
  }

  overlay.classList.add('active');
  drawer.classList.add('active');
  document.body.style.overflow = 'hidden';
}

function closeMiniCart() {
  const drawer = document.querySelector('.mini-cart-drawer');
  const overlay = document.querySelector('.mini-cart-overlay');
  if (drawer) drawer.classList.remove('active');
  if (overlay) overlay.classList.remove('active');
  document.body.style.overflow = '';
}

function updateCartBadge() {
  const badge = document.getElementById('cart-badge');
  if (!badge) return;
  const cart = getCart();
  const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
  if (totalItems > 0) {
    badge.textContent = totalItems;
    badge.style.display = 'inline-block';
  } else {
    badge.style.display = 'none';
  }
}

function initCartBadge() {
  // HTML değiştirmeden navbar'daki Alışveriş ikonunu Sepete çeviriyoruz
  const navCta = document.querySelector('.nav-cta');
  if (navCta && navCta.textContent.includes('Alışveriş')) {
    navCta.href = 'sepet.html';
    navCta.style.position = 'relative';
    navCta.innerHTML = '🛒 Sepetim <span id="cart-badge" style="position:absolute; top:-8px; right:-12px; background:var(--primary); color:white; border-radius:50%; padding:2px 6px; font-size:12px; font-weight:bold; display:none; line-height:1;">0</span>';
  }
  updateCartBadge();
}

/* ---------- Scroll to Top Button ---------- */
function initScrollToTop() {
  const btn = document.createElement('button');
  btn.type = 'button';
  btn.className = 'btn-scroll-top';
  btn.setAttribute('aria-label', 'Yukarı çık');
  btn.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 15l-6-6-6 6"/></svg>';
  document.body.appendChild(btn);

  btn.addEventListener('click', () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });

  const onScroll = () => {
    if (window.scrollY > 300) {
      btn.classList.add('visible');
    } else {
      btn.classList.remove('visible');
    }
  };

  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();
}

/* ---------- AI-Ready Live Support / Chatbot ---------- */
function initChatbot() {
  const isBlogDir = window.location.pathname.includes('/blog/');
  const resolveUrl = (path) => isBlogDir ? '../' + path : path;

  // Trigger Button
  const triggerBtn = document.createElement('button');
  triggerBtn.type = 'button';
  triggerBtn.className = 'btn-live-support';
  triggerBtn.setAttribute('aria-label', 'Canlı Destek');
  triggerBtn.innerHTML = '<span style="font-size:1.2rem">💬</span> Canlı Destek';
  document.body.appendChild(triggerBtn);

  // Chat Window Container
  const chatWindow = document.createElement('div');
  chatWindow.className = 'chatbot-window';
  chatWindow.innerHTML = `
    <div class="chatbot-header">
      <div>
        <h4 style="color:#fff; font-size:1.05rem; margin-bottom:2px;">💬 Rawlabs Canlı Destek</h4>
        <span style="font-size:0.78rem; color:rgba(255,255,255,0.8); display:block;">Mama önerisi ve sipariş desteği</span>
      </div>
      <button type="button" class="chatbot-close" aria-label="Kapat">×</button>
    </div>
    <div style="background:#fff5f5; color:#e11d48; font-size:0.75rem; padding:8px 12px; border-bottom:1px solid #ffe4e6; line-height:1.4; font-weight: 500; text-align: center;">
      ⚠️ Bu bir yapay zeka asistanıdır. Lütfen şifre, kart bilgisi veya özel kişisel bilgilerinizi paylaşmayınız.
    </div>
    <div class="chatbot-messages" id="chatbot-messages">
      <div class="chatbot-msg bot">
        Merhaba 👋 Rawlabs’a hoş geldiniz. Size nasıl yardımcı olabiliriz?
      </div>
      <div class="chatbot-options">
        <button type="button" class="chatbot-option-btn" data-opt="mama">Mama önerisi al</button>
        <button type="button" class="chatbot-option-btn" data-opt="urun">Ürünleri incele</button>
        <button type="button" class="chatbot-option-btn" data-opt="nedir">Freeze-dried mama nedir?</button>
        <button type="button" class="chatbot-option-btn" data-opt="kargo">Kargo ve teslimat</button>
        <button type="button" class="chatbot-option-btn" data-opt="wp">WhatsApp’a bağlan</button>
      </div>
    </div>
    <form class="chatbot-input-area" id="chatbot-form">
      <input type="text" id="chatbot-input" placeholder="Mesajınızı yazın..." aria-label="Mesaj yazın" required>
      <button type="submit" id="chatbot-submit-btn" aria-label="Gönder">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
      </button>
    </form>
  `;
  document.body.appendChild(chatWindow);

  const messagesContainer = chatWindow.querySelector('#chatbot-messages');
  const chatForm = chatWindow.querySelector('#chatbot-form');
  const chatInput = chatWindow.querySelector('#chatbot-input');
  const chatSubmitBtn = chatWindow.querySelector('#chatbot-submit-btn');

  // Toggle open/close
  triggerBtn.addEventListener('click', () => {
    chatWindow.classList.toggle('active');
    if (chatWindow.classList.contains('active')) {
      chatInput.focus();
    }
  });

  chatWindow.querySelector('.chatbot-close').addEventListener('click', () => {
    chatWindow.classList.remove('active');
  });

  // Helper to append message
  function appendMessage(sender, text) {
    const msgDiv = document.createElement('div');
    msgDiv.className = `chatbot-msg ${sender}`;
    msgDiv.innerHTML = text;
    messagesContainer.appendChild(msgDiv);
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
  }

  let typingIndicator = null;
  function showTyping() {
    typingIndicator = document.createElement('div');
    typingIndicator.className = 'chatbot-msg bot';
    typingIndicator.style.opacity = '0.75';
    typingIndicator.style.fontStyle = 'italic';
    typingIndicator.textContent = 'Rawlabs Asistanı yazıyor...';
    messagesContainer.appendChild(typingIndicator);
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
  }

  function removeTyping() {
    if (typingIndicator) {
      typingIndicator.remove();
      typingIndicator = null;
    }
  }

  // Sohbet geçmişi (sadece AI konuşmaları için)
  let chatHistory = [];
  const MAX_HISTORY = 6;

  // Hassas veri filtresi (16 haneli olası kart numaralarını maskeler)
  function filterSensitive(text) {
    if (!text) return '';
    return text.replace(/(?:\d[ -]*?){13,16}/g, '[KVKK GİZLENDİ]');
  }

  // Handle message sending to secure backend or hybrid canned responses
  function handleChatMessage(userText) {
    appendMessage('user', userText);
    
    const lowerText = userText.toLowerCase();
    
    // AI triggers (danışmanlık gerektiren ifadeler)
    const aiKeywords = [
      'nasıl geçiş',
      'nasıl başlam',
      'nasıl kullan',
      'hangi ürün',
      'ne öner',
      'önerirsiniz',
      'ürün öner',
      'seçemiyorum',
      'alerji',
      'alerjik',
      'hassasiyet',
      'iştahsız',
      'yavru',
      'kısır',
      'barf farkı',
      'yardım al'
    ];
    let needsAI = aiKeywords.some(kw => lowerText.includes(kw));

    let cannedReply = null;

    if (!needsAI) {
      if (lowerText.includes('kargo ücret') || lowerText.includes('kargo ne kadar') || lowerText.includes('kargo kaç')) {
        cannedReply = "3.000 TL ve üzeri siparişlerde kargo ücretsizdir. 3.000 TL altındaki siparişlerde sabit 300 TL kargo ücreti uygulanır.";
      } else if (lowerText.includes('ücretsiz kargo') || lowerText.includes('kargo bedava')) {
        cannedReply = "Rawlabs’ta ücretsiz kargo limiti 3.000 TL’dir. Sepet tutarınız 3.000 TL ve üzerine ulaştığında kargo ücreti otomatik olarak ücretsiz olur.";
      } else if (lowerText.includes('teslimat') || lowerText.includes('ne zaman kargo') || lowerText.includes('kargo ne zaman')) {
        cannedReply = "Siparişler ödeme onayından sonra hazırlanır ve en kısa sürede kargoya teslim edilir. Kargoya verildiğinde takip bilgileriniz e-posta yoluyla tarafınıza iletilir. Yoğun dönemlerde veya resmi tatillerde teslim süresi değişebilir.";
      } else if (lowerText.includes('iade') || lowerText.includes('değişim')) {
        cannedReply = "Rawlabs ürünleri gıda niteliğinde olduğu için iade ve değişim süreçleri hijyen, ambalaj bütünlüğü ve yasal koşullar çerçevesinde değerlendirilir. Ambalajı açılmış, kullanılmış veya saklama koşulları bozulmuş ürünlerde iade kabul edilemeyebilir. Detaylı bilgi için İade ve Geri Ödeme Politikası sayfamızı inceleyebilirsiniz.";
      } else if (lowerText.includes('freeze-dried nedir') || lowerText.includes('freeze dry nedir') || lowerText.includes('dondurarak kurutma') || lowerText.includes('nedir')) {
        cannedReply = "Freeze-dried, taze içeriklerin düşük sıcaklıkta dondurulduktan sonra neminin alınmasıyla elde edilen özel bir kurutma teknolojisidir. Bu yöntemde ürünler pişirilmez ve yüksek ısıl işleme maruz kalmaz. Böylece doğal aroma, koku ve besin değerlerinin mümkün olduğunca korunması hedeflenir.";
      } else if (lowerText.includes('ödül mama')) {
        cannedReply = "Rawlabs ödül mamaları eğitim, motivasyon veya ara öğün desteği için küçük porsiyonlarda kullanılabilir. Günlük toplam beslenme miktarı dikkate alınarak verilmesi önerilir.";
      } else if (lowerText.includes('kedi mama') || lowerText.includes('kedim için') || lowerText.includes('kediler için')) {
        cannedReply = "Rawlabs’ta kediler için tam mama ve ödül maması seçenekleri bulunur. Kedinizin yaşı, kilosu, kısır olup olmadığı ve varsa hassasiyetlerine göre seçim yapmanız önerilir. Dilerseniz size ürün seçiminde yardımcı olabilirim.";
      } else if (lowerText.includes('köpek mama') || lowerText.includes('köpeğim için') || lowerText.includes('köpekler için')) {
        cannedReply = "Rawlabs’ta köpekler için tam mama ve ödül maması seçenekleri bulunur. Köpeğinizin yaşı, kilosu, aktivite düzeyi ve varsa hassasiyetlerine göre uygun ürünü seçebilirsiniz. Dilerseniz ürün seçiminde birlikte ilerleyebiliriz.";
      } else if (lowerText.includes('ödeme') || lowerText.includes('kredi kartı') || lowerText.includes('taksit') || lowerText.includes('havale')) {
        cannedReply = "Rawlabs’ta ödemeler kredi kartı ile Kuveyt Türk Sanal POS / 3D Secure altyapısı üzerinden güvenli şekilde alınır. Kart bilgileriniz Rawlabs tarafından saklanmaz.";
      } else if (lowerText.includes('sipariş durum') || lowerText.includes('siparişim') || lowerText.includes('kargom nerede') || lowerText.includes('takip')) {
        cannedReply = "Güvenliğiniz gereği sipariş detaylarınızı buradan görüntüleyemiyoruz. Siparişinizi Hesabım sayfasından kontrol edebilir veya bilgi@rawlabs.com.tr adresinden bize ulaşabilirsiniz.";
      } else if (lowerText.includes('iletişim') || lowerText.includes('telefon') || lowerText.includes('numara') || lowerText.includes('ulaşabilirim') || lowerText.includes('whatsapp')) {
        cannedReply = "Bize bilgi@rawlabs.com.tr adresinden, +90 532 420 66 35 numaralı telefondan veya web sitemizdeki iletişim formundan ulaşabilirsiniz.";
      } else if (lowerText.includes('kampanya') || lowerText.includes('indirim')) {
        cannedReply = "Güncel kampanya ve avantajları ana sayfamızdaki kampanya alanından takip edebilirsiniz. Kampanya koşulları dönemsel olarak değişebilir.";
      }
    }

    // Eğer hazır cevapla eşleştiyse, hemen ekranda göster ve AI'a gitme
    if (cannedReply) {
      appendMessage('bot', cannedReply);
      return;
    }
    
    // Hazır cevapla eşleşmiyorsa (veya AI zorunluysa), backend'e gönder
    // Disable inputs for AI loading state
    chatInput.disabled = true;
    chatSubmitBtn.disabled = true;
    
    showTyping();

    fetch(resolveUrl('api/chatbot.php'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ 
        message: userText,
        history: chatHistory
      })
    })
    .then(res => {
      if (!res.ok) throw new Error('Sunucu hatası');
      return res.json();
    })
    .then(data => {
      removeTyping();
      if (data && data.status === 'success' && data.reply) {
        appendMessage('bot', data.reply);
        
        // Başarılı yapay zeka işlemlerini geçmişe kaydet (KVKK filtreli)
        chatHistory.push({ role: 'user', content: filterSensitive(userText) });
        chatHistory.push({ role: 'assistant', content: data.reply });
        
        // Yalnızca son N mesajı tut
        if (chatHistory.length > MAX_HISTORY) {
          chatHistory = chatHistory.slice(chatHistory.length - MAX_HISTORY);
        }
      } else {
        appendMessage('bot', 'Şu anda asistanımıza bağlanamıyorum. Dilerseniz iletişim sayfasından bize ulaşabilirsiniz.');
      }
    })
    .catch(err => {
      console.error('Chatbot Hatası:', err);
      removeTyping();
      appendMessage('bot', 'Şu anda asistanımıza bağlanamıyorum. Dilerseniz iletişim sayfasından bize ulaşabilirsiniz.');
    })
    .finally(() => {
      chatInput.disabled = false;
      chatSubmitBtn.disabled = false;
      chatInput.focus();
    });
  }

  // Submit custom message
  chatForm.addEventListener('submit', (e) => {
    e.preventDefault();
    const text = chatInput.value.trim();
    if (!text) return;
    chatInput.value = ''; // Kullanıcı gönderir göndermez kutuyu temizle
    handleChatMessage(text);
  });

  // Quick replies actions (connected to the same smart backend endpoint)
  chatWindow.querySelectorAll('.chatbot-option-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const text = btn.textContent;
      handleChatMessage(text);
    });
  });
}

/* ---------- Çerez Onay (Cookie Consent) Mantığı ---------- */

function loadOptionalTrackingScripts() {
  // Gelecekte eklenecek olan Google Analytics, Facebook Pixel, vb. opsiyonel izleme/pazarlama scriptleri burada tetiklenecektir.
  // Örnek: console.log("Opsiyonel izleme scriptleri yüklendi.");
}

function resetCookieConsent() {
  localStorage.removeItem('rawlabs_cookie_consent');
  initCookieBanner();
}

function initCookieBanner() {
  // Admin panellerinde veya /api/ yollarında banner gösterilmesin
  const path = window.location.pathname;
  const isAdminPage = path.includes('/admin') || path.includes('admin-orders.php') || path.includes('/api/');
  if (isAdminPage) return;

  const storageKey = 'rawlabs_cookie_consent';
  let consent = null;

  // localStorage'da kayıtlı rıza kontrolü
  try {
    const rawConsent = localStorage.getItem(storageKey);
    if (rawConsent) {
      consent = JSON.parse(rawConsent);
    }
  } catch (e) {
    console.error('Çerez rıza verisi okunamadı:', e);
  }

  // GPC (Global Privacy Control) kontrolü
  const isGpcActive = navigator.globalPrivacyControl === true;

  // Eğer zaten bir rıza seçimi varsa ve bu seçim geçerliyse
  if (consent && consent.status) {
    if (consent.status === 'accepted') {
      loadOptionalTrackingScripts();
    }
    return;
  }

  // Eğer kullanıcı tarayıcıda GPC tercihini açmışsa ve henüz bir tercih kaydetmemişse
  // mevzuat gereği varsayılan olarak çerezleri reddedilmiş (rejected) olarak kabul ediyoruz.
  if (isGpcActive && !consent) {
    const defaultGpcConsent = {
      status: 'rejected',
      gpc: true,
      updatedAt: new Date().toISOString()
    };
    try {
      localStorage.setItem(storageKey, JSON.stringify(defaultGpcConsent));
    } catch (e) {
      console.error('GPC çerez rıza verisi kaydedilemedi:', e);
    }
    return;
  }

  // Blog dizini altındaysak relative path düzeltmesi yapıyoruz (404 hatasını önlemek için)
  const isBlogDir = path.includes('/blog/');
  const pathPrefix = isBlogDir ? '../' : '';
  const policyUrl = pathPrefix + 'cerez-politikasi.html';

  // Eğer banner zaten sayfada varsa tekrar ekleme
  if (document.getElementById('cookie-consent-banner')) return;

  // Banner elemanının oluşturulması
  const banner = document.createElement('div');
  banner.id = 'cookie-consent-banner';
  banner.className = 'cookie-consent-banner';
  banner.setAttribute('role', 'dialog');
  banner.setAttribute('aria-label', 'Çerez Onay Bildirimi');

  banner.innerHTML = `
    <div class="cookie-consent-content">
      <p>
        Sizlere daha iyi bir deneyim sunabilmek amacıyla sitemizde zorunlu çerezler kullanmaktayız. Ayrıca onay vermeniz durumunda analiz ve pazarlama amaçlı çerezler de kullanılacaktır. Detaylı bilgi için <a href="${policyUrl}" class="cookie-policy-link">Çerez Politikamızı</a> inceleyebilirsiniz.
      </p>
      <div class="cookie-consent-buttons">
        <button type="button" class="cookie-consent-btn cookie-consent-btn-reject" id="cookie-reject">Reddet</button>
        <button type="button" class="cookie-consent-btn cookie-consent-btn-accept" id="cookie-accept">Kabul Et</button>
      </div>
    </div>
  `;

  document.body.appendChild(banner);

  // Buton olay dinleyicileri
  const acceptBtn = document.getElementById('cookie-accept');
  const rejectBtn = document.getElementById('cookie-reject');

  if (acceptBtn) {
    acceptBtn.addEventListener('click', () => {
      const consentData = {
        status: 'accepted',
        gpc: isGpcActive,
        updatedAt: new Date().toISOString()
      };
      try {
        localStorage.setItem(storageKey, JSON.stringify(consentData));
      } catch (e) {
        console.error('Rıza verisi kaydedilemedi:', e);
      }
      loadOptionalTrackingScripts();
      closeCookieBanner();
    });
  }

  if (rejectBtn) {
    rejectBtn.addEventListener('click', () => {
      const consentData = {
        status: 'rejected',
        gpc: isGpcActive,
        updatedAt: new Date().toISOString()
      };
      try {
        localStorage.setItem(storageKey, JSON.stringify(consentData));
      } catch (e) {
        console.error('Rıza verisi kaydedilemedi:', e);
      }
      closeCookieBanner();
    });
  }

  function closeCookieBanner() {
    banner.classList.add('cookie-consent-fade-out');
    // Animasyon tamamlandıktan sonra DOM'dan kaldır
    banner.addEventListener('animationend', () => {
      banner.remove();
    }, { once: true });
  }
}

