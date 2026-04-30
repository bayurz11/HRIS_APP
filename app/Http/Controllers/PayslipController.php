<?php

namespace App\Http\Controllers;

use Modules\Payroll\Models\Payslip;
use Modules\Payroll\Services\PayslipService;

class PayslipController extends Controller
{
    public function __construct(
        protected PayslipService $payslipService,
    ) {
    }

    public function download(Payslip $payslip)
    {
        abort_unless($payslip->canBeDownloaded(), 422, 'Payslip must be published before download.');

        return $this->payslipService->download($payslip);
    }
}
