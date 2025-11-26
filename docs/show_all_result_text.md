# Dokumentasi: Cara Mengambil dan Menampilkan Semua Result Text

## Overview

Dokumen ini menjelaskan bagaimana sistem mengambil dan menampilkan semua `result_text` pada kartu setup hasil di halaman tab pertanyaan.

## Alur Data

### 1. Backend: Persiapan Data (FormController.php)

#### 1.1 Query Data dari Database

```php
// File: app/Http/Controllers/FormController.php (baris 899-904)
$resultRulesCollection = $form->resultRules()
    ->with(['texts' => function ($textQuery) {
        $textQuery->orderBy('order')->with('textSetting');
    }])
    ->orderBy('order')
    ->get();
```

**Penjelasan:**

-   Mengambil semua `result_rules` yang terkait dengan form
-   Load relasi `texts` (result_rule_texts) dengan urutan berdasarkan `order`
-   Load relasi `textSetting` (setting_results) untuk setiap text
-   Diurutkan berdasarkan `order` dari result_rules

#### 1.2 Mapping Data untuk Frontend

```php
// File: app/Http/Controllers/FormController.php (baris 906-936)
$resultRulesData = $resultRulesCollection
    ->map(function ($rule) {
        // Get texts with their settings (title, image)
        $textsWithSettings = $rule->texts
            ->sortBy('order')
            ->map(function ($text) {
                $textSetting = $text->textSetting;
                return [
                    'id' => $text->id,                    // result_rule_text_id
                    'result_text' => $text->result_text,  // Teks hasil
                    'title' => $textSetting ? $textSetting->title : null,
                    'image' => $textSetting ? $textSetting->image : null,
                    'image_url' => $textSetting && $textSetting->image ? asset($textSetting->image) : null,
                    'text_alignment' => $textSetting ? $textSetting->text_alignment : 'center',
                    'image_alignment' => $textSetting ? $textSetting->image_alignment : 'center',
                ];
            })
            ->toArray();

        return [
            'id' => $rule->id,                    // result_rule_id
            'condition_type' => $rule->condition_type,
            'min_score' => $rule->min_score,
            'max_score' => $rule->max_score,
            'single_score' => $rule->single_score,
            'rule_group_id' => $rule->rule_group_id,
            'texts' => $textsWithSettings,        // Array of texts dengan settings
        ];
    })
    ->values()
    ->toArray();
```

**Struktur Data yang Dikirim:**

```json
{
  "result_rules": [
    {
      "id": 1,                    // result_rule_id
      "rule_group_id": "uuid-123",
      "texts": [
        {
          "id": 10,               // result_rule_text_id
          "result_text": "Text 1",
          "title": "Judul 1",
          "image": "path/to/image.jpg",
          "image_url": "http://...",
          "text_alignment": "center",
          "image_alignment": "center"
        },
        {
          "id": 11,               // result_rule_text_id
          "result_text": "Text 2",
          "title": null,
          "image": null,
          ...
        }
      ]
    },
    {
      "id": 2,                    // result_rule_id berbeda
      "rule_group_id": "uuid-123", // SAMA dengan rule sebelumnya
      "texts": [
        {
          "id": 12,               // result_rule_text_id
          "result_text": "Text 3",
          ...
        }
      ]
    }
  ],
  "rule_groups": {
    "uuid-123": "Judul Rule Group"
  }
}
```

#### 1.3 Grouping by rule_group_id

```php
// File: app/Http/Controllers/FormController.php (baris 938-957)
$ruleGroupTextSettings = $resultRulesCollection
    ->groupBy('rule_group_id')
    ->map(function ($rules) {
        return $rules->flatMap(function ($rule) {
            return $rule->texts
                ->sortBy('order')
                ->map(function ($text) {
                    $textSetting = $text->textSetting;
                    return [
                        'result_rule_text_id' => $text->id,
                        'result_text' => $text->result_text,
                        'title' => $textSetting ? $textSetting->title : null,
                        'image' => $textSetting ? $textSetting->image : null,
                        'image_url' => $textSetting && $textSetting->image ? asset($textSetting->image) : null,
                        'text_alignment' => $textSetting ? $textSetting->text_alignment : 'center',
                        'image_alignment' => $textSetting ? $textSetting->image_alignment : 'center',
                    ];
                });
        })->values()->toArray();
    });
```

**Catatan Penting:**

-   Data dikelompokkan berdasarkan `rule_group_id`
-   Setiap `rule_group_id` berisi array flat dari semua `result_text` dari semua `result_rules` dalam group tersebut
-   **TIDAK ada informasi `result_rule_id` di sini** - hanya `result_rule_text_id`

---

### 2. Frontend: Mengambil Data (form-builder.js)

#### 2.1 Fungsi: `getRuleTextsFromSettings()`

