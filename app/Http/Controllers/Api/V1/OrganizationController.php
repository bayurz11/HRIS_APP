<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\OrganizationResource;
use App\Support\Http\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Organization\Models\Organization;

class OrganizationController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $organizations = Organization::query()
            ->with('parent')
            ->withCount(['employees', 'payrollGroups'])
            ->orderBy('name')
            ->paginate(15);

        return $this->success(
            OrganizationResource::collection($organizations->getCollection()),
            'Organizations retrieved successfully',
            meta: $this->paginationMeta($organizations),
        );
    }

    public function show(Organization $organization)
    {
        $organization->load('parent')->loadCount(['employees', 'payrollGroups']);

        return $this->success(new OrganizationResource($organization), 'Organization retrieved successfully');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:organizations,code'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:100'],
            'parent_id' => ['nullable', 'exists:organizations,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $organization = Organization::query()->create($validated);

        return $this->success(new OrganizationResource($organization), 'Organization created successfully', 201);
    }

    public function update(Request $request, Organization $organization)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('organizations', 'code')->ignore($organization->id)],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:100'],
            'parent_id' => ['nullable', 'exists:organizations,id', Rule::notIn([$organization->id])],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $organization->update($validated);

        return $this->success(new OrganizationResource($organization->fresh('parent')->loadCount(['employees', 'payrollGroups'])), 'Organization updated successfully');
    }

    public function destroy(Organization $organization)
    {
        if ($organization->employees()->exists() || $organization->payrollGroups()->exists()) {
            return $this->error('Organization cannot be deleted while linked to employees or payroll groups.', status: 422);
        }

        $organization->delete();

        return $this->success(null, 'Organization deleted successfully');
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
