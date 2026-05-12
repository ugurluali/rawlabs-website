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
      <button type="submit" aria-label="Gönder">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
      </button>
    </form>
  `;
  document.body.appendChild(chatWindow);

  const messagesContainer = chatWindow.querySelector('#chatbot-messages');
  const chatForm = chatWindow.querySelector('#chatbot-form');
  const chatInput = chatWindow.querySelector('#chatbot-input');

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

  // Placeholder AI function for future integration
  function handleChatMessage(userText) {
    appendMessage('user', userText);
    
    // Simulate thinking delay
    setTimeout(() => {
      appendMessage('bot', 'Yapay zeka destekli asistanımız yakında aktif olacak. Şimdilik hızlı seçenekleri kullanabilir veya WhatsApp üzerinden bize ulaşabilirsiniz.');
    }, 400);
  }

  // Submit custom message
  chatForm.addEventListener('submit', (e) => {
    e.preventDefault();
    const text = chatInput.value.trim();
    if (!text) return;
    chatInput.value = '';
    handleChatMessage(text);
  });

  // Quick replies actions
  chatWindow.querySelectorAll('.chatbot-option-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const opt = btn.getAttribute('data-opt');
      appendMessage('user', btn.textContent);

      setTimeout(() => {
        if (opt === 'mama') {
          appendMessage('bot', `Patili dostunuza en uygun mamayı ve porsiyonu bulmak için testimizi çözebilirsiniz:<br><a href="${resolveUrl('mama-onerisi.html')}" style="color:var(--primary); font-weight:bold; text-decoration:underline; display:inline-block; margin-top:4px;">Mama Önerisi Testine Git →</a>`);
        } else if (opt === 'urun') {
          appendMessage('bot', `Freeze dry teknolojisiyle üretilen %100 doğal kedi ve köpek mamalarımızı inceleyebilirsiniz:<br><a href="${resolveUrl('magaza.html')}" style="color:var(--primary); font-weight:bold; text-decoration:underline; display:inline-block; margin-top:4px;">Mağazaya Göz At →</a>`);
        } else if (opt === 'nedir') {
          appendMessage('bot', `Freeze dry, besin değerlerini %97 oranında koruyan en sağlıklı saklama yöntemidir. Pişirme yapılmadığı için etin doğallığı korunur.<br><a href="${resolveUrl('blog/freeze-dry-mama-nedir.html')}" style="color:var(--primary); font-weight:bold; text-decoration:underline; display:inline-block; margin-top:4px;">Detaylı Yazımızı Okuyun →</a>`);
        } else if (opt === 'kargo') {
          appendMessage('bot', `Siparişleriniz özenle hazırlanıp en kısa sürede teslim edilmektedir. Teslimat koşullarımız için:<br><a href="${resolveUrl('teslimat-kosullari.html')}" style="color:var(--primary); font-weight:bold; text-decoration:underline; display:inline-block; margin-top:4px;">Teslimat Koşulları →</a>`);
        } else if (opt === 'wp') {
          appendMessage('bot', `WhatsApp destek hattımız üzerinden uzman ekibimizle anında görüşebilirsiniz:<br><a href="https://wa.me/905324206635" target="_blank" style="color:var(--primary); font-weight:bold; text-decoration:underline; display:inline-block; margin-top:4px;">WhatsApp'a Bağlan →</a>`);
        }
      }, 300);
    });
  });
}
