<?php

namespace App\Http\Requests\Admin;

use App\Models\UserModel;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserRequest extends FormRequest
{
    public const SUPER_ADMIN_ASSIGNABLE_ROLES = ['super-admin', 'admin-opd', 'reviewer', 'bupati', 'dprd', 'asn', 'publik'];

    public const ADMIN_OPD_ASSIGNABLE_ROLES = ['asn'];

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return list<string>
     */
    public static function assignableRolesFor(UserModel $actor): array
    {
        return $actor->hasRole('super-admin') ? self::SUPER_ADMIN_ASSIGNABLE_ROLES : self::ADMIN_OPD_ASSIGNABLE_ROLES;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->route('user');
        $actor = $this->user();
        $allowedRoles = self::assignableRolesFor($actor);

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user?->id),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => [
                $user ? 'nullable' : 'required',
                'confirmed',
                Password::defaults(),
            ],
            'is_active' => ['boolean'],
            'role' => ['required', Rule::in($allowedRoles)],
            'organisasi_id' => [
                Rule::requiredIf(fn (): bool => $actor->hasRole('super-admin') && in_array($this->input('role'), ['admin-opd', 'asn'], true)),
                'nullable',
                'integer',
                Rule::exists('organisasis', 'id'),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama wajib diisi.',
            'name.max' => 'Nama terlalu panjang.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan.',
            'phone.max' => 'Nomor telepon terlalu panjang.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak sesuai.',
            'role.required' => 'Role wajib dipilih.',
            'role.in' => 'Role yang dipilih tidak valid.',
            'organisasi_id.required' => 'Organisasi/OPD wajib dipilih untuk role Admin OPD.',
            'organisasi_id.exists' => 'Organisasi yang dipilih tidak ditemukan.',
        ];
    }
}
