<?php

namespace App\Http\Requests\Admin;

use App\Models\CategoryModel;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['description', 'parent_id'] as $field) {
            if ($this->input($field) === '') {
                $this->merge([$field => null]);
            }
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $category = $this->route('category');

        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id'),
                function (string $attribute, mixed $value, Closure $fail) use ($category): void {
                    if ($value === null || ! $category instanceof CategoryModel) {
                        return;
                    }

                    if ((int) $value === $category->id) {
                        $fail('Kategori tidak dapat menjadi induk dari dirinya sendiri.');

                        return;
                    }

                    if (in_array((int) $value, $category->descendantIds(), true)) {
                        $fail('Induk tidak boleh berupa subkategori dari kategori ini.');
                    }
                },
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama kategori wajib diisi.',
            'name.max' => 'Nama kategori terlalu panjang.',
            'description.max' => 'Deskripsi kategori terlalu panjang.',
            'parent_id.exists' => 'Induk yang dipilih tidak ditemukan.',
            'parent_id.integer' => 'Induk yang dipilih tidak ditemukan.',
        ];
    }
}
