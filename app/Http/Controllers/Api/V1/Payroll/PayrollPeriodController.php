<?php

namespace App\Http\Controllers\Api\V1\Payroll;

use App\Http\Resources\Api\V1\Payroll\PayrollPeriodResource;
use App\Support\Http\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Payroll\Enums\PayrollPeriodStatus;
use Modules\Payroll\Models\PayrollPeriod;
use Modules\Payroll\Services\PayrollFreezeService;

class PayrollPeriodController extends \App\Http\Controllers\Controller
{
    use ApiResponse;

    public function __construct(
        protected PayrollFreezeService $payrollFreezeService,
    ) {
    }

    public function index()
    {
        $periods = PayrollPeriod::query()
            ->with('payrollGroup')
            ->withCount('runs')
            ->orderByDesc('pay_date')
            ->paginate(10);

        return $this->success(
            PayrollPeriodResource::collection($periods->getCollection()),
            'Payroll periods retrieved successfully',
            meta: [
                'current_page' => $periods->currentPage(),
                'last_page' => $periods->lastPage(),
                'per_page' => $periods->perPage(),
                'total' => $periods->total(),
            ],
        );
    }

    public function show(PayrollPeriod $payrollPeriod)
    {
        $payrollPeriod->load('payrollGroup')->loadCount('runs');

        return $this->success(
            new PayrollPeriodResource($payrollPeriod),
            'Payroll period retrieved successfully',
        );
    }

    public function store(Request $request)
    {
        $validated = $this->validatePeriod($request);

        $period = PayrollPeriod::query()->create($validated);

        return $this->success(new PayrollPeriodResource($period->load('payrollGroup')->loadCount('runs')), 'Payroll period created successfully', 201);
    }

    public function update(Request $request, PayrollPeriod $payrollPeriod)
    {
        if ($payrollPeriod->loadMissing('runs')->isLocked()) {
            return $this->error($this->payrollFreezeService->buildLockedMessage($payrollPeriod, 'Payroll period'), status: 422);
        }

        $validated = $this->validatePeriod($request);
        $payrollPeriod->update($validated);

        return $this->success(new PayrollPeriodResource($payrollPeriod->fresh()->load('payrollGroup')->loadCount('runs')), 'Payroll period updated successfully');
    }

    public function destroy(PayrollPeriod $payrollPeriod)
    {
        if ($payrollPeriod->loadMissing('runs')->isLocked()) {
            return $this->error($this->payrollFreezeService->buildLockedMessage($payrollPeriod, 'Payroll period'), status: 422);
        }

        if ($payrollPeriod->runs()->exists()) {
            return $this->error('Payroll period cannot be deleted after payroll runs have been created.', status: 422);
        }

        $payrollPeriod->delete();

        return $this->success(null, 'Payroll period deleted successfully');
    }

    protected function validatePeriod(Request $request): array
    {
        return $request->validate([
            'payroll_group_id' => ['required', 'exists:payroll_groups,id'],
            'period_name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'pay_date' => ['nullable', 'date'],
            'status' => ['required', Rule::in(array_map(fn (PayrollPeriodStatus $status) => $status->value, PayrollPeriodStatus::cases()))],
        ]);
    }
}
