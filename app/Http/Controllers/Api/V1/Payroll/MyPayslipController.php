<?php

namespace App\Http\Controllers\Api\V1\Payroll;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Payroll\PayslipResource;
use App\Support\Http\ApiResponse;
use Illuminate\Http\Request;
use Modules\Payroll\Models\Payslip;

class MyPayslipController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $employeeId = $request->user()->employee?->id;

        abort_unless($employeeId !== null, 403);

        $payslips = Payslip::query()
            ->with(['payrollPeriod.payrollGroup', 'payrollRun'])
            ->where('employee_id', $employeeId)
            ->where(function ($query) {
                $query->where('is_published', true)
                    ->orWhereHas('payrollRun', fn ($runQuery) => $runQuery->where('calculation_status', 'paid'));
            })
            ->latest('issue_date')
            ->paginate(15);

        return $this->success(
            PayslipResource::collection($payslips->getCollection()),
            'My payslips retrieved successfully',
            meta: [
                'current_page' => $payslips->currentPage(),
                'last_page' => $payslips->lastPage(),
                'per_page' => $payslips->perPage(),
                'total' => $payslips->total(),
            ],
        );
    }

    public function show(Request $request, Payslip $payslip)
    {
        abort_unless($request->user()->employee?->id === $payslip->employee_id, 403);

        return $this->success(
            new PayslipResource($payslip->load(['employee', 'payrollPeriod', 'payrollRun'])),
            'My payslip retrieved successfully',
        );
    }
}
