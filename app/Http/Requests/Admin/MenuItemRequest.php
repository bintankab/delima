<?php

namespace App\Http\Requests\Admin;

use App\Models\MenuItemModel;
use App\Models\MenuModel;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MenuItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('parent_id') === '') {
            $this->merge(['parent_id' => null]);
        }

        $this->merge([
            'opens_in_new_tab' => $this->boolean('opens_in_new_tab'),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $item = $this->route('menuItem');
        $menuId = MenuModel::query()->where('location', 'header')->value('id');

        return [
            'label' => ['required', 'string', 'max:80'],
            'url' => ['required', 'string', 'max:255', function (string $attribute, mixed $value, Closure $fail): void {
                if (! is_string($value) || ! $this->isPublicUrl($value)) {
                    $fail('Alamat tautan harus diawali garis miring atau memakai http/https.');
                }
            }],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('menu_items', 'id')->where(
                    fn ($query) => $query->where('menu_id', $menuId)->whereNull('parent_id'),
                ),
                Rule::notIn($item instanceof MenuItemModel ? [$item->id] : []),
                Rule::prohibitedIf($item instanceof MenuItemModel && $item->children()->exists()),
            ],
            'opens_in_new_tab' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'label.required' => 'Label menu wajib diisi.',
            'label.max' => 'Label menu terlalu panjang.',
            'url.required' => 'Alamat tautan wajib diisi.',
            'url.max' => 'Alamat tautan terlalu panjang.',
            'parent_id.exists' => 'Induk menu tidak valid.',
            'parent_id.integer' => 'Induk menu tidak valid.',
            'parent_id.not_in' => 'Induk menu tidak valid.',
            'parent_id.prohibited' => 'Item yang punya submenu tidak bisa dijadikan submenu.',
        ];
    }

    private function isPublicUrl(string $url): bool
    {
        if (preg_match('/\s/', $url) === 1) {
            return false;
        }

        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return true;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);

        return in_array($scheme, ['http', 'https'], true)
            && filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
}
