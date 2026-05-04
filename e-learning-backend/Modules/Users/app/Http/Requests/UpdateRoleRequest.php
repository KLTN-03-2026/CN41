<?php

namespace Modules\Users\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $roleId = $this->route('role');

        return [
            'name' => 'sometimes|string|max:255|unique:roles,name,'.$roleId,
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ];
    }
}
