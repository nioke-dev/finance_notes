# API CONTRACTS SPECIFICATION (API-CONTRACTS.md)
**Status:** KONTRAK RESMI ENDPOINT (REST API)  
**Base URL:** `/api/v1`  
**Content-Type:** `application/json`

---

## 1. Modul Akun & Saldo (`/accounts`)

### A. Mendapatkan Daftar Semua Akun & Total Saldo
- **Method:** `GET /accounts`
- **Response `200 OK`:**
```json
{
  "status": "success",
  "data": {
    "total_wealth": 4500000,
    "total_digital": 4150000,
    "total_cash": 350000,
    "accounts": [
      { "id": 1, "name": "BCA", "type": "bank", "balance": 2500000 },
      { "id": 2, "name": "ShopeePay", "type": "ewallet", "balance": 750000 },
      { "id": 3, "name": "GoPay", "type": "ewallet", "balance": 650000 },
      { "id": 4, "name": "GoPay Merchant", "type": "ewallet", "balance": 250000 },
      { "id": 5, "name": "Cash", "type": "cash", "balance": 350000 }
    ]
  }
}
```

---

## 2. Modul Quick Lapangan (`/quick`)

### A. Quick Parkir Rp1.000 (1-Tap)
- **Method:** `POST /quick/parking`
- **Request Body:**
```json
{
  "work_day_id": 12,
  "restaurant_name": "Mie Gacoan Sutoyo", // Opsional
  "latitude": -7.966620,                   // Opsional
  "longitude": 112.632632                  // Opsional
}
```
- **Response `201 Created`:**
```json
{
  "status": "success",
  "message": "Parkir Rp1.000 berhasil dicatat",
  "data": {
    "transaction_id": 105,
    "deducted_account": "Cash",
    "amount": 1000,
    "recorded_at": "2026-10-14T11:45:00Z"
  }
}
```

### B. Quick Bensin (SPBU / Warung Madura)
- **Method:** `POST /quick/fuel`
- **Request Body:**
```json
{
  "work_day_id": 12,
  "vendor_type": "spbu", // "spbu" (Rp10.000/L) atau "madura" (Rp12.000/botol)
  "amount": 30000,
  "account_id": 1 // BCA atau Cash
}
```
- **Response `201 Created`:**
```json
{
  "status": "success",
  "message": "Bensin berhasil dicatat",
  "data": {
    "amount": 30000,
    "estimated_liters": 3.0,
    "account_name": "BCA"
  }
}
```

### C. Upload Voice Memo (Intercom Helm)
- **Method:** `POST /quick/voice-memo`
- **Content-Type:** `multipart/form-data`
- **Form Data:**
  - `work_day_id`: `12`
  - `audio_file`: binary file (.m4a/.mp3)
  - `duration_seconds`: `15`
  - `latitude`: `-7.970123`
  - `longitude`: `112.628912`
- **Response `201 Created`:**
```json
{
  "status": "success",
  "message": "Voice memo berhasil disimpan",
  "data": {
    "memo_id": 4,
    "file_url": "/storage/voice_memos/memo_12_4.m4a",
    "recorded_at": "2026-10-14T12:05:00Z"
  }
}
```

---

## 3. Modul Progres On-Beat & Evaluasi Jam 20.00 (`/driver`)

### A. Simpan Snapshot On-Beat All-in-One
- **Method:** `POST /driver/snapshot`
- **Request Body:**
```json
{
  "work_day_id": 12,
  "completed_orders": 22,
  "hub_income": 176000,
  "hub_bonus": 8000,
  "customer_tips": 15000,
  "shopeepay_balance": 520000,
  "gopay_balance": 185000,
  "gopay_merchant_balance": 8500
}
```
- **Response `200 OK`:**
```json
{
  "status": "success",
  "data": {
    "total_today_earned": 199000,
    "merchant_settled_status": "holding", // atau "ready_to_settle" jika >= 10k
    "recorded_at": "2026-10-14T19:30:00Z"
  }
}
```

### B. Evaluasi Jam 20.00 (Kelayakan Bonus Hub)
- **Method:** `POST /driver/evaluate-performance`
- **Request Body:**
```json
{
  "work_day_id": 12,
  "online_hours": 9.25,
  "peak_hour_rate": 85.5,
  "completion_rate": 92.0
}
```
- **Response `200 OK`:**
```json
{
  "status": "success",
  "data": {
    "is_eligible": true,
    "calculated_bonus": 14500, // Berdasarkan tier progresif order
    "message": "Performa memenuhi syarat. Bonus Hub diakui di estimasi pendapatan hari ini."
  }
}
```

---

## 4. Modul Rekonsiliasi & Closing (`/reconciliation`)

### A. Cek Status Tanggal Belum Final (Banner Mobile)
- **Method:** `GET /reconciliation/unfinalized-dates`
- **Response `200 OK`:**
```json
{
  "status": "success",
  "data": [
    {
      "work_day_id": 11,
      "work_date": "2026-10-13",
      "status": "needs_reconciliation",
      "cash_discrepancy": -15000,
      "reminder_message": "Tanggal 13 Okt belum difinalisasi (Selisih Cash -Rp15.000)"
    }
  ]
}
```

### B. Submit Denominasi Fisik & Validasi Closing
- **Method:** `POST /reconciliation/submit-closing`
- **Request Body:**
```json
{
  "work_day_id": 12,
  "denominations": {
    "coin_500": 4,
    "coin_1000": 3,
    "bill_2000": 5,
    "bill_5000": 4,
    "bill_10000": 6,
    "bill_20000": 5,
    "bill_50000": 4,
    "bill_100000": 2
  },
  "is_shopeepay_bonus_received": true
}
```
- **Response `200 OK` (Jika Selisih Rp0):**
```json
{
  "status": "success",
  "can_finalize": true,
  "message": "Selisih Rp0. Status tanggal kerja berhasil diubah ke FINAL."
}
```
- **Response `422 Unprocessable Entity` (Jika Ada Selisih):**
```json
{
  "status": "error",
  "can_finalize": false,
  "discrepancy": -12000,
  "message": "Terdapat selisih -Rp12.000. Tanggal tetap berstatus 'needs_reconciliation'."
}
```
