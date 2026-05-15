/**
 * Rawlabs - Kimlik Doğrulama Frontend Modülü
 * Tüm sayfalarda auth durumunu kontrol eder ve nav'ı günceller.
 * hesabim.html sayfasında form işlemlerini yönetir.
 */

const AUTH_API_BASE = window.location.pathname.includes('/blog/') ? '../api/' : 'api/';

const AUTH_API = {
  register: AUTH_API_BASE + 'auth-register.php',
  login: AUTH_API_BASE + 'auth-login.php',
  me: AUTH_API_BASE + 'auth-me.php',
  logout: AUTH_API_BASE + 'auth-logout.php',
  forgotPassword: AUTH_API_BASE + 'auth-forgot-password.php',
  resetPassword: AUTH_API_BASE + 'auth-reset-password.php'
};

/**
 * Sayfa yüklendiğinde auth durumunu kontrol et
 */
document.addEventListener('DOMContentLoaded', () => {
  checkAuthStatus();
  initAuthPage();
});

/**
 * Auth durumunu kontrol et ve nav'ı güncelle
 */
async function checkAuthStatus() {
  try {
    const res = await fetch(AUTH_API.me, { credentials: 'same-origin' });
    const data = await res.json();

    const authLinks = document.querySelectorAll('.nav-auth');
    authLinks.forEach(link => {
      if (data.loggedIn && data.user) {
        link.textContent = '👤 ' + data.user.fullName.split(' ')[0];
        link.title = data.user.fullName;
      }
    });

    // hesabim.html sayfasındaysa uygun view'ı göster
    if (document.getElementById('auth-page-container')) {
      if (data.loggedIn && data.user) {
        showProfileView(data.user);
      } else {
        // URL'de reset token varsa reset view'ı göster
        const params = new URLSearchParams(window.location.search);
        if (params.get('action') === 'reset' && params.get('token')) {
          showView('reset');
        } else {
          showView('login');
        }
      }
    }
  } catch (e) {
    // API erişilemezse (örn: local geliştirme) sessizce devam et
  }
}

/**
 * hesabim.html sayfası için form ve buton dinleyicileri
 */
