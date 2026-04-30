<?php

namespace App\Http\Controllers\Api\V1\Payroll;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Payroll\PayrollGroupResource;
use App\Support\Http\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Payroll\Models\PayrollGroup;

class PayrollGroupController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $groups = PayrollGroup::query()
            ->with('organization')
            ->withCount('periods')
            ->orderBy('name')
            ->paginate(15);

        return $this->success(
            PayrollGroupResource::collection($groups->getCollection()),
            'Payroll groups retrieved successfully',
            meta: $this->paginationMeta($groups),
        );
    }

    public function show(PayrollGroup $payrollGroup)
    {
        $payrollGroup->load('organization')->loadCount('periods');

        return $this->success(new PayrollGroupResource($payrollGroup), 'Payroll group retrieved successfully');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:payroll_groups,code'],
            'name' => ['required', 'string', 'max:255'],
            'organization_id' => ['nullable', 'exists:organizations,id'],
            'pay_frequency' => ['required', Rule::in(['monthly', 'biweekly', 'weekly'])],
            'payroll_day' => ['nullable', 'integer', 'min:1', 'max:31'],
        ]);

        $group = PayrollGroup::query()->create($validated);

        return $this->success(new PayrollGroupResource($group->load('organization')), 'Payroll group created successfully', 201);
    }

    public function update(Request $request, PayrollGroup $payrollGroup)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('payroll_groups', 'code')->ignore($payrollGroup->id)],
            'name' => ['required', 'string', 'max:255'],
            'organization_id' => ['nullable', 'exists:organizations,id'],
            'pay_frequency' => ['required', Rule::in(['monthly', 'biweekly', 'weekly'])],
            'payroll_day' => ['nullable', 'integer', 'min:1', 'max:31'],
        ]);

        $payrollGroup->update($validated);

        return $this->success(new PayrollGroupResource($payrollGroup->fresh('organization')->loadCount('periods')), 'Payroll group updated successfully');
    }

    public function destroy(PayrollGroup $payrollGroup)
    {
        if ($payrollGroup->periods()->exists()) {
            return $this->error('Payroll group cannot be deleted while it still has payroll periods.', status: 422);
        }

        $payrollGroup->delete();

        return $this->success(null, 'Payroll group deleted successfully');
    }

    protected function paginationMeta($paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }
}
