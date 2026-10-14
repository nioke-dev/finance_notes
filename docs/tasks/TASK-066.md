# TASK-066: Inisialisasi Project Flutter di Folder `mobile/` & Setup Design System Tokens

**Modul:** 7. Mobile Client Flutter (Quick-Input Lapangan)  
**Fokus Tunggal:** Setup Flutter Project, Dependencies (`pubspec.yaml`), & Theme Tokens  
**Status:** `[PENDING]`  
**Estimasi Waktu Pengerjaan Agent:** 1 Menit  

---

## 1. Deskripsi Detail Teknis
Menginisialisasi proyek mobile Flutter pada direktori `mobile/` dengan konfigurasi tema Dark Mode terstandarisasi sesuai dokumen `UI-DESIGN-SYSTEM.md` dan `PRD.md`:
1. **Inisialisasi Project & Dependensi (`pubspec.yaml`):**
   - HTTP Client: `dio: ^5.4.0`
   - State Management: `flutter_riverpod: ^2.5.1`
   - Audio Recording Intercom Helm: `record: ^5.1.2`
   - GPS & Lokasi Parkir: `geolocator: ^11.0.0`
   - Local Storage: `flutter_secure_storage: ^9.0.0`
   - Format Mata Uang: `intl: ^0.19.0`
2. **Definisi Theme Tokens (`lib/core/theme/app_theme.dart`):**
   - Background Primer: Slate 900 (`#0F172A`).
   - Surface / Card: Slate 800 (`#1E293B`).
   - Border: Slate 700 (`#334155`).
   - Accent / Brand: ShopeeFood Orange (`#EE4D2D`) & Emerald Green (`#10B981`) untuk indikator finansial surplus.
   - Text Primary: White (`#F8FAFC`), Text Muted: Slate 400 (`#94A3B8`).
3. **Penyusunan Struktur Folder Modular:**
   - `lib/core/` (network, theme, constants)
   - `lib/features/` (home, quick_actions, closing, voice_memo)

---

## 2. File-File yang Disentuh / Dibuat
1. `mobile/pubspec.yaml` — File konfigurasi proyek & daftar paket Flutter.
2. `mobile/lib/core/theme/app_theme.dart` — Tema terpusat dark slate & typography.
3. `mobile/lib/main.dart` — Entrypoint aplikasi dengan wrapper `ProviderScope`.
4. `mobile/test/widget_test.dart` — Automated widget test untuk inisialisasi aplikasi.

---

## 3. Spesifikasi Anatomi Kode (`mobile/lib/core/theme/app_theme.dart`)
```dart
import 'package:flutter/material.dart';

class AppTheme {
  static const Color slate900 = Color(0xFF0F172A);
  static const Color slate800 = Color(0xFF1E293B);
  static const Color slate700 = Color(0xFF334155);
  static const Color shopeeOrange = Color(0xFFEE4D2D);
  static const Color emerald500 = Color(0xFF10B981);
  static const Color rose500 = Color(0xFFF43F5E);
  static const Color amber500 = Color(0xFFF59E0B);

  static ThemeData get darkTheme {
    return ThemeData(
      brightness: Brightness.dark,
      scaffoldBackgroundColor: slate900,
      primaryColor: shopeeOrange,
      cardColor: slate800,
      colorScheme: const ColorScheme.dark(
        primary: shopeeOrange,
        secondary: emerald500,
        surface: slate800,
        background: slate900,
        error: rose500,
      ),
      appBarTheme: const AppBarTheme(
        backgroundColor: slate900,
        elevation: 0,
        centerTitle: false,
      ),
    );
  }
}
```

---

## 4. Kriteria Selesai (Acceptance Criteria)
1. Project Flutter di `mobile/` terkonfigurasi dengan dependensi `dio`, `flutter_riverpod`, `geolocator`, `record`.
2. `AppTheme.darkTheme` dapat dimuat oleh `MaterialApp` tanpa warning / error tema.
3. Test suite widget Flutter awal lulus 100%.

---

## 5. Automated Tests Wajib
- **File:** `mobile/test/widget_test.dart`
- **Metode Uji:**
  1. `testWidgets('App initializes with dark theme and shows Home title', (WidgetTester tester) async)`:
     - Pump widget `MyApp`.
     - Verifikasi tema yang aktif adalah dark mode dengan latar belakang `Color(0xFF0F172A)`.
