# Security Notes: Result Rules & Rule Groups Authorization

## Masalah yang Ditemukan

### Struktur Database Saat Ini
- `result_rules` → hanya punya `form_id` (foreign key ke forms)
- `rule_groups` → hanya punya `form_id` (foreign key ke forms)
- `forms` → punya `user_id` (ownership)

### Relasi
```
result_rules → form → user
rule_groups → form → user
```

### Potensi Masalah
1. **Tidak ada relasi langsung ke `user_id`**
   - Jika validasi di controller di-bypass atau ada bug, user bisa akses data user lain
   - Hanya ada 1 layer validasi (di controller)
   - Tidak ada defense in depth

2. **Skenario Bug Potensial**
   - Jika ada bug di validasi `$form->user_id !== Auth::id()`, semua data bisa diakses
   - Jika ada query langsung ke `result_rules` atau `rule_groups` tanpa melalui `Form` model
   - Race condition atau timing attack

## Validasi yang Sudah Ada

### Di Controller (FormController.php)
- Setiap method cek: `if ($form->user_id !== Auth::id()) { abort(403); }`
- Query selalu melalui `$form->resultRules()` atau `$form->ruleGroups()` (scoped)
- Raw query selalu filter `where('form_id', $form->id)`

**Contoh validasi yang sudah ada:**
```php
// edit()
if ($form->user_id !== Auth::id()) {
    abort(403);
}

// update()
if ($form->user_id !== Auth::id()) {
    abort(403);
}

// updateRules()
if ($form->user_id !== Auth::id()) {
    abort(403);
}

// destroyResultRule()
if ($form->user_id !== Auth::id()) {
    abort(403);
}
```

## Solusi yang Disarankan

### Opsi 1: Tambah Global Scope di Model (RECOMMENDED)

**Keuntungan:**
- ✅ Tidak perlu ubah struktur database
- ✅ Defense in depth: validasi di controller + scope di model
- ✅ Jika ada bug di controller, scope tetap melindungi
- ✅ Best practice Laravel
- ✅ Tidak ada overhead besar

**Implementasi:**

#### 1. Tambah Global Scope di ResultRule Model
```php
// app/Models/ResultRule.php
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ResultRule extends Model
{
    // ... existing code ...

    protected static function booted()
    {
        // Global scope untuk filter berdasarkan user_id melalui form
        static::addGlobalScope('user', function (Builder $query) {
            if (Auth::check()) {
                $query->whereHas('form', function ($q) {
                    $q->where('user_id', Auth::id());
                });
            }
        });
    }
}
```

#### 2. Tambah Global Scope di RuleGroup Model
```php
// app/Models/RuleGroup.php
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class RuleGroup extends Model
{
    // ... existing code ...

    protected static function booted()
    {
        // Global scope untuk filter berdasarkan user_id melalui form
        static::addGlobalScope('user', function (Builder $query) {
            if (Auth::check()) {
                $query->whereHas('form', function ($q) {
                    $q->where('user_id', Auth::id());
                });
            }
        });
    }
}
```

#### 3. Untuk Query yang Perlu Bypass Scope (jika ada)
```php
// Jika perlu query tanpa scope (misalnya untuk admin atau testing)
ResultRule::withoutGlobalScope('user')->get();
```

**Catatan:**
- Scope hanya aktif jika user sudah login (`Auth::check()`)
- Untuk public routes (seperti form submission), scope tidak aktif karena tidak ada Auth::id()
- Scope ini akan otomatis filter semua query ke `result_rules` dan `rule_groups`

---

### Opsi 2: Tambah `user_id` Langsung ke Tabel

**Keuntungan:**
- ✅ Lebih eksplisit dan jelas
- ✅ Query lebih cepat (tidak perlu join)
- ✅ Bisa langsung filter tanpa melalui form

**Kekurangan:**
- ❌ Redundant dengan `form_id` (denormalisasi)
- ❌ Perlu migration dan backfill data
- ❌ Perlu update semua query yang insert/update
- ❌ Perlu maintain consistency (jika form.user_id berubah)

