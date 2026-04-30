<?php

namespace App\Http\Controllers\Api\V1\Payroll;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Payroll\PayslipResource;
use App\Support\Http\ApiResponse;
use Illuminate\Http\Request;
use Modules\Payroll\Models\Payslip;

class PayslipController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $employeeId = $request->integer('employee_id');
        $periodId = $request->integer('period_id');

        $payslips = Payslip::query()
            ->with(['employee', 'payrollPeriod', 'payrollRun'])
            ->when($employeeId, fn ($query) => $query->where('employee_id', $employeeId))
            ->when($periodId, fn ($query) => $query->where('payroll_period_id', $periodId))
            ->latest('issue_date')
            ->paginate(15);

        return $this->success(
            PayslipResource::collection($payslips->getCollection()),
            'Payslips retrieved successfully',
            meta: [
                'current_page' => $payslips->currentPage(),
                'last_page' => $payslips->lastPage(),
                'per_page' => $payslips->perPage(),
                'total' => $payslips->total(),
            ],
        );
    }

    public function show(Payslip $payslip)
    {
        return $this->success(
            new PayslipResource($payslip->load(['employee', 'payrollPeriod', 'payrollRun'])),
            'Payslip retrieved successfully',
        );
    }
}
