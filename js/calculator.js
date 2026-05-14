/**
 * Beslenme Asistanı - Mama Miktarı ve Porsiyon Hesaplama Algoritması
 * Sadece mama-onerisi.html içinde çağrılmak üzere izole edilmiştir.
 */

// Tam mama kcal/g değerleri ürün etiketlerindeki metabolik enerji kcal/kg bilgilerinden hesaplanmıştır. Ortalama parça ağırlığı 2g kabul edilmiştir.
const CALCULATOR_CONFIG = {
  // Köpek Tam Mamaları
  kopek_dana_balik: {
    label: "Dana & Balık Köpek Maması 300g",
    kcalPerGram: 4.850,
    averagePieceGram: 2,
    packageGram: 300,
    petType: "kopek"
  },
  kopek_hindi_balik: {
    label: "Hindi & Balık Köpek Maması 300g",
    kcalPerGram: 4.158,
    averagePieceGram: 2,
    packageGram: 300,
    petType: "kopek"
  },
  kopek_tavuk_balik: {
    label: "Tavuk & Balık Köpek Maması 300g",
    kcalPerGram: 4.932,
    averagePieceGram: 2,
    packageGram: 300,
    petType: "kopek"
  },
  // Kedi Tam Mamaları
  kedi_hindi_balik: {
    label: "Hindi & Balık Kedi Maması 300g",
    kcalPerGram: 4.613,
    averagePieceGram: 2,
    packageGram: 300,
    petType: "kedi"
  },
  kedi_tavuk_balik: {
    label: "Tavuk & Balık Kedi Maması 300g",
    kcalPerGram: 4.769,
    averagePieceGram: 2,
    packageGram: 300,
    petType: "kedi"
  },
  // Ödül Maması (Fallback)
  treat40g: {
    label: "Ödül Maması 40g",
    kcalPerGram: 4.5,
    averagePieceGram: 1.2,
    packageGram: 40,
    petType: "all"
  }
};

let calcPetType = 'kedi';

function updateCalcProductDropdown() {
  const select = document.getElementById('calc-product');
  if (!select) return;

  select.innerHTML = '';

  for (const key in CALCULATOR_CONFIG) {
    const item = CALCULATOR_CONFIG[key];
    if (item.petType === calcPetType || item.petType === 'all') {
      const opt = document.createElement('option');
      opt.value = key;
      opt.textContent = item.label;
      select.appendChild(opt);
    }
  }
}

function setCalcPetType(type, el) {
  calcPetType = type;
  const container = el.closest('.pet-type-toggle');
  if (container) {
    container.querySelectorAll('.pet-type-btn').forEach(b => b.classList.remove('active'));
    el.classList.add('active');
  }
  updateCalcProductDropdown();
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
  const config = CALCULATOR_CONFIG[productKey] || CALCULATOR_CONFIG.treat40g;

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
      ℹ️ Bu hesaplama genel bilgilendirme amaçlıdır. Başlangıç porsiyonunu temsil eder. Evcil dostunuzun yaşı, sağlık durumu, aktivitesi ve özel ihtiyaçlarına göre porsiyon değişebilir.
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
  document.getElementById('calc-result-subtitle').textContent = `${titlePet} · ${weight} kg · ${selectedAgeText} · ${config.label}`;
}