**Implementasi:**

#### 1. Migration
```php
// database/migrations/xxxx_add_user_id_to_result_rules_table.php
Schema::table('result_rules', function (Blueprint $table) {
    $table->foreignId('user_id')->after('form_id')->constrained()->onDelete('cascade');
    $table->index('user_id');
});

// database/migrations/xxxx_add_user_id_to_rule_groups_table.php
Schema::table('rule_groups', function (Blueprint $table) {
    $table->foreignId('user_id')->after('form_id')->constrained()->onDelete('cascade');
    $table->index('user_id');
});
```

#### 2. Backfill Data
```php
// Backfill user_id dari form
DB::table('result_rules')
    ->join('forms', 'result_rules.form_id', '=', 'forms.id')
    ->update([
        'result_rules.user_id' => DB::raw('forms.user_id')
    ]);

DB::table('rule_groups')
    ->join('forms', 'rule_groups.form_id', '=', 'forms.id')
    ->update([
        'rule_groups.user_id' => DB::raw('forms.user_id')
    ]);
```

#### 3. Update Model
```php
// app/Models/ResultRule.php
protected $fillable = [
    'form_id',
    'user_id', // Tambahkan ini
    // ... lainnya
];

// app/Models/RuleGroup.php
protected $fillable = [
    'form_id',
    'user_id', // Tambahkan ini
    // ... lainnya
];
```

#### 4. Update Controller
```php
// Saat create result_rule atau rule_group, tambahkan user_id
$rule = $form->resultRules()->create([
    'form_id' => $form->id,
    'user_id' => $form->user_id, // Tambahkan ini
    // ... lainnya
]);
```

---

### Opsi 3: Tetap Seperti Sekarang

**Keuntungan:**
- ✅ Tidak perlu perubahan
- ✅ Relasi melalui form sudah cukup
- ✅ Validasi di controller sudah ada

**Kekurangan:**
- ❌ Hanya 1 layer validasi
- ❌ Jika ada bug di validasi, semua data bisa diakses
- ❌ Tidak ada defense in depth

**Catatan:**
- Opsi ini bisa digunakan jika yakin validasi di controller selalu benar
- Tapi tidak recommended untuk production dengan data sensitif

---

## Rekomendasi Final

**Gunakan Opsi 1: Tambah Global Scope di Model**

**Alasan:**
1. ✅ Defense in depth (2 layer: controller + model)
2. ✅ Tidak perlu ubah database
3. ✅ Best practice Laravel
4. ✅ Mudah diimplementasikan
5. ✅ Tidak ada overhead besar
6. ✅ Otomatis melindungi semua query

**Prioritas:**
- 🔴 **HIGH** - Implementasikan segera untuk keamanan
- Penting untuk mencegah akses data user lain jika ada bug di validasi

---

## Testing Checklist

Setelah implementasi, test:

1. ✅ User A tidak bisa akses result_rules milik User B
2. ✅ User A tidak bisa akses rule_groups milik User B
3. ✅ Query melalui `$form->resultRules()` tetap bekerja
4. ✅ Query langsung `ResultRule::all()` otomatis filter berdasarkan user
5. ✅ Public routes (form submission) tetap bekerja (tidak ada Auth::id())
6. ✅ Admin atau superuser bisa bypass scope jika diperlukan

---

## Catatan Implementasi

- Implementasikan setelah semua fitur utama selesai
- Test thoroughly sebelum deploy ke production
- Dokumentasikan di code comments bahwa scope ini untuk security
- Pertimbangkan untuk tambahkan logging jika ada attempt akses unauthorized

---

## Referensi

- Laravel Global Scopes: https://laravel.com/docs/eloquent#global-scopes
- Laravel Authorization: https://laravel.com/docs/authorization
- Defense in Depth: https://en.wikipedia.org/wiki/Defense_in_depth_(computing)

