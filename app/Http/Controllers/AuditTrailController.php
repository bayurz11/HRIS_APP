<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Modules\AuditTrail\Models\AuditLog;

class AuditTrailController extends Controller
{
    public function index(Request $request)
    {
        $module = $request->string('module')->value();
        $event = $request->string('event')->value();

        return view('modules.audit-trail.index', [
            'logs' => AuditLog::query()
                ->with('actor')
                ->when($module, fn ($query) => $query->where('module', $module))
                ->when($event, fn ($query) => $query->where('event', $event))
                ->latest()
                ->paginate(15)
                ->withQueryString(),
            'modules' => AuditLog::query()->select('module')->distinct()->orderBy('module')->pluck('module'),
            'events' => AuditLog::query()->select('event')->distinct()->orderBy('event')->pluck('event'),
            'selectedModule' => $module,
            'selectedEvent' => $event,
        ]);
    }
}
