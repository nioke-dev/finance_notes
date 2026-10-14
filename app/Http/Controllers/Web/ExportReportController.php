<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportReportController extends Controller
{
    public function exportCsv(Request $request): StreamedResponse
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());

        $transactions = Transaction::whereBetween('transaction_time', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->with(['ledgerEntries.account', 'workDay'])
            ->orderBy('transaction_time', 'asc')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"mutasi_{$startDate}_{$endDate}.csv\"",
        ];

        return response()->stream(function () use ($transactions) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Waktu', 'No Referensi', 'Tipe', 'Deskripsi', 'Jumlah (Rp)', 'Akun Terlibat']);

            foreach ($transactions as $tx) {
                $accountsInvolved = $tx->ledgerEntries->map(function ($entry) {
                    $typeStr = $entry->entry_type instanceof \App\Enums\EntryType ? $entry->entry_type->value : $entry->entry_type;
                    return ($entry->account->name ?? 'Unknown') . ' (' . strtoupper($typeStr) . ': ' . $entry->amount . ')';
                })->implode('; ');

                fputcsv($handle, [
                    $tx->transaction_time->format('Y-m-d H:i:s'),
                    'TX-' . str_pad($tx->id, 6, '0', STR_PAD_LEFT),
                    strtoupper($tx->type),
                    $tx->description,
                    $tx->amount,
                    $accountsInvolved,
                ]);
            }
            fclose($handle);
        }, 200, $headers);
    }

    public function exportPdf(Request $request)
    {
        $month = $request->input('month', now()->format('Y-m'));
        $startDate = $month . '-01 00:00:00';
        $endDate = date('Y-m-t 23:59:59', strtotime($startDate));

        $transactions = Transaction::whereBetween('transaction_time', [$startDate, $endDate])
            ->with(['ledgerEntries.account', 'workDay'])
            ->orderBy('transaction_time', 'asc')
            ->get();

        $totalIncome = (int) $transactions->where('type', 'income')->sum('amount');
        $totalExpense = (int) $transactions->where('type', 'expense')->sum('amount');
        $netCashFlow = $totalIncome - $totalExpense;

        return view('reports.monthly_pdf', compact(
            'month',
            'transactions',
            'totalIncome',
            'totalExpense',
            'netCashFlow'
        ));
    }
}
