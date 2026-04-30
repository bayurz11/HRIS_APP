<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AuditLogResource;
use App\Support\Http\ApiResponse;
use Illuminate\Http\Request;
use Modules\AuditTrail\Models\AuditLog;

class AuditTrailController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $module = $request->string('module')->value();
        $event = $request->string('event')->value();

        $logs = AuditLog::query()
            ->with('actor')
            ->when($module, fn ($query) => $query->where('module', $module))
            ->when($event, fn ($query) => $query->where('event', $event))
            ->latest()
            ->paginate(20);

        return $this->success(
            AuditLogResource::collection($logs->getCollection()),
            'Audit logs retrieved successfully',
            meta: [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
                'filters' => [
                    'module' => $module,
                    'event' => $event,
                ],
            ],
        );
    }
}
