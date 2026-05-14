/**
 * Beslenme Asistanı - Mama Miktarı ve Porsiyon Hesaplama Algoritması
 * Sadece mama-onerisi.html içinde çağrılmak üzere izole edilmiştir.
 */

// Bu değerler geçici hesaplama değerleridir. Ürün etiketlerindeki net kcal ve parça gramajı bilgileriyle güncellenmelidir.
const CALCULATOR_CONFIG = {
  completeFood300g: {
    label: "Tam Mama 300g",
    kcalPerGram: 4.2,
    averagePieceGram: 2.5,
    packageGram: 300
  },
  treat40g: {
    label: "Ödül Maması 40g",
    kcalPerGram: 4.5,
    averagePieceGram: 1.2,
    packageGram: 40
  }
};

let calcPetType = 'kedi';

function setCalcPetType(type, el) {
  calcPetType = type;
  const container = el.closest('.pet-type-toggle');
  if (container) {
    container.querySelectorAll('.pet-type-btn').forEach(b => b.classList.remove('active'));
    el.classList.add('active');
  }
}

function runCalculation() {
  const weightInput = document.getElementById('calc-weight');
  const weightVal = weightInput.value.trim();

  // 1. Validasyonlar
  if (!weightVal) {
    alert("Lütfen evcil dostunuzun kilosunu giriniz.");
    weightInput.focus();
    return;
  }

  const weight = parseFloat(weightVal);
  if (isNaN(weight) || weight <= 0) {
    alert("Lütfen geçerli, pozitif bir kilo değeri giriniz.");
    weightInput.focus();
    return;
  }

  if (weight < 0.5 || weight > 90) {
    // Aşırı düşük veya yüksek kilolarda kullanıcıyı uyaran ama işleme izin veren kontrol
    console.warn(`Sıra dışı kilo girişi tespit edildi: ${weight} kg`);
  }

  const age = document.getElementById('calc-age').value;
  const activity = document.getElementById('calc-activity').value;
  const neutered = document.getElementById('calc-neutered').value;
  const goal = document.getElementById('calc-goal').value;
  const productKey = document.getElementById('calc-product').value;

  // 2. RER Hesaplaması (Resting Energy Requirement)
  const rer = 70 * Math.pow(weight, 0.75);

  // 3. Katsayı Belirleme Mantığı
  let factor = 1.0;

  if (calcPetType === 'kedi') {
    if (age === 'yavru') factor = 2.2;
    else if (age === 'yetiskin') factor = 1.2;
    else if (age === 'yasli') factor = 1.0;

    if (neutered === 'evet') factor -= 0.2;

    if (activity === 'dusuk') factor -= 0.15;
    else if (activity === 'yuksek') factor += 0.25;

    if (goal === 'alma') factor += 0.20;
    else if (goal === 'verme') factor -= 0.20;
  } else {
    // Köpek
    if (age === 'yavru') factor = 2.0;
    else if (age === 'yetiskin') factor = 1.6;
    else if (age === 'yasli') factor = 1.3;

    if (neutered === 'evet') factor -= 0.2;

    if (activity === 'dusuk') factor -= 0.20;
    else if (activity === 'yuksek') factor += 0.30;

    if (goal === 'alma') factor += 0.25;
    else if (goal === 'verme') factor -= 0.25;
  }

  // Güvenlik: Nihai katsayı hiçbir zaman 0.8'in altına düşmesin
  factor = Math.max(0.8, factor);

  // 4. İhtiyaç Duyulan Değerlerin Hesaplanması
  const dailyCal = rer * factor;
  const config = CALCULATOR_CONFIG[productKey];

  const dailyGrams = dailyCal / config.kcalPerGram;
  const dailyPieces = dailyGrams / config.averagePieceGram;
  const durationDays = config.packageGram / dailyGrams;

  // 5. Ekran Çıktılarının Hazırlanması
  const minPieces = Math.max(1, Math.floor(dailyPieces * 0.88));
  const maxPieces = Math.ceil(dailyPieces * 1.12);

  document.getElementById('out-cal').textContent = `${Math.round(dailyCal)} kcal / gün`;
  document.getElementById('out-gram').textContent = `${Math.round(dailyGrams)} g / gün`;
  document.getElementById('out-piece').textContent = `${minPieces}–${maxPieces} parça / gün`;
  document.getElementById('out-duration').textContent = `${config.packageGram}g paket yaklaşık ${Math.max(1, Math.round(durationDays))} gün gider`;

  // Uyarılar ve Destekleyici Bilgiler
  const noticeContainer = document.getElementById('calc-notices');
  let noticesHtml = `
    <p style="font-size:0.85rem; color:var(--text-light); line-height:1.5; margin-top:16px;">
      ℹ️ Bu hesaplama genel bilgilendirme amaçlıdır. Evcil dostunuzun yaşı, sağlık durumu, aktivitesi ve özel ihtiyaçlarına göre porsiyon değişebilir.
    </p>
  `;

  if (productKey === 'treat40g') {
    noticesHtml += `
      <div style="background:#fff5f5; border:1px solid #feb2b2; padding:12px; border-radius:var(--radius-sm); margin-top:12px; font-size:0.85rem; color:#c53030; text-align:left;">
        ⚠️ <strong>Önemli Not:</strong> Ödül mamaları günlük ana beslenmenin yerine geçmez; kontrollü miktarda destek olarak kullanılmalıdır.
      </div>
    `;
  }

  noticeContainer.innerHTML = noticesHtml;

  // Paneli görünür yapalım
  document.getElementById('calc-empty').style.display = 'none';
  document.getElementById('calc-result-content').style.display = 'block';

  // Sonuç başlığını dinamik güncelleyelim
  const titlePet = calcPetType === 'kedi' ? '🐱 Kedi' : '🐶 Köpek';
  const selectedAgeText = document.getElementById('calc-age').selectedOptions[0].text;
  document.getElementById('calc-result-subtitle').textContent = `${titlePet} · ${weight} kg · ${selectedAgeText}`;
}
