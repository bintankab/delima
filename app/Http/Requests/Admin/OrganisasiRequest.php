<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrganisasiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $organisasi = $this->route('organisasi');

        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:100'],
            'code' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('organisasis', 'code')->ignore($organisasi?->id),
            ],
            'address' => ['nullable', 'string'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'is_active' => ['boolean'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama organisasi wajib diisi.',
            'name.max' => 'Nama organisasi terlalu panjang.',
            'type.max' => 'Jenis organisasi terlalu panjang.',
            'code.max' => 'Kode organisasi terlalu panjang.',
            'code.unique' => 'Kode organisasi sudah digunakan.',
            'contact_person.max' => 'Nama kontak terlalu panjang.',
            'phone.max' => 'Nomor telepon terlalu panjang.',
            'email.email' => 'Format email tidak valid.',
            'logo.image' => 'Logo harus berupa gambar.',
            'logo.mimes' => 'Logo harus berformat JPEG, PNG, atau WebP.',
            'logo.max' => 'Ukuran logo maksimal 5 MB.',
        ];
    }
}
