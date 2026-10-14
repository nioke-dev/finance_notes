# MASTER TASK BACKLOG (TASK-BACKLOG.md)
**Total Tasks:** 105 Super-Mikro Tasks (Terdefinisi 100% Lengkap, Atomik, & Super Detail)  
**Status Eksekusi:** Dikerjakan satu per satu secara berurutan sesuai aturan di `AGENTS.md`  

---

## Modul 0: Autentikasi & Keamanan Dasar
- [x] `TASK-076`: Setup Autentikasi Laravel Sanctum & Migrasi `personal_access_tokens` `[DONE]`
- [x] `TASK-077`: Form Request & API Controller Login Driver (`POST /api/v1/auth/login`) `[DONE]`
- [x] `TASK-078`: Halaman Web Login Driver Blade Tailwind (`GET /login` & `POST /login`) `[DONE]`
- [x] `TASK-079`: Middleware Proteksi Session Web & Grouping Rute Dashboard `[DONE]`

---

## Modul 1: Fondasi Backend Laravel 11 & Database Engine
- [x] `TASK-001`: Inisialisasi Project Laravel 11, Pembersihan Boilerplate, & Setup In-Memory SQLite Testing `[DONE]`
- [x] `TASK-002`: Definisi Enum PHP 8 Master Akun (`AccountType` & `AccountCategory`) `[DONE]`
- [x] `TASK-003`: Migrasi Database Tabel Master Akun (`accounts`) `[DONE]`
- [x] `TASK-004`: Model Eloquent `Account` & Scope Queries `[DONE]`
- [x] `TASK-005`: Seeder Akun Default Dompet Driver Malang (`AccountSeeder`) `[DONE]`
- [x] `TASK-006`: Migrasi Database Tabel Audit Log Mutasi (`audit_logs`) `[DONE]`
- [x] `TASK-007`: Model Eloquent `AuditLog` & Service Saldo Awal (`InitialBalanceService`) `[DONE]`
- [x] `TASK-080`: Form Request Validasi Inisialisasi Saldo Akun (`SetInitialBalanceRequest`) `[DONE]`
- [x] `TASK-008`: Controller & API Inisialisasi Saldo Awal Akun (`POST /api/v1/accounts/initial-balance`) `[DONE]`
- [x] `TASK-074`: Migrasi Database Tabel Kategori Transaksi Dinamis (`categories`) `[DONE]`
- [x] `TASK-075`: Model Eloquent `Category` & Seeder Kategori Bawaan Driver `[DONE]`
- [x] `TASK-009`: Migrasi Skema Tabel Tanggal Kerja Driver (`work_days`) `[DONE]`
- [x] `TASK-010`: Migrasi Database Tabel Tanggal Kerja (`work_days`) `[DONE]`
- [x] `TASK-011`: Model Eloquent `WorkDay` & Relasi Mutasi Harian `[DONE]`
- [x] `TASK-012`: Migrasi Database Tabel Header Transaksi (`transactions`) `[DONE]`
- [x] `TASK-013`: Model Eloquent `Transaction` & Relasi Buku Besar `[DONE]`
- [x] `TASK-014`: Migrasi Database Baris Jurnal Double-Entry (`ledger_entries`) `[DONE]`
- [x] `TASK-015`: Model Eloquent `LedgerEntry` `[DONE]`
- [x] `TASK-016`: Custom Exception Keseimbangan Jurnal (`UnbalancedLedgerException`) `[DONE]`
- [x] `TASK-017`: Core Double-Entry Engine Service (`LedgerService`) `[DONE]`
- [x] `TASK-018`: Transfer Service Antar Akun Sendiri + Beban Biaya Admin (`TransferService`) `[DONE]`
- [x] `TASK-081`: Form Request Validasi Transfer Antar Akun (`TransferFundsRequest`) `[DONE]`
- [x] `TASK-019`: Controller API Transfer Antar Akun `[DONE]`
- [x] `TASK-020`: Reversal Engine Service (Koreksi Mutasi Tanpa Hard Delete) `[DONE]`
- [x] `TASK-102`: Resource API JSON Transformation Master Akun (`AccountResource`) `[DONE]`
- [x] `TASK-103`: Resource API JSON Transformation Mutasi Transaksi (`TransactionResource`) `[DONE]`