```javascript
// File: resources/js/form-builder.js (baris 1321-1440)
const getRuleTextsFromSettings = () => {
    const selectedOption = ruleSelect?.selectedOptions[0];
    const ruleGroupId = selectedOption.getAttribute("data-rule-group-id");
    const ruleType = selectedOption.getAttribute("data-rule-type");

    // ... logic untuk mengambil data berdasarkan ruleType
};
```

**Sumber Data:**

##### A. Dari Active Rules (ruleType === 'active')

```javascript
// Baris 1347-1383
if (ruleType === "active") {
    const resultRulesContainer = document.getElementById(
        "result-rules-container"
    );
    const ruleCards = Array.from(
        resultRulesContainer.querySelectorAll(".result-rule-card")
    );
    const matchingRules = ruleCards.filter(
        (card) => card.getAttribute("data-rule-group-id") === ruleGroupId
    );

    // Collect all texts from all matching rules
    const allTexts = [];
    matchingRules.forEach((ruleCard, ruleIndex) => {
        const textareas = Array.from(
            ruleCard.querySelectorAll(".rule-result-text")
        );
        textareas.forEach((textarea, textIndex) => {
            const text = textarea.value.trim();
            if (text) {
                allTexts.push({
                    result_rule_text_id: `temp-${ruleGroupId}-${ruleIndex}-${textIndex}`, // Temporary ID
                    result_text: text,
                    title: null,
                    image: null,
                });
            }
        });
    });

    return allTexts;
}
```

**Catatan:**

-   Mengambil dari DOM (textarea di halaman builder)
-   Menggunakan temporary ID karena belum tersimpan ke database
-   **TIDAK ada informasi `result_rule_id`** di sini

##### B. Dari Saved Rules (ruleType === 'saved')

```javascript
// Baris 1387-1434
if (ruleType === 'saved') {
    const savedRules = loadSavedRules();
    const matchingRule = savedRules.find(r => {
        const normalized = normalizeSavedRule(r);
        return normalized.rule_group_id === ruleGroupId;
    });

    if (matchingRule) {
        const normalized = normalizeSavedRule(matchingRule);
        if (normalized.result_rules && Array.isArray(normalized.result_rules)) {
            const allTexts = [];
            normalized.result_rules.forEach((rule, ruleIndex) => {
                if (Array.isArray(rule.texts)) {
                    rule.texts.forEach((text, textIndex) => {
                        const textValue = typeof text === 'string' ? text : ...;
                        if (textValue) {
                            allTexts.push({
                                result_rule_text_id: text.id || `saved-${ruleGroupId}-${ruleIndex}-${textIndex}`,
                                result_text: textValue,
                                title: text.title || null,
                                image: text.image || text.image_url || null,
                            });
                        }
                    });
                }
            });
            return allTexts;
        }
    }
}
```

**Catatan:**

-   Mengambil dari saved rules (data yang sudah disimpan)
-   Loop melalui `result_rules` kemudian `texts` di dalamnya
-   **Ada informasi `ruleIndex`** yang menunjukkan `result_rule_id` secara tidak langsung

##### C. Fallback: Dari Lookup Table

```javascript
// Baris 1437-1439
const fallback = getRuleGroupTextFromLookup(ruleGroupId);
return fallback;
```

**Catatan:**

-   Menggunakan lookup table yang sudah di-build sebelumnya
-   Biasanya dari data initial form yang sudah di-load

---

### 3. Frontend: Menampilkan Data (form-builder.js)

#### 3.1 Fungsi: `setResultSettingTextValues()`

```javascript
// File: resources/js/form-builder.js (baris 69-171)
function setResultSettingTextValues(card, textData = []) {
    const display = card.querySelector(".result-setting-text-display");

    // textData should be array of objects:
    // [{result_rule_text_id, result_text, title, image, image_url}]
    const sanitized = Array.isArray(textData)
        ? textData.filter((item) => item && item.result_text)
        : [];

    if (!sanitized.length) {
        display.innerHTML =
            '<p class="text-sm text-gray-400 italic">Pilih aturan untuk melihat teks hasil.</p>';
    } else {
        // Display each text form in a separate container
        display.innerHTML = sanitized
            .map((item, index) => {
                const resultRuleTextId =
                    item.result_rule_text_id || item.id || `text-${index}`;
                const title = item.title || "";
                const image = item.image || item.image_url || "";
                const resultText = item.result_text ?? item.text ?? "";

                return `
                    <div class="result-text-form border border-gray-200 rounded-lg p-4 bg-white ${
                        index > 0 ? "mt-4" : ""
                    }" 
                         data-result-rule-text-id="${resultRuleTextId}">
                        <!-- Title Input -->
                        <div class="mb-3">
                            <label>Judul (opsional)</label>
                            <input type="text" class="result-text-form-title" value="${title}">
                        </div>
                        
                        <!-- Image Upload -->
                        <div class="mb-3">
                            <label>Gambar (opsional)</label>
                            <!-- Image display and upload UI -->
                        </div>
                        
                        <!-- Result Text (ReadOnly) -->
                        <div>
                            <label>Teks Hasil</label>
                            <textarea readonly class="result-text-form-text">${resultText}</textarea>
                        </div>
                    </div>
                `;
            })
            .join("");
    }
}
```

