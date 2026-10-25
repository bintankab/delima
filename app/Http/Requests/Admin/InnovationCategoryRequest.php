<?php

namespace App\Http\Requests\Admin;

use App\Models\InnovationCategoryModel;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InnovationCategoryRequest extends FormRequest
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
        $category = $this->route('innovation_category');

        return [
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('innovation_categories', 'id'),
                function (string $attribute, mixed $value, Closure $fail) use ($category): void {
                    if ($value === null || ! $category instanceof InnovationCategoryModel) {
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
            'icon' => ['nullable', 'string', 'max:100'],
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
            'parent_id.exists' => 'Induk yang dipilih tidak ditemukan.',
            'parent_id.integer' => 'Induk yang dipilih tidak ditemukan.',
            'icon.max' => 'Nama ikon terlalu panjang.',
        ];
    }
}
