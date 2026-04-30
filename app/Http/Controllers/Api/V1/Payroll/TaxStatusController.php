<?php

namespace App\Http\Controllers\Api\V1\Payroll;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Payroll\TaxStatusResource;
use App\Support\Http\ApiResponse;
use Illuminate\Http\Request;
use Modules\Payroll\Models\TaxStatus;

class TaxStatusController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $onlyActive = $request->boolean('active_only', false);

        $statuses = TaxStatus::query()
            ->when($onlyActive, fn ($query) => $query->where('is_active', true))
            ->orderBy('code')
            ->get();

        return $this->success(TaxStatusResource::collection($statuses), 'Tax statuses retrieved successfully');
    }

    public function show(TaxStatus $taxStatus)
    {
        return $this->success(new TaxStatusResource($taxStatus), 'Tax status retrieved successfully');
    }
}
