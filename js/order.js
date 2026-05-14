/**
 * Rawlabs - Sipariş Oluşturma ve Veri Yönetimi Altyapısı
 * Sadece sepet.html sayfasında çağrılmak üzere modüler olarak tasarlanmıştır.
 */

function createOrder() {
  // 1. Sepet kontrolü
  const cart = typeof getCart === 'function' ? getCart() : [];
  if (!cart || cart.length === 0) {
    alert("Sepetiniz boş. Lütfen sipariş oluşturmadan önce sepetinize ürün ekleyin.");
    return;
  }

  // 2. Form Alanları ve Validasyon
  const fullNameEl = document.getElementById('order-fullname');
  const phoneEl = document.getElementById('order-phone');
  const emailEl = document.getElementById('order-email');
  const cityEl = document.getElementById('order-city');
  const districtEl = document.getElementById('order-district');
  const addressEl = document.getElementById('order-address');
  const noteEl = document.getElementById('order-note');

  const fullName = fullNameEl ? fullNameEl.value.trim() : '';
  const phone = phoneEl ? phoneEl.value.trim() : '';
  const email = emailEl ? emailEl.value.trim() : '';
  const city = cityEl ? cityEl.value.trim() : '';
  const district = districtEl ? districtEl.value.trim() : '';
  const address = addressEl ? addressEl.value.trim() : '';
  const note = noteEl ? noteEl.value.trim() : '';

  // Eksik alan kontrolü
  const missingFields = [];
  if (!fullName) missingFields.push("Ad Soyad");
  if (!phone) missingFields.push("Telefon");
  if (!email) missingFields.push("E-posta");
  if (!city) missingFields.push("İl");
  if (!district) missingFields.push("İlçe");
  if (!address) missingFields.push("Açık Adres");

  if (missingFields.length > 0) {
    alert(`Lütfen teslimat için zorunlu alanları doldurunuz:\n\n• ${missingFields.join('\n• ')}`);
    return;
  }

  // E-posta formatı basit kontrol
  if (!email.includes('@') || !email.includes('.')) {
    alert("Lütfen geçerli bir e-posta adresi giriniz.");
    return;
  }

  // 3. Sepetteki Ürünleri ve Hesaplamaları Hazırlama
  const orderItems = [];
  let totalAmount = 0;
  let subtotalAmount = 0;

  cart.forEach(item => {
    const product = typeof getProductBySlug === 'function' ? getProductBySlug(item.slug) : null;
    if (product) {
      const unitPrice = product.salePrice ? product.salePrice : product.price;
      const lineTotal = unitPrice * item.quantity;
      
      orderItems.push({
        slug: product.slug,
        name: product.name,
        quantity: item.quantity,
        unitPrice: unitPrice,
        lineTotal: lineTotal,
        weight: product.weight
      });

      totalAmount += lineTotal;
      subtotalAmount += product.price * item.quantity;
    }
  });

  const FREE_SHIPPING_THRESHOLD = 3000;
  const SHIPPING_COST = 300;
  const shippingFee = totalAmount >= FREE_SHIPPING_THRESHOLD ? 0 : SHIPPING_COST;
  const grandTotal = totalAmount + shippingFee;
  const discountAmount = subtotalAmount - totalAmount;

  // 4. Sipariş Veri Yapısını (JSON Payload) Oluşturma
  // NOT: Sipariş numarası güvenlik gereği artık Backend'de (PHP) üretilmektedir.
  const orderPayload = {
    customer: {
      fullName: fullName,
      phone: phone,
      email: email,
      city: city,
      district: district,
      address: address,
      note: note
    },
    items: orderItems,
    summary: {
      subtotal: subtotalAmount,
      discount: discountAmount,
      shippingFee: shippingFee,
      grandTotal: grandTotal,
      currency: "TRY"
    }
  };

  // 5. Backend'e Fetch API ile gönderme ve Yönlendirme
  const btn = document.querySelector('#rawlabs-order-form button[type="submit"]');
  const originalBtnText = btn ? btn.innerHTML : '';
  if (btn) {
    btn.disabled = true;
    btn.innerHTML = '🔄 İşleniyor...';
  }

  fetch('api/create-order.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(orderPayload)
  })
  .then(response => response.json())
  .then(data => {
    if (data.success && data.paymentUrl) {
      console.log("📦 [RAWLABS SİPARİŞ DATASI BACKEND'E İLETİLDİ]");
      // Başarılıysa güvenlikli ödeme ekranına yönlendir
      window.location.href = data.paymentUrl;
    } else {
      alert("Sipariş oluşturulamadı: " + (data.message || "Bilinmeyen sunucu hatası."));
      if (btn) {
        btn.disabled = false;
        btn.innerHTML = originalBtnText;
      }
    }
  })
  .catch(err => {
    console.error("Fetch API Hatası:", err);
    alert("Sunucuya bağlanılamadı veya sistemde bir hata oluştu (Örn: config.php yok). Lütfen bağlantınızı kontrol edip tekrar deneyin.");
    if (btn) {
      btn.disabled = false;
      btn.innerHTML = originalBtnText;
    }
  });
}

if (typeof window !== 'undefined') {
  window.createOrder = createOrder;
}
