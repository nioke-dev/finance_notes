# DESIGN SYSTEM & UI GUIDELINES (UI-DESIGN-SYSTEM.md)
**Status:** PANDUAN VISUAL KONSISTEN (WEB & MOBILE)  

Dokumen ini menjamin antarmuka tidak tampak seperti form HTML mentah atau aplikasi tanpa styling.

---

## 1. Palet Warna (Color Tokens)
- **Primary (Driver Green / Shopee Orange Accent)**:
  - Brand Primary: `#059669` (Emerald 600 - Kesan finansial & stabil)
  - Accent / Shopee Highlight: `#EE4D2D` (Shopee Orange untuk kartu driver)
- **Neutral (Slate Theme)**:
  - Background Utama: `#0F172A` (Dark Slate untuk mode malam saat closing) / `#F8FAFC` (Slate 50 di web terang)
  - Surface Card: `#1E293B` (Dark) / `#FFFFFF` (Light)
  - Text Primary: `#F8FAFC` (Dark) / `#0F172A` (Light)
  - Text Muted: `#94A3B8` (Slate 400)
- **Status Alerts**:
  - Danger / Belum Final / Selisih: `#EF4444` (Red 500)
  - Warning / Needs Attention: `#F59E0B` (Amber 500)
  - Success / Final / Cocok: `#10B981` (Emerald 500)

---

## 2. Tipografi & Skala
- **Font Family**: Inter, SF Pro, atau Plus Jakarta Sans.
- **Skala Ukuran**:
  - Display Angka Saldo: `text-3xl font-extrabold tracking-tight`
  - Heading Modul: `text-xl font-bold`
  - Body Text / Label Form: `text-sm font-medium`
  - Microcopy / Timestamp: `text-xs text-slate-400`

---

## 3. Komponen Form Reusable (Dilarang HTML Mentah)
1. **Input Teks & Angka Uang**:
   - Border halus: `border border-slate-300 dark:border-slate-700 rounded-xl`
   - Padding nyaman untuk jari tangan di mobile: `px-4 py-3.5`
   - Prefix simbol mata uang otomatis: `"Rp "` menempel di sisi kiri.
   - Focus ring: `focus:ring-2 focus:ring-emerald-500 focus:outline-none`.
2. **Tombol Cepat (Quick Action Pills)**:
   - Tinggi minimal 48px agar mudah ditekan saat motoran/on-beat.
   - Efek haptic / active scale: `active:scale-95 transition-transform duration-100`.
3. **Date Picker**:
   - Menggunakan modal picker bertema senada, bukan `input type="date"` default browser yang tidak seragam.
4. **Banner Peringatan Rekonsiliasi**:
   - Background: `bg-amber-500/10 border-l-4 border-amber-500`
   - Ikon peringatan jelas + teks tebal selisih uang + tombol *"Selesaikan Sekarang"*.

---

## 4. Standar Tampilan Dashboard Web
- Layout desktop memanfaatkan lebar layar:
  - Sisi kiri: Ringkasan Saldo Semua Akun & Total Kekayaan (Realtime).
  - Tengah: Grafik tren pendapatan ShopeeFood per tanggal & analitik bensin per hari.
  - Sisi kanan: Audio player rekaman Voice Memo harian untuk mendengar kembali memo saat closing.
