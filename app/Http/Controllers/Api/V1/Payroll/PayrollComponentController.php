<?php

namespace App\Http\Controllers\Api\V1\Payroll;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Payroll\PayrollComponentResource;
use App\Support\Http\ApiResponse;
use Illuminate\Http\Request;
use Modules\Payroll\Models\PayrollComponent;

class PayrollComponentController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $activeOnly = $request->boolean('active_only', false);
        $category = $request->string('category')->value();

        $components = PayrollComponent::query()
            ->when($activeOnly, fn ($query) => $query->where('is_active', true))
            ->when($category, fn ($query) => $query->where('category', $category))
            ->orderBy('name')
            ->get();

        return $this->success(PayrollComponentResource::collection($components), 'Payroll components retrieved successfully');
    }

    public function show(PayrollComponent $payrollComponent)
    {
        return $this->success(new PayrollComponentResource($payrollComponent), 'Payroll component retrieved successfully');
    }
}