---

## Modul 2: Operasional ShopeeFood & Lapangan Engine
- [x] `TASK-021`: Migrasi Database Tabel Skema Bonus ShopeeFood Malang (`shopee_bonus_schemes`) `[DONE]`
- [x] `TASK-022`: Model Eloquent `ShopeeBonusScheme` & Seeder Malang 2026 `[DONE]`
- [x] `TASK-023`: Service Kalkulator Bonus Bertingkat (`ShopeeBonusCalculatorService`) `[DONE]`
- [x] `TASK-024`: Service Validasi Kelayakan Bonus Cut-Off Jam 20.00 (`BonusEligibilityService`) `[DONE]`
- [x] `TASK-025`: Controller & Endpoint Evaluasi Performa Jam 20.00 (`GET /api/v1/driver/evaluate-bonus`) `[DONE]`
- [x] `TASK-026`: Migrasi Tabel `driver_snapshots` & Model Eloquent On-Beat Progress `[DONE]`
- [x] `TASK-027`: Controller & API Pencatatan Snapshot On-Beat (`POST /api/v1/driver/snapshot`) `[DONE]`
- [x] `TASK-028`: Service Pengakuan Tip QRIS Otomatis On-Beat (`AutoQrisTipService`) `[DONE]`
- [x] `TASK-029`: Service Settlement GoPay Merchant Tiap 1 Jam / Minimal Rp10.000 `[DONE]`
- [x] `TASK-030`: Service & Endpoint Konfirmasi Pencairan Bonus Hub ShopeePay Jam 22.00 `[DONE]`

---

## Modul 3: Quick Action Lapangan
- [x] `TASK-031`: Service Quick Parkir 1-Tap Potong Kas Tunai Rp1.000 / Rp2.000 `[DONE]`
- [x] `TASK-082`: Form Request Validasi Quick Parkir (`QuickParkingRequest`) `[DONE]`
- [x] `TASK-032`: Controller API Quick Parkir + Koordinat GPS `[DONE]`
- [x] `TASK-033`: Service Quick Bensin SPBU vs Madura Auto-Kalkulasi Volume `[DONE]`
- [x] `TASK-083`: Form Request Validasi Quick Bensin (`QuickFuelRequest`) `[DONE]`
- [x] `TASK-034`: Controller API Quick Bensin Lapangan `[DONE]`
- [x] `TASK-035`: Migrasi Database Tabel Catatan Suara Intercom Helm (`voice_memos`) `[DONE]`
- [x] `TASK-036`: Model Eloquent `VoiceMemo` & Audio Storage Mutator `[DONE]`
- [x] `TASK-037`: Endpoint Upload Voice Memo Lapangan (`POST /api/v1/voice-memos`) `[DONE]`
- [x] `TASK-038`: Endpoint List & Playback Voice Memo per Shift Tanggal Kerja `[DONE]`
- [x] `TASK-039`: Service Pencatatan Pesanan COD Lapangan & Surplus Tip Tunai `[DONE]`
- [x] `TASK-084`: Form Request Validasi Pesanan COD Lapangan (`CodOrderRequest`) `[DONE]`
- [x] `TASK-040`: Controller API Pencatatan Pesanan COD `[DONE]`
- [x] `TASK-085`: Form Request Validasi Pengeluaran Pribadi BCA (`PersonalExpenseRequest`) `[DONE]`
- [x] `TASK-041`: Controller Quick Input Pengeluaran Pribadi BCA `[DONE]`

---

