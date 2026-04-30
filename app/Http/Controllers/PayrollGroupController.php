<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\AuditTrail\Services\AuditTrailService;
use Modules\Organization\Models\Organization;
use Modules\Payroll\Models\PayrollGroup;

class PayrollGroupController extends Controller
{
    public function __construct(
        protected AuditTrailService $auditTrailService,
    ) {
    }

    public function index()
    {
        return view('modules.payroll.groups.index', [
            'groups' => PayrollGroup::query()
                ->with('organization')
                ->withCount('periods')
                ->orderBy('name')
                ->paginate(10),
        ]);
    }

    public function create()
    {
        return view('modules.payroll.groups.create', [
            'group' => new PayrollGroup(['pay_frequency' => 'monthly', 'payroll_day' => 25]),
            'organizations' => Organization::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:payroll_groups,code'],
            'name' => ['required', 'string', 'max:255'],
            'organization_id' => ['nullable', 'exists:organizations,id'],
            'pay_frequency' => ['required', Rule::in(['monthly', 'biweekly', 'weekly'])],
            'payroll_day' => ['nullable', 'integer', 'min:1', 'max:31'],
        ]);

        $group = PayrollGroup::query()->create($validated);

        $this->auditTrailService->recordModelChange(
            module: 'payroll_master',
            event: 'created',
            description: "Grup payroll {$group->name} dibuat.",
            auditable: $group,
            actor: $request->user(),
            after: $group->toArray(),
        );

        return redirect()
            ->route('payroll.groups.index')
            ->with('status', 'Grup payroll berhasil dibuat.');
    }

    public function edit(PayrollGroup $group)
    {
        return view('modules.payroll.groups.edit', [
            'group' => $group,
            'organizations' => Organization::query()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, PayrollGroup $group): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('payroll_groups', 'code')->ignore($group->id)],
            'name' => ['required', 'string', 'max:255'],
            'organization_id' => ['nullable', 'exists:organizations,id'],
            'pay_frequency' => ['required', Rule::in(['monthly', 'biweekly', 'weekly'])],
            'payroll_day' => ['nullable', 'integer', 'min:1', 'max:31'],
        ]);

        $before = $group->toArray();
        $group->update($validated);
        $this->auditTrailService->recordModelChange(
            module: 'payroll_master',
            event: 'updated',
            description: "Grup payroll {$group->name} diperbarui.",
            auditable: $group,
            actor: $request->user(),
            before: $before,
            after: $group->fresh()->toArray(),
        );

        return redirect()
            ->route('payroll.groups.index')
            ->with('status', 'Grup payroll berhasil diperbarui.');
    }

    public function destroy(PayrollGroup $group): RedirectResponse
    {
        if ($group->periods()->exists()) {
            return back()->with('error', 'Grup payroll tidak bisa dihapus selama masih memiliki periode payroll.');
        }

        $before = $group->toArray();
        $group->delete();
        $this->auditTrailService->record(
            module: 'payroll_master',
            event: 'deleted',
            description: "Grup payroll {$before['name']} dihapus.",
            actor: auth()->user(),
            before: $before,
        );

        return redirect()
            ->route('payroll.groups.index')
            ->with('status', 'Grup payroll berhasil dihapus.');
    }
}