function initAuthPage() {
  const container = document.getElementById('auth-page-container');
  if (!container) return;

  // Tab butonları
  document.querySelectorAll('[data-auth-tab]').forEach(btn => {
    btn.addEventListener('click', () => {
      showView(btn.dataset.authTab);
    });
  });

  // Giriş formu
  const loginForm = document.getElementById('login-form');
  if (loginForm) {
    loginForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const btn = loginForm.querySelector('button[type="submit"]');
      const originalText = btn.textContent;
      btn.disabled = true;
      btn.textContent = 'Giriş yapılıyor...';

      try {
        const res = await fetch(AUTH_API.login, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          credentials: 'same-origin',
          body: JSON.stringify({
            email: document.getElementById('login-email').value.trim(),
            password: document.getElementById('login-password').value
          })
        });

        const data = await res.json();
        if (data.success) {
          showMessage('login-message', data.message, 'success');
          setTimeout(() => {
            showProfileView(data.user);
            checkAuthStatus(); // Nav'ı güncelle
          }, 800);
        } else {
          showMessage('login-message', data.message, 'error');
        }
      } catch (err) {
        showMessage('login-message', 'Sunucuya bağlanılamadı.', 'error');
      }

      btn.disabled = false;
      btn.textContent = originalText;
    });
  }

  // Kayıt formu
  const registerForm = document.getElementById('register-form');
  if (registerForm) {
    registerForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const pw = document.getElementById('register-password').value;
      const pw2 = document.getElementById('register-password-confirm').value;

      if (pw !== pw2) {
        showMessage('register-message', 'Şifreler eşleşmiyor.', 'error');
        return;
      }

      const btn = registerForm.querySelector('button[type="submit"]');
      const originalText = btn.textContent;
      btn.disabled = true;
      btn.textContent = 'Hesap oluşturuluyor...';

      try {
        const res = await fetch(AUTH_API.register, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          credentials: 'same-origin',
          body: JSON.stringify({
            fullName: document.getElementById('register-fullname').value.trim(),
            email: document.getElementById('register-email').value.trim(),
            password: pw
          })
        });

        const data = await res.json();
        if (data.success) {
          showMessage('register-message', data.message, 'success');
          setTimeout(() => {
            showProfileView(data.user);
            checkAuthStatus();
          }, 800);
        } else {
          showMessage('register-message', data.message, 'error');
        }
      } catch (err) {
        showMessage('register-message', 'Sunucuya bağlanılamadı.', 'error');
      }

      btn.disabled = false;
      btn.textContent = originalText;
    });
  }

  // Şifremi unuttum formu
  const forgotForm = document.getElementById('forgot-form');
  if (forgotForm) {
    forgotForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const btn = forgotForm.querySelector('button[type="submit"]');
      const originalText = btn.textContent;
      btn.disabled = true;
      btn.textContent = 'Gönderiliyor...';

      try {
        const res = await fetch(AUTH_API.forgotPassword, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          credentials: 'same-origin',
          body: JSON.stringify({
            email: document.getElementById('forgot-email').value.trim()
          })
        });

        const data = await res.json();
        showMessage('forgot-message', data.message, data.success ? 'success' : 'error');
      } catch (err) {
        showMessage('forgot-message', 'Sunucuya bağlanılamadı.', 'error');
      }

      btn.disabled = false;
      btn.textContent = originalText;
    });
  }

  // Şifre sıfırlama formu
  const resetForm = document.getElementById('reset-form');
  if (resetForm) {
    const params = new URLSearchParams(window.location.search);
    const tokenInput = document.getElementById('reset-token');
    const emailInput = document.getElementById('reset-email');
    if (tokenInput) tokenInput.value = params.get('token') || '';
    if (emailInput) emailInput.value = params.get('email') || '';

    resetForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const pw = document.getElementById('reset-password').value;
      const pw2 = document.getElementById('reset-password-confirm').value;

      if (pw !== pw2) {
        showMessage('reset-message', 'Şifreler eşleşmiyor.', 'error');
        return;
      }

      const btn = resetForm.querySelector('button[type="submit"]');
      const originalText = btn.textContent;
      btn.disabled = true;
      btn.textContent = 'Güncelleniyor...';

      try {
        const res = await fetch(AUTH_API.resetPassword, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          credentials: 'same-origin',
          body: JSON.stringify({
            email: document.getElementById('reset-email').value.trim(),
            token: document.getElementById('reset-token').value.trim(),
            password: pw
          })
        });

        const data = await res.json();
        if (data.success) {
          showMessage('reset-message', data.message, 'success');
          setTimeout(() => showView('login'), 2000);
        } else {
          showMessage('reset-message', data.message, 'error');
        }
      } catch (err) {
        showMessage('reset-message', 'Sunucuya bağlanılamadı.', 'error');
      }

      btn.disabled = false;
      btn.textContent = originalText;
    });
  }
}

/**
 * Profil view'ını göster (giriş yapıldığında)
 */
function showProfileView(user) {
  hideAllViews();
  const profileView = document.getElementById('profile-view');
  if (!profileView) return;

  document.getElementById('profile-name').textContent = user.fullName;
  document.getElementById('profile-email').textContent = user.email;
  profileView.style.display = 'block';

  // Çıkış butonu
  const logoutBtn = document.getElementById('logout-btn');
  if (logoutBtn) {
    logoutBtn.onclick = async () => {
      try {
        await fetch(AUTH_API.logout, {
          method: 'POST',
          credentials: 'same-origin'
        });
      } catch (e) {}
      window.location.reload();
    };
  }
}

/**
 * Belirtilen view'ı göster
 */
function showView(viewName) {
  hideAllViews();
  const view = document.getElementById(viewName + '-view');
  if (view) view.style.display = 'block';

  // Tab butonlarını güncelle
  document.querySelectorAll('[data-auth-tab]').forEach(btn => {
    btn.classList.toggle('active', btn.dataset.authTab === viewName);
  });
}

function hideAllViews() {
  document.querySelectorAll('.auth-view').forEach(v => v.style.display = 'none');
}

function showMessage(elementId, message, type) {
  const el = document.getElementById(elementId);
  if (!el) return;
  el.textContent = message;
  el.className = 'auth-message ' + (type === 'success' ? 'auth-message-success' : 'auth-message-error');
  el.style.display = 'block';
}
