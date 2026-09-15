<?php

namespace App\Http\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('user'));
    }

    /**
     * UserPolicy::update() allows self-service updates (a user editing their
     * own profile) purely by permission-less identity match, so it never
     * sees this request's payload. Without this, a user could include
     * is_active in their own self-update and reactivate themselves the
     * moment their token is revoked (see UserService::update()) - so
     * is_active is dropped here for anyone who isn't a genuine
     * users.update-privileged admin, self-edit or not.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->user()->hasPermission('users.update')) {
            // JSON requests (putJson/PATCH with Content-Type: application/json)
            // are read from the json() bag, not $this->request - both must be
            // cleared or the field survives for JSON callers.
            $this->request->remove('is_active');
            $this->json()->remove('is_active');
        }
    }

    public function rules(): array
    {
        $userId = $this->route('user')->id;

        return [
            'employee_code' => ['nullable', 'string', 'max:50', Rule::unique('users', 'employee_code')->ignore($userId)],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'mobile' => ['nullable', 'string', 'max:20'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'password' => ['sometimes', 'nullable', 'string', 'min:8'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
