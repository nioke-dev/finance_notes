# PROTOKOL REVISI & PERUBAHAN KEBUTUHAN (CHANGE-LOG.md)
**Status:** DOKUMEN MANAJEMEN PERUBAHAN  

Setiap kali pengguna meminta revisi fitur, penambahan alur baru, atau penghapusan skenario lama, langkah wajib yang harus dijalankan agent:
1. **Catat Permintaan**: Masukkan tanggal, uraian permintaan revisi dari pengguna ke tabel di bawah.
2. **Audit Dampak**: Telusuri dokumen mana saja yang terpengaruh (`PRD.md`, `BUSINESS-RULES.md`, `DATA-MODEL.md`, `API-CONTRACTS.md`, dsb).
3. **Update Spesifikasi**: Perbarui isi dokumen terkait agar mencerminkan keputusan terbaru (dokumen lama tidak boleh bertentangan).
4. **Update Task Backlog & Progres**: Tambahkan, sesuaikan, atau coret task di `TASK-BACKLOG.md` dan sesuaikan persentase di `PROGRESS-TRACKER.md`.

---

## Tabel Log Riwayat Revisi Pengguna (Change Requests)

| ID Revisi | Tanggal | Permintaan Pengguna | Dokumen Diperbarui | Dampak Task Backlog | Status |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `CR-001` | 14/10/2026 | Pembentukan dokumen pengendali perubahan & pencatatan keputusan | `DECISIONS.md`, `CHANGE-LOG.md` | Memperkuat workflow eksekusi task | `[APPLIED]` |