## Modul 4: Pengelolaan Utang, PayLater, & Teman
- [x] `TASK-042`: Migrasi Database Tabel Master Utang & PayLater (`debts`) & Model `[DONE]`
- [x] `TASK-043`: Migrasi Skema Tabel Jadwal Cicilan Bulanan (`debt_installments`) & Model `[DONE]`
- [x] `TASK-044`: Service Pencairan Pinjaman Dana Masuk (`LoanDisbursementService`) `[DONE]`
- [x] `TASK-045`: Service SPayLater Skema Promo Diskon 0% & Pelunasan Cicilan (`SPayLaterPaymentService`) `[DONE]`
- [x] `TASK-046`: Service Pelunasan Utang Teman & Auto-Sedekah Sisa Utang (`FriendDebtSettlementService`) `[DONE]`
- [x] `TASK-047`: Form Request & API Endpoint Pelunasan Utang Teman (`POST /api/v1/debts/friend-payment`) `[DONE]`
- [x] `TASK-048`: API Endpoint Ringkasan Jadwal Jatuh Tempo Utang 30 Hari (`GET /api/v1/debts/upcoming`) `[DONE]`
- [x] `TASK-098`: Form Request Validasi Pinjaman Baru (`StoreDebtRequest`) `[DONE]`
- [x] `TASK-099`: Form Request Validasi Pembayaran Cicilan Utang (`PayDebtInstallmentRequest`) `[DONE]`
- [x] `TASK-100`: Form Request Validasi Pelunasan Utang Teman (`SettleFriendDebtRequest`) `[DONE]`
- [x] `TASK-105`: Resource API JSON Transformation Utang & Cicilan (`DebtResource`) `[DONE]`

---

## Modul 5: Rekonsiliasi, Denominasi, & Closing Engine
- [x] `TASK-049`: Migrasi Database Tabel Rincian Fisik Kas Closing (`cash_denominations`) `[DONE]`
- [x] `TASK-050`: Model Eloquent `CashDenomination` & Kalkulator Otomatis Akumulasi Fisik `[DONE]`
- [x] `TASK-051`: Service Rekonsiliasi Saldo Buku vs Aktual (`ReconciliationEngineService`) `[DONE]`
- [x] `TASK-052`: Service Penguncian Finalisasi Closing (`ClosingDayService`) `[DONE]`
- [x] `TASK-053`: Form Request & API Finalisasi Closing Hari Kerja (`POST /api/v1/reconciliation/finalize-day`) `[DONE]`
- [x] `TASK-054`: Service Pembukaan Tanggal Kerja Baru Saat Tanggal Lama Masih Belum Final `[DONE]`
- [x] `TASK-055`: Controller & Endpoint API Banner Pengingat Hari Kerja Belum Selesai `[DONE]`
- [x] `TASK-056`: Service Resolusi Selisih & Penambahan Transaksi Terlupa ke Tanggal Lampau `[DONE]`
- [x] `TASK-086`: Form Request Validasi Finalisasi Closing (`FinalizeClosingRequest`) `[DONE]`
- [x] `TASK-087`: Form Request Validasi Resolusi Selisih Kas Lampau (`ResolveDiscrepancyRequest`) `[DONE]`
- [x] `TASK-101`: Form Request Validasi Pembukaan Hari Kerja Baru (`OpenWorkDayRequest`) `[DONE]`
- [x] `TASK-104`: Resource API JSON Transformation Hari Kerja (`WorkDayResource`) `[DONE]`

---

## Modul 6: Web Super App Dashboard & Visualisasi (Laravel Blade Tailwind)
- [x] `TASK-057`: Setup Tailwind CSS Dark Theme & Master Layout Web Blade (`layouts/app.blade.php`) `[DONE]`
- [x] `TASK-058`: Komponen Blade Reusable Form Anti-Mentah (`<x-currency-input>` & `<x-status-badge>`) `[DONE]`
- [x] `TASK-059`: Halaman Web Dashboard Ringkasan Semua Akun & Total Net Worth (`GET /dashboard`) `[DONE]`
- [x] `TASK-060`: Halaman Web Closing Harian & Audit Denominasi Fisik (`GET /closing/{id}`) `[DONE]`
- [x] `TASK-061`: Komponen Blade Audio Player Voice Memo HTML5 (`<x-voice-memo-player>`) `[DONE]`
- [x] `TASK-062`: Halaman Web Analitik Performa Driver ShopeeFood (`GET /analytics/driver`) `[DONE]`
- [x] `TASK-063`: Halaman Web Analitik Pengeluaran Bensin & Kendaraan (`GET /analytics/fuel`) `[DONE]`
- [x] `TASK-064`: Halaman Web Pengelolaan Utang & Jadwal Cicilan (`GET /debts`) `[DONE]`
- [x] `TASK-065`: Fitur Export Laporan Mutasi & Cash Flow CSV & PDF (`/export/...`) `[DONE]`
- [x] `TASK-088`: Halaman Web Konfigurasi Skema Bonus ShopeeFood di Web Admin `[DONE]`
- [x] `TASK-089`: Halaman Web Audit Log Explorer (`GET /audit-logs`) `[DONE]`

