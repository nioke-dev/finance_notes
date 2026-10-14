<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogWebController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::orderBy('created_at', 'desc');

        if ($request->filled('entity_type')) {
            $query->where('auditable_type', 'LIKE', '%' . $request->input('entity_type') . '%');
        }

        if ($request->filled('action')) {
            $query->where('action', $request->input('action'));
        }

        $logs = $query->paginate(20)->withQueryString();

        return view('pages.audit_logs.index', compact('logs'));
    }
}
