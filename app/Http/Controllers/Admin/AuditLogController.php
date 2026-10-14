<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::with('user');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->query('user_id'));
        }

        if ($request->filled('action')) {
            $query->where('action', 'like', '%'.$request->query('action').'%');
        }

        if ($request->filled('target_type')) {
            $query->where('target_type', 'like', '%'.$request->query('target_type').'%');
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->query('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->query('date_to'));
        }

        $logs = $query->latest('id')->paginate(20)->withQueryString();

        // Get unique actions for filter dropdown
        $actions = AuditLog::select('action')->distinct()->pluck('action');
        $users = User::whereIn('id', AuditLog::select('user_id')->distinct()->whereNotNull('user_id'))->get(['id', 'name', 'email']);

        return view('admin.audit-logs.index', compact('logs', 'actions', 'users'));
    }

    public function show(AuditLog $auditLog)
    {
        $oldValues = $this->maskSensitiveData($auditLog->old_values ?? []);
        $newValues = $this->maskSensitiveData($auditLog->new_values ?? []);

        return response()->json([
            'id' => $auditLog->id,
            'action' => $auditLog->action,
            'user' => $auditLog->user?->name ?? 'System',
            'target_type' => $auditLog->target_type,
            'target_id' => $auditLog->target_id,
            'description' => $auditLog->description,
            'ip_address' => $auditLog->ip_address,
            'created_at' => $auditLog->created_at?->format('Y-m-d H:i:s'),
            'old_values' => $oldValues,
            'new_values' => $newValues,
        ]);
    }

    private function maskSensitiveData(array $data): array
    {
        $sensitiveKeys = ['password', 'secret', 'token', 'key', 'webhook_secret', 'credential', 'api_key'];

        foreach ($data as $key => $val) {
            foreach ($sensitiveKeys as $sensitive) {
                if (stripos($key, $sensitive) !== false) {
                    $data[$key] = '*** MASKED ***';
                    break;
                }
            }
            if (is_array($val)) {
                $data[$key] = $this->maskSensitiveData($val);
            }
        }

        return $data;
    }
}
