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
  if (!weightInput) return;
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

  const lifeStageSelect = document.getElementById('calc-life-stage');
  const feedingStatusSelect = document.getElementById('calc-feeding-status');
  const productKeySelect = document.getElementById('calc-product');

  const lifeStage = lifeStageSelect ? lifeStageSelect.value : 'yetiskin';
  const feedingStatus = feedingStatusSelect ? feedingStatusSelect.value : 'sabit';
  const productKey = productKeySelect ? productKeySelect.value : 'kedi_hindi_balik';

  // 2. Parça Katsayısı Belirleme Mantığı (Rawlabs Porsiyon Tablosu)
  let pieceFactorPerKg = 3.0;

  if (lifeStage === 'yavru_kucuk') {
    // 2–6 ay yavru
    pieceFactorPerKg = 8.4;
  } else if (lifeStage === 'yavru_buyuk') {
    // 6–11 ay yavru
    pieceFactorPerKg = 6.0;
  } else {
    // Yetişkin / yaşlı -> Beslenme durumuna göre katsayı
    if (feedingStatus === 'sabit') pieceFactorPerKg = 3.0;
    else if (feedingStatus === 'alma') pieceFactorPerKg = 3.5;
    else if (feedingStatus === 'hareketsiz') pieceFactorPerKg = 2.4;
    else if (feedingStatus === 'hareketli') pieceFactorPerKg = 3.5;
    else if (feedingStatus === 'verme') pieceFactorPerKg = 2.0;
  }

  // 3. Değerlerin Hesaplanması
  // Ana formül: Günlük Parça Sayısı = Vücut Ağırlığı × Duruma Göre Parça Katsayısı
  const dailyPieces = weight * pieceFactorPerKg;
  
  // Günlük gram: Günlük Gram = Günlük Parça Sayısı × 2
  const dailyGrams = dailyPieces * 2;

  const config = CALCULATOR_CONFIG[productKey] || CALCULATOR_CONFIG.treat40g;
  const isTreat = productKey === 'treat40g';

  // Aylık paket ihtiyacı: Günlük Gram × 30 / Paket Gramajı
  const monthlyPackage = (dailyGrams * 30) / config.packageGram;

  // Yaklaşık Kalori Karşılığı (İkincil Bilgi)
  const dailyCal = dailyGrams * config.kcalPerGram;

  // 4. Ekran Çıktılarının Hazırlanması
  const formatTr = (num, maxDecimals = 2) => num.toLocaleString('tr-TR', {maximumFractionDigits: maxDecimals});

  const outPieceEl = document.getElementById('out-piece');
  if (outPieceEl) outPieceEl.textContent = `${formatTr(dailyPieces)} parça / gün`;
  
  const outGramEl = document.getElementById('out-gram');
  if (outGramEl) outGramEl.textContent = `${formatTr(dailyGrams)} g / gün`;
  
  const outPackageEl = document.getElementById('out-monthly-package');
  if (outPackageEl) {
    if (isTreat) {
      outPackageEl.textContent = `${formatTr(monthlyPackage)} paket / ay (Kontrollü Destek)`;
    } else {
      outPackageEl.textContent = `${formatTr(monthlyPackage)} paket / ay`;
    }
  }
  
  const outCalEl = document.getElementById('out-cal');
  if (outCalEl) outCalEl.textContent = `${Math.round(dailyCal)} kcal / gün`;

  // Uyarılar ve Destekleyici Bilgiler
  const noticeContainer = document.getElementById('calc-notices');
  if (noticeContainer) {
    let noticesHtml = `
      <p style="font-size:0.85rem; color:var(--text-light); line-height:1.5; margin-top:16px;">
        ℹ️ Bu hesaplama Rawlabs başlangıç porsiyon tablosuna göre hazırlanmıştır. Evcil dostunuzun vücut kondisyonu, aktivitesi ve özel ihtiyaçlarına göre porsiyon zamanla ayarlanabilir.
      </p>
    `;

    if (isTreat) {
      noticesHtml += `
        <div style="background:#fff5f5; border:1px solid #feb2b2; padding:12px; border-radius:var(--radius-sm); margin-top:12px; font-size:0.85rem; color:#c53030; text-align:left;">
          ⚠️ <strong>Önemli Not:</strong> Ödül mamaları günlük ana beslenmenin yerine geçmez; kontrollü miktarda destek olarak kullanılmalıdır.
        </div>
      `;
    }

    noticeContainer.innerHTML = noticesHtml;
  }

  // Paneli görünür yapalım
  const emptyPanel = document.getElementById('calc-empty');
  if (emptyPanel) emptyPanel.style.display = 'none';
  
  const contentPanel = document.getElementById('calc-result-content');
  if (contentPanel) contentPanel.style.display = 'block';

  // Sonuç başlığını dinamik güncelleyelim
  const titlePet = calcPetType === 'kedi' ? '🐱 Kedi' : '🐶 Köpek';
  const selectedLifeStageText = lifeStageSelect && lifeStageSelect.selectedOptions[0] ? lifeStageSelect.selectedOptions[0].text : '';
  const subtitleEl = document.getElementById('calc-result-subtitle');
  if (subtitleEl) {
    subtitleEl.textContent = `${titlePet} · ${weight} kg · ${selectedLifeStageText} · ${config.label}`;
  }
}
