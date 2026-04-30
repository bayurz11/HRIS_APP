<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Organization\Models\Organization;

class OrganizationController extends Controller
{
    public function index()
    {
        return view('modules.organization.index', [
            'organizations' => Organization::query()
                ->withCount(['employees', 'payrollGroups'])
                ->orderBy('name')
                ->paginate(10),
            'stats' => [
                'organization_count' => Organization::query()->count(),
                'active_count' => Organization::query()->where('is_active', true)->count(),
            ],
        ]);
    }

    public function create()
    {
        return view('modules.organization.create', [
            'organization' => new Organization(['is_active' => true]),
            'parents' => Organization::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:organizations,code'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:100'],
            'parent_id' => ['nullable', 'exists:organizations,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        Organization::query()->create($validated);

        return redirect()
            ->route('organization.index')
            ->with('status', 'Organisasi berhasil dibuat.');
    }

    public function edit(Organization $organization)
    {
        return view('modules.organization.edit', [
            'organization' => $organization,
            'parents' => Organization::query()
                ->whereKeyNot($organization->id)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function update(Request $request, Organization $organization): RedirectResponse
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

        return redirect()
            ->route('organization.index')
            ->with('status', 'Organisasi berhasil diperbarui.');
    }

    public function destroy(Organization $organization): RedirectResponse
    {
        if ($organization->employees()->exists() || $organization->payrollGroups()->exists()) {
            return back()->with('error', 'Organisasi tidak bisa dihapus selama masih terhubung ke karyawan atau grup payroll.');
        }

        $organization->delete();

        return redirect()
            ->route('organization.index')
            ->with('status', 'Organisasi berhasil dihapus.');
    }
}
