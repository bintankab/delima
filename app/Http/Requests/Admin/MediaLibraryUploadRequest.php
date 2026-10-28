<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class MediaLibraryUploadRequest extends FormRequest
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
        return [
            'file' => ['required', 'image', 'mimes:jpeg,jpg,png,webp,gif', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Berkas gambar wajib diunggah.',
            'file.image' => 'Berkas harus berupa gambar.',
            'file.mimes' => 'Gambar harus berformat JPEG, PNG, WebP, atau GIF.',
            'file.max' => 'Ukuran gambar maksimal 5 MB.',
        ];
    }
}
