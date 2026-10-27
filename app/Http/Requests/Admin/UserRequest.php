<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserRequest extends FormRequest
{
    public const ASSIGNABLE_ROLES = ['super-admin', 'admin-opd', 'reviewer'];

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->route('user');

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
            'role' => ['required', Rule::in(self::ASSIGNABLE_ROLES)],
            'organisasi_id' => [
                Rule::requiredIf($this->input('role') === 'admin-opd'),
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
