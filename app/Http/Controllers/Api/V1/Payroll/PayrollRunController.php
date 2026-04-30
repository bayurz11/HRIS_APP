<?php

namespace App\Http\Controllers\Api\V1\Payroll;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Payroll\PayrollRunResource;
use App\Support\Http\ApiResponse;
use Illuminate\Http\Request;
use Modules\Payroll\Models\PayrollRun;

class PayrollRunController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $periodId = $request->integer('period_id');
        $status = $request->string('status')->value();
        $employeeId = $request->integer('employee_id');

        $runs = PayrollRun::query()
            ->with(['employee', 'payrollPeriod', 'payslip', 'approvalSteps'])
            ->when($periodId, fn ($query) => $query->where('payroll_period_id', $periodId))
            ->when($employeeId, fn ($query) => $query->where('employee_id', $employeeId))
            ->when($status, fn ($query) => $query->where('calculation_status', $status))
            ->orderByDesc('payroll_period_id')
            ->orderBy('employee_id')
            ->paginate(15);

        return $this->success(
            PayrollRunResource::collection($runs->getCollection()),
            'Payroll runs retrieved successfully',
            meta: [
                'current_page' => $runs->currentPage(),
                'last_page' => $runs->lastPage(),
                'per_page' => $runs->perPage(),
                'total' => $runs->total(),
            ],
        );
    }

    public function show(PayrollRun $payrollRun)
    {
        $payrollRun->load([
            'employee.organization',
            'employee.user',
            'payrollProfile.taxStatus',
            'payrollPeriod.payrollGroup',
            'items',
            'bpjsResults',
            'taxResults',
            'payslip',
            'approvalSteps.actor',
            'workflowLogs.actor',
        ]);

        return $this->success(new PayrollRunResource($payrollRun), 'Payroll run retrieved successfully');
    }
}
