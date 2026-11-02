<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PostRequest extends FormRequest
{
    public const STATUSES = ['draft', 'published'];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['excerpt', 'published_at'] as $field) {
            if ($this->input($field) === '') {
                $this->merge([$field => null]);
            }
        }

        if ($this->input('category_ids') === null || $this->input('category_ids') === '') {
            $this->merge(['category_ids' => []]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['required', 'string'],
            'status' => ['required', Rule::in(self::STATUSES)],
            'published_at' => ['nullable', 'date'],
            'cover' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240'],
            'category_ids' => ['array'],
            'category_ids.*' => ['integer', 'distinct', Rule::exists('categories', 'id')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Judul post wajib diisi.',
            'title.max' => 'Judul post terlalu panjang.',
            'excerpt.max' => 'Ringkasan terlalu panjang.',
            'content.required' => 'Isi post wajib diisi.',
            'status.required' => 'Status post wajib dipilih.',
            'status.in' => 'Status post tidak valid.',
            'published_at.date' => 'Tanggal terbit tidak valid.',
            'cover.image' => 'Cover harus berupa gambar.',
            'cover.mimes' => 'Cover harus berformat JPEG, PNG, atau WebP.',
            'cover.max' => 'Ukuran cover maksimal 10 MB.',
            'category_ids.array' => 'Kategori tidak valid.',
            'category_ids.*.integer' => 'Kategori yang dipilih tidak ditemukan.',
            'category_ids.*.exists' => 'Kategori yang dipilih tidak ditemukan.',
            'category_ids.*.distinct' => 'Kategori tidak valid.',
        ];
    }
}
