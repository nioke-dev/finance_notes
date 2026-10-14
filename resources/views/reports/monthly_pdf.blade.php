<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Keuangan Bulanan - {{ $month }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #1e293b;
            font-size: 11px;
            margin: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
            text-transform: uppercase;
        }
        .header p {
            margin: 4px 0 0 0;
            color: #64748b;
        }
        .summary-grid {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            gap: 15px;
        }
        .summary-box {
            border: 1px solid #cbd5e1;
            padding: 10px;
            border-radius: 6px;
            flex: 1;
        }
        .summary-title {
            font-size: 10px;
            text-transform: uppercase;
            color: #64748b;
            font-weight: bold;
        }
        .summary-value {
            font-size: 14px;
            font-weight: bold;
            margin-top: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            border: 1px solid #cbd5e1;
            padding: 6px 8px;
            text-align: left;
        }
        th {
            background-color: #f1f5f9;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 9px;
        }
        .text-right {
            text-align: right;
        }
        .income { color: #059669; }
        .expense { color: #dc2626; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Laporan Rekapitulasi Arus Kas Bulanan</h1>
        <p>ShopeeFood Driver & Keuangan Pribadi Malang &bull; Periode: {{ $month }}</p>
    </div>

    <div class="summary-grid">
        <div class="summary-box">
            <div class="summary-title">Total Pendapatan Bersih</div>
            <div class="summary-value income">Rp {{ number_format($totalIncome, 0, ',', '.') }}</div>
        </div>
        <div class="summary-box">
            <div class="summary-title">Total Pengeluaran</div>
            <div class="summary-value expense">Rp {{ number_format($totalExpense, 0, ',', '.') }}</div>
        </div>
        <div class="summary-box">
            <div class="summary-title">Net Arus Kas (Surplus/Defisit)</div>
            <div class="summary-value {{ $netCashFlow >= 0 ? 'income' : 'expense' }}">
                Rp {{ number_format($netCashFlow, 0, ',', '.') }}
            </div>
        </div>
    </div>

    <h3>Daftar Rincian Mutasi</h3>
    <table>
        <thead>
            <tr>
                <th>Waktu</th>
                <th>Tipe</th>
                <th>Deskripsi</th>
                <th class="text-right">Nominal</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $tx)
                <tr>
                    <td>{{ $tx->transaction_time->format('d/m/Y H:i') }}</td>
                    <td>{{ strtoupper($tx->type) }}</td>
                    <td>{{ $tx->description }}</td>
                    <td class="text-right">Rp {{ number_format($tx->amount, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="text-align: center; color: #94a3b8; padding: 20px;">
                        Tidak ada transaksi pada periode ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