---

## Modul 7: Mobile Quick Action Client (Flutter)
- [x] `TASK-066`: Inisialisasi Project Flutter di Folder `mobile/` & Setup Design System Tokens `[DONE]`
- [x] `TASK-067`: Setup Dio API Client & Token Interceptor Flutter (`ApiClient`) `[DONE]`
- [x] `TASK-090`: Repository Data Layer Quick Actions Mobile (`QuickActionRepository`) `[DONE]`
- [x] `TASK-091`: Riverpod State Notifier & Provider Quick Actions Mobile (`QuickActionController`) `[DONE]`
- [x] `TASK-092`: Repository Data Layer ShopeeFood Lapangan Mobile (`ShopeeRepository`) `[DONE]`
- [x] `TASK-094`: Riverpod State Notifier Monitoring On-Beat Shopee (`ShopeeController`) `[DONE]`
- [x] `TASK-093`: Repository Data Layer Closing & Rekonsiliasi Mobile (`ClosingRepository`) `[DONE]`
- [x] `TASK-095`: Riverpod State Notifier Closing Audit Mobile (`ClosingController`) `[DONE]`
- [x] `TASK-096`: Riverpod State Notifier Voice Memo (`VoiceMemoController`) `[DONE]`
- [x] `TASK-068`: Layar Beranda Mobile (`HomeScreen`) & Banner Pengingat Hari Gantung `[DONE]`
- [x] `TASK-069`: Widget Quick Parkir 1-Tap & Integrasi Geolocation GPS (`geolocator`) `[DONE]`
- [x] `TASK-070`: BottomSheet Quick Bensin Flutter (Toggle SPBU vs Madura & Auto-liter) `[DONE]`
- [x] `TASK-071`: Floating Action Button Audio Recorder Intercom Helm (`record` Package) `[DONE]`
- [x] `TASK-072`: Layar On-Beat Progress Snapshot & Dialog Evaluasi Jam 20.00 Flutter `[DONE]`
- [x] `TASK-073`: Layar Closing Mobile: Input Denominasi Fisik & Validasi Selisih Rp0 `[DONE]`
- [x] `TASK-097`: Integration Test End-to-End On-Beat Flow Mobile Flutter `[DONE]`

---

## Modul 8: Penyempurnaan Web Desktop (Desktop-First Core Architecture)
- [x] `TASK-104`: Multi-COD Asynchronous Tracking (Kode 3-Digit Resto, Calculator Kembalian Cepat, Auto-Rounding & Cancel/Reversal) `[DONE]`
- [x] `TASK-105`: Target Belanja Survival & Wishlist Prioritas 5-Level (Needs vs Wants, Progress Bar, Quick Sisihkan 10k/20k, & Realisasi Belanja) `[DONE]`
- [x] `TASK-106`: Safe-to-Spend Metric Card (Total Likuid - Survival Reserved Level 1-3 - Wants Reserved Level 4-5) `[DONE]`
- [x] `TASK-107`: Snapshot 4 Saldo Digital Malam Hari & Deteksi Surplus QRIS Otomatis (GoPay Merchant, GoPay, Dompet ShopeeFood Driver, ShopeePay) `[DONE]`
- [x] `TASK-108`: Automated Feature & Web Tests Suite (280 Tests Passed 100%) & Live Webhook Deployment `[DONE]`

