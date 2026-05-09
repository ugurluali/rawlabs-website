/* ============================================
   RAWLABS - Main JavaScript
   ============================================ */

document.addEventListener('DOMContentLoaded', () => {
  initHeader();
  initMobileNav();
  initScrollAnimations();
  initBestSellers();
  initCartBadge();
});

/* ---------- Sticky Header ---------- */
function initHeader() {
  const header = document.querySelector('.header');
  if (!header) return;
  const onScroll = () => {
    header.classList.toggle('scrolled', window.scrollY > 60);
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
  window.location.href = 'sepet.html';
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
