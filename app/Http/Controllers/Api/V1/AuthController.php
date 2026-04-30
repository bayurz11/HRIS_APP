<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\User;

class AuthController extends Controller
{
    use ApiResponse;

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:255'],
        ]);

        $user = User::query()
            ->with(['roles', 'employee.organization', 'employee.payrollProfile.payrollGroup', 'employee.payrollProfile.taxStatus'])
            ->where('email', $validated['email'])
            ->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken($validated['device_name'], $user->apiTokenAbilities());

        return $this->success([
            'token_type' => 'Bearer',
            'access_token' => $token->plainTextToken,
            'token' => [
                'name' => $token->accessToken->name,
                'abilities' => $token->accessToken->abilities,
                'last_used_at' => $token->accessToken->last_used_at?->toISOString(),
                'expires_at' => $token->accessToken->expires_at?->toISOString(),
            ],
            'user' => $this->userPayload($user),
        ], 'API login successful');
    }

    public function me(Request $request)
    {
        $user = $request->user()->loadMissing(['roles', 'employee.organization', 'employee.payrollProfile.payrollGroup', 'employee.payrollProfile.taxStatus']);

        return $this->success($this->userPayload($user), 'Authenticated user retrieved successfully');
    }

    public function logout(Request $request)
    {
        $request->user()?->currentAccessToken()?->delete();

        return $this->success(null, 'API token revoked successfully');
    }

    protected function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->roles->pluck('name')->values()->all(),
            'employee' => $user->employee ? [
                'id' => $user->employee->id,
                'employee_number' => $user->employee->employee_number,
                'full_name' => $user->employee->full_name,
                'organization' => $user->employee->organization ? [
                    'id' => $user->employee->organization->id,
                    'code' => $user->employee->organization->code,
                    'name' => $user->employee->organization->name,
                ] : null,
                'payroll_profile' => $user->employee->payrollProfile ? [
                    'id' => $user->employee->payrollProfile->id,
                    'basic_salary' => (float) $user->employee->payrollProfile->basic_salary,
                    'payment_type' => $user->employee->payrollProfile->payment_type,
                    'tax_status' => $user->employee->payrollProfile->taxStatus ? [
                        'id' => $user->employee->payrollProfile->taxStatus->id,
                        'code' => $user->employee->payrollProfile->taxStatus->code,
                        'name' => $user->employee->payrollProfile->taxStatus->name,
                    ] : null,
                    'payroll_group' => $user->employee->payrollProfile->payrollGroup ? [
                        'id' => $user->employee->payrollProfile->payrollGroup->id,
                        'code' => $user->employee->payrollProfile->payrollGroup->code,
                        'name' => $user->employee->payrollProfile->payrollGroup->name,
                    ] : null,
                ] : null,
            ] : null,
            'created_at' => $user->created_at?->toISOString(),
        ];
    }
}
