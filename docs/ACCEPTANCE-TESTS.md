# ACCEPTANCE TESTS PLAN (ACCEPTANCE-TESTS.md)
**Status:** DAFTAR UJI OTOMATIS WAJIB  
**Framework Backend:** Pest / PHPUnit (Laravel 11)  
**Database Test:** SQLite in-memory (`:memory:`)

Semua skenario pengujian di bawah ini adalah automated tests yang harus berjalan hijau (100% PASS) sebelum task terkait dianggap selesai.

---

## 1. Test Suite Ledger & Presisi Keuangan (`tests/Feature/LedgerPrecisionTest.php`)
- `test_initial_balance_is_not_treated_as_income`: Memastikan pengisian saldo awal akun tidak masuk ke laporan laba/rugi pendapatan.
- `test_transfer_between_own_accounts_links_correctly`: Transfer BCA ke ShopeePay Rp100.000 dengan admin Rp1.000 memotong BCA Rp101.000, menambah ShopeePay Rp100.000, dan mencatat admin Rp1.000 sebagai pengeluaran.
- `test_no_floating_point_rounding_error_in_large_sum`: Pengujian presisi hingga Rp1 pada 10.000 transaksi beruntun.
- `test_audit_log_created_when_transaction_is_reversed`: Memastikan pembatalan/koreksi transaksi membuat row baru di `audit_logs` dan membalik saldo dengan benar tanpa hard delete.

---

## 2. Test Suite Bonus Hub ShopeeFood (`tests/Unit/ShopeeBonusCalculatorTest.php`)
- `test_bonus_tier_zero_for_under_16_orders`: Input 14 order $\rightarrow$ Bonus Rp0.
- `test_bonus_tier_one_calculation`: Input 18 order $\rightarrow$ $(18 - 15) \times 1.000 = \text{Rp}3.000$.
- `test_bonus_tier_two_calculation`: Input 23 order $\rightarrow$ $(5 \times 1.000) + (3 \times 1.500) = 5.000 + 4.500 = \text{Rp}9.500$.
- `test_bonus_tier_three_calculation`: Input 26 order $\rightarrow$ $(5 \times 1.000) + (5 \times 1.500) + (1 \times 2.000) = \text{Rp}14.500$.
- `test_orders_outside_10_to_20_are_excluded_from_bonus_count`: Order masuk jam 08.00 pagi atau 21.00 malam tidak dihitung ke akumulasi tiering bonus.

---

## 3. Test Suite Evaluasi Performa Jam 20.00 (`tests/Feature/PerformanceEvaluationTest.php`)
- `test_performance_ineligible_if_online_hours_under_9`: Jika waktu online 8.75 jam, evaluasi mengembalikan bonus Rp0 meskipun order $\ge$ 26.
- `test_performance_eligible_recognizes_bonus_immediately`: Jika waktu online 9.1 jam, bonus langsung dihitung dan diakui di total estimasi pendapatan hari itu.

---

## 4. Test Suite GoPay Merchant Auto-Settlement (`tests/Feature/MerchantSettlementTest.php`)
- `test_merchant_balance_under_10k_holds_in_merchant_account`: Saldo Rp8.500 tetap berada di akun GoPay Merchant dan tidak dipindahkan ke GoPay.
- `test_merchant_balance_over_10k_settles_to_gopay`: Saldo $\ge$ Rp10.000 memicu transfer ledger ke akun GoPay.

---

## 5. Test Suite Quick Input Lapangan (`tests/Feature/QuickActionsTest.php`)
- `test_quick_parking_deducts_cash_and_saves_gps`: Quick parkir memotong saldo Cash tepat Rp1.000 dan menyimpan koordinat latitude/longitude.
- `test_quick_fuel_calculates_correct_liters`: Input bensin SPBU Rp30.000 menghasilkan estimasi 3.0 liter. Input Warung Madura Rp24.000 menghasilkan estimasi 2 botol.
- `test_voice_memo_upload_stores_audio_and_links_work_day`: Upload file .m4a berhasil disimpan ke disk dan berelasi dengan `work_days`.

---

## 6. Test Suite Utang Teman & Auto-Sedekah (`tests/Feature/DebtForgivenessTest.php`)
- `test_debt_payment_with_forgiveness_splits_to_charity_account`: Utang Rp249.000 dibayar Rp248.000 dengan opsi `diikhlaskan` $\rightarrow$ saldo kas bertambah Rp248.000, piutang teman berkurang Rp249.000 (lunas), dan akun Beban Sedekah bertambah Rp1.000 (keseimbangan ledger terjaga).

---

## 7. Test Suite Rekonsiliasi & Closing Lock (`tests/Feature/ReconciliationLockTest.php`)
- `test_cannot_finalize_work_day_if_cash_discrepancy_exists`: Hitung fisik beda Rp500 dari saldo buku $\rightarrow$ status tetap `needs_reconciliation`, response `422 Unprocessable`.
- `test_can_finalize_work_day_when_discrepancy_is_zero`: Setelah koreksi dan selisih Rp0 $\rightarrow$ status berubah `final`.
- `test_new_work_day_can_be_opened_even_with_unfinalized_previous_day`: User tetap bisa membuka tanggal kerja baru meskipun tanggal kemarin masih `needs_reconciliation`.
- `test_unfinalized_dates_endpoint_returns_alert_banner`: API banner mendeteksi tanggal yang belum final beserta nominal selisihnya.