**Struktur HTML yang Dihasilkan:**

```html
<div class="result-setting-text-display">
    <!-- Form 1 -->
    <div class="result-text-form" data-result-rule-text-id="10">
        <!-- Title, Image, Text -->
    </div>

    <!-- Form 2 -->
    <div class="result-text-form" data-result-rule-text-id="11">
        <!-- Title, Image, Text -->
    </div>

    <!-- Form 3 -->
    <div class="result-text-form" data-result-rule-text-id="12">
        <!-- Title, Image, Text -->
    </div>
</div>
```

---

## Masalah Saat Ini

### 1. Tidak Ada Informasi `result_rule_id` di Data yang Ditampilkan

**Masalah:**

-   Setiap `result_text` ditampilkan dalam form terpisah
-   Tidak ada cara untuk mengetahui bahwa beberapa `result_text` memiliki `result_rule_id` yang sama
-   Data yang dikirim ke frontend tidak selalu menyertakan `result_rule_id`

**Contoh:**

```
Rule Group: uuid-123
├─ Result Rule 1 (id: 1)
│  ├─ Result Text A (id: 10, result_rule_id: 1)
│  └─ Result Text B (id: 11, result_rule_id: 1)
└─ Result Rule 2 (id: 2)
   └─ Result Text C (id: 12, result_rule_id: 2)
```

**Tampilan Saat Ini:**

```
[Form Text A]  ← Tidak tahu bahwa ini dari result_rule_id: 1
[Form Text B]  ← Tidak tahu bahwa ini dari result_rule_id: 1
[Form Text C]  ← Tidak tahu bahwa ini dari result_rule_id: 2
```

**Yang Diinginkan:**

```
[Container: Result Rule 1]
  ├─ [Form Text A]
  └─ [Form Text B]

[Container: Result Rule 2]
  └─ [Form Text C]
```

---

## Solusi yang Diperlukan

### 1. Modifikasi Backend: Tambahkan `result_rule_id` ke Data

**Di FormController.php:**

```php
// Saat mapping texts, tambahkan result_rule_id
$textsWithSettings = $rule->texts
    ->sortBy('order')
    ->map(function ($text) use ($rule) {  // ← Tambahkan $rule
        $textSetting = $text->textSetting;
        return [
            'id' => $text->id,
            'result_rule_id' => $rule->id,  // ← TAMBAHKAN INI
            'result_text' => $text->result_text,
            'title' => $textSetting ? $textSetting->title : null,
            'image' => $textSetting ? $textSetting->image : null,
            'image_url' => $textSetting && $textSetting->image ? asset($textSetting->image) : null,
            'text_alignment' => $textSetting ? $textSetting->text_alignment : 'center',
            'image_alignment' => $textSetting ? $textSetting->image_alignment : 'center',
        ];
    })
    ->toArray();
```

### 2. Modifikasi Frontend: Grouping berdasarkan `result_rule_id`

**Di form-builder.js:**

```javascript
function setResultSettingTextValues(card, textData = []) {
    // Group texts by result_rule_id
    const groupedByRuleId = textData.reduce((acc, item) => {
        const ruleId = item.result_rule_id || "unknown";
        if (!acc[ruleId]) {
            acc[ruleId] = [];
        }
        acc[ruleId].push(item);
        return acc;
    }, {});

    // Render grouped containers
    display.innerHTML = Object.entries(groupedByRuleId)
        .map(([ruleId, texts]) => {
            return `
                <div class="result-rule-container border-2 border-blue-200 rounded-lg p-4 mb-4 bg-blue-50" 
                     data-result-rule-id="${ruleId}">
                    <div class="text-xs font-semibold text-blue-700 mb-3">
                        Aturan ${ruleId}
                    </div>
                    ${texts
                        .map((item, index) => {
                            // Render individual form seperti sebelumnya
                        })
                        .join("")}
                </div>
            `;
        })
        .join("");
}
```

---

## Kesimpulan

1. **Data Flow:**

    - Backend: `result_rules` → `result_rule_texts` → dikirim ke frontend
    - Frontend: Ambil dari DOM/saved rules → tampilkan dalam form terpisah

2. **Masalah:**

    - Tidak ada informasi `result_rule_id` di data yang ditampilkan
    - Tidak bisa membedakan `result_text` yang memiliki `result_rule_id` sama

3. **Solusi:**
    - Tambahkan `result_rule_id` ke data yang dikirim backend
    - Grouping di frontend berdasarkan `result_rule_id`
    - Tampilkan dalam container terpisah untuk setiap `result_rule_id`
