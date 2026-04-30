<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Payroll\Enums\PayrollRunStatus;
use Modules\Payroll\Models\PayrollPeriod;
use Modules\Payroll\Models\PayrollRun;

class PayrollRunController extends Controller
{
    public function index(Request $request)
    {
        $periodId = $request->integer('period_id');
        $status = $request->string('status')->value();

        $runs = PayrollRun::query()
            ->with(['employee.organization', 'payrollPeriod.payrollGroup', 'payslip', 'approvalSteps'])
            ->when($periodId, fn ($query) => $query->where('payroll_period_id', $periodId))
            ->when($status, fn ($query) => $query->where('calculation_status', $status))
            ->orderByDesc('payroll_period_id')
            ->orderBy('employee_id')
            ->paginate(12)
            ->withQueryString();

        return view('modules.payroll.runs.index', [
            'runs' => $runs,
            'periods' => PayrollPeriod::query()->with('payrollGroup')->orderByDesc('start_date')->get(),
            'statuses' => PayrollRunStatus::cases(),
            'selectedPeriodId' => $periodId,
            'selectedStatus' => $status,
        ]);
    }

    public function show(PayrollRun $run)
    {
        return view('modules.payroll.runs.show', [
            'run' => $run->load([
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
            ]),
        ]);
    }
}
