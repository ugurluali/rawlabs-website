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

  // 3. Sipariş Numarası Üretimi (Tarih/Saat bazlı güvenli kısa format)
  // Örnek: RAW-20260515-XXXX
  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, '0');
  const day = String(now.getDate()).padStart(2, '0');
  const randomSuffix = Math.floor(1000 + Math.random() * 9000);
  const orderId = `RAW-${year}${month}${day}-${randomSuffix}`;

  // 4. Sepetteki Ürünleri ve Hesaplamaları Hazırlama
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

  // 5. Sipariş Veri Yapısını (JSON Payload) Oluşturma
  const orderPayload = {
    orderId: orderId,
    createdAt: now.toISOString(),
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
    },
    status: "DRAFT"
  };

  // 6. Console.log ile okunabilir JSON olarak gösterme
  console.log("📦 [RAWLABS SİPARİŞ DATASI OLUŞTURULDU]:");
  console.log(JSON.stringify(orderPayload, null, 2));

  // 7. Kullanıcıya geçici başarı mesajı gösterme
  const successBox = document.getElementById('order-success-message');
  if (successBox) {
    successBox.innerHTML = `
      <div style="background:#edf7ed; border:1px solid #c3e6cb; color:#155724; padding:24px; border-radius:12px; margin-top:24px; text-align:left; box-shadow:0 4px 12px rgba(0,0,0,0.05);">
        <h4 style="font-size:1.15rem; margin-bottom:8px; display:flex; align-items:center; gap:8px;">
          <span>✅</span> Sipariş Taslağı Başarıyla Oluşturuldu!
        </h4>
        <p style="margin-bottom:12px; font-size:0.95rem; color:#2d1e44;"><strong>Sipariş No:</strong> ${orderId}</p>
        <p style="font-size:0.9rem; line-height:1.6; color:#4a5568;">
          Sipariş taslağı oluşturuldu. Mail ve PDF entegrasyonu bir sonraki adımda eklenecektir.<br>
          Oluşturulan veri yapısını tarayıcınızın Geliştirici Konsolundan (F12 -&gt; Console) inceleyebilirsiniz.
        </p>
      </div>
    `;
    successBox.style.display = 'block';
    successBox.scrollIntoView({ behavior: 'smooth' });
  } else {
    alert(`✅ Sipariş taslağı oluşturuldu. Mail ve PDF entegrasyonu bir sonraki adımda eklenecektir.\n\nSipariş No: ${orderId}\n\n(Sipariş verisi Console üzerine yazdırılmıştır.)`);
  }
}

if (typeof window !== 'undefined') {
  window.createOrder = createOrder;
}
