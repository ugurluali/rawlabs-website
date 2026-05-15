/**
 * Rawlabs - Siparişlerim ve Hesap Yönetimi Frontend
 * hesabim.html yüklendiğinde siparişleri çeker ve ekrana basar.
 */

document.addEventListener('rawlabs:auth-ready', loadMyOrders);
document.addEventListener('rawlabs:auth-changed', loadMyOrders);

async function loadMyOrders() {
  const container = document.getElementById('orders-container');
  if (!container) return;

  // Başlangıçta nötr yükleme mesajı göster
  container.innerHTML = '<div style="text-align:center; padding: 20px; color: var(--text-light);">Siparişleriniz kontrol ediliyor...</div>';

  try {
    const res = await fetch('api/auth-my-orders.php', {
      method: 'GET',
      credentials: 'same-origin'
    });

    if (res.status === 401) {
      // Giriş yapmamış
      container.innerHTML = '<div style="text-align:center; padding: 20px; color: var(--text-light);">Lütfen siparişlerinizi görmek için giriş yapın.</div>';
      return;
    }

    const data = await res.json();
    
    if (data.success) {
      const orders = data.orders || [];
      if (orders.length === 0) {
        container.innerHTML = '<div style="text-align:center; padding: 20px; color: var(--text-light);">Henüz siparişiniz bulunmuyor.</div>';
      } else {
        renderOrders(orders, container);
      }
    } else {
      container.innerHTML = `<div style="text-align:center; padding: 20px; color: var(--error);">${data.message || 'Siparişler yüklenemedi.'}</div>`;
    }
  } catch (err) {
    container.innerHTML = '<div style="text-align:center; padding: 20px; color: var(--error);">Sunucu bağlantı hatası oluştu.</div>';
  }
}

function renderOrders(orders, container) {
  let html = '<div style="overflow-x: auto;"><table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem;">';
  html += `
    <thead>
      <tr style="border-bottom: 1px solid var(--border); color: var(--text-light);">
        <th style="padding: 12px 8px; font-weight: 600;">Sipariş No</th>
        <th style="padding: 12px 8px; font-weight: 600;">Tarih</th>
        <th style="padding: 12px 8px; font-weight: 600;">Durum</th>
        <th style="padding: 12px 8px; font-weight: 600;">Tutar</th>
      </tr>
    </thead>
    <tbody>
  `;

  orders.forEach(order => {
    const date = new Date(order.createdAt).toLocaleDateString('tr-TR', { day: '2-digit', month: 'short', year: 'numeric' });
    let statusBadge = '';
    
    // Basit durum çevirileri
    if (order.status === 'created') {
      statusBadge = '<span style="background: #fff3cd; color: #856404; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem;">Ödeme Bekliyor</span>';
    } else if (order.status === 'paid_test_success' || order.status === 'paid') {
      statusBadge = '<span style="background: #d4edda; color: #155724; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem;">Onaylandı</span>';
    } else if (order.status === 'shipped') {
      statusBadge = '<span style="background: #cce5ff; color: #004085; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem;">Kargoya Verildi</span>';
    } else {
      statusBadge = `<span style="background: #e2e3e5; color: #383d41; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem;">${escapeHTML(order.status)}</span>`;
    }

    const price = new Intl.NumberFormat('tr-TR', { style: 'currency', currency: order.currency }).format(order.grandTotal);

    html += `
      <tr style="border-bottom: 1px solid var(--border);">
        <td style="padding: 16px 8px; font-weight: 500; color: var(--secondary-dark);">${escapeHTML(order.orderId)}
          <div style="font-size: 0.8rem; color: var(--text-light); margin-top: 4px;">${escapeHTML(order.itemSummary || '')}</div>
        </td>
        <td style="padding: 16px 8px; color: var(--text-light);">${date}</td>
        <td style="padding: 16px 8px;">${statusBadge}</td>
        <td style="padding: 16px 8px; font-weight: 600;">${price}</td>
      </tr>
    `;
  });

  html += '</tbody></table></div>';
  container.innerHTML = html;
}

function escapeHTML(str) {
  if (!str) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}
