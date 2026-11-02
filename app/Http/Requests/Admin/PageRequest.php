<?php

namespace App\Http\Requests\Admin;

use App\Enums\PageLayout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PageRequest extends FormRequest
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

        $layout = PageLayout::tryFrom((string) $this->input('layout'));

        if ($layout === null || ! $layout->hasCards()) {
            $this->merge(['cards' => []]);
        } else {
            $cards = $this->input('cards', []);
            if (! is_array($cards)) {
                $cards = [];
            }

            foreach ($cards as $index => $card) {
                if (! is_array($card)) {
                    continue;
                }

                if (($card['image_id'] ?? null) === '' || ($card['image_id'] ?? null) === null) {
                    $cards[$index]['image_id'] = null;
                }

                if (($card['body'] ?? null) === null) {
                    $cards[$index]['body'] = '';
                }
            }

            $this->merge(['cards' => array_values($cards)]);
        }

        if ($layout === null || ! $layout->hasSlides()) {
            $this->merge([
                'slides' => [],
                'new_slides' => [],
                'new_slide_captions' => [],
            ]);
        } elseif ($this->input('slides') === null) {
            $this->merge(['slides' => []]);
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
            'layout' => ['required', Rule::enum(PageLayout::class)],
            'cards' => ['array'],
            'cards.*.title' => ['required', 'string', 'max:255'],
            'cards.*.body' => ['nullable', 'string', 'max:5000'],
            'cards.*.image_id' => ['nullable', 'integer'],
            'cards.*.image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240'],
            'slides' => ['array'],
            'slides.*.id' => ['required', 'integer'],
            'slides.*.caption' => ['nullable', 'string', 'max:255'],
            'new_slides' => ['array'],
            'new_slides.*' => ['image', 'mimes:jpeg,jpg,png,webp', 'max:10240'],
            'new_slide_captions' => ['array'],
            'new_slide_captions.*' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Judul page wajib diisi.',
            'title.max' => 'Judul page terlalu panjang.',
            'excerpt.max' => 'Ringkasan terlalu panjang.',
            'content.required' => 'Isi page wajib diisi.',
            'status.required' => 'Status page wajib dipilih.',
            'status.in' => 'Status page tidak valid.',
            'published_at.date' => 'Tanggal terbit tidak valid.',
            'layout.required' => 'Layout wajib dipilih.',
            'layout.enum' => 'Layout tidak valid.',
            'cards.*.title.required' => 'Judul kartu wajib diisi.',
            'cards.*.body.max' => 'Isi kartu terlalu panjang.',
            'cards.*.image.image' => 'Gambar kartu harus berupa gambar.',
            'cards.*.image.mimes' => 'Gambar kartu harus berformat JPEG, PNG, atau WebP.',
            'cards.*.image.max' => 'Ukuran gambar kartu maksimal 10 MB.',
            'new_slides.*.image' => 'Gambar slideshow harus berupa gambar.',
            'new_slides.*.mimes' => 'Gambar slideshow harus berformat JPEG, PNG, atau WebP.',
            'new_slides.*.max' => 'Ukuran gambar slideshow maksimal 10 MB.',
            'slides.*.caption.max' => 'Keterangan slideshow terlalu panjang.',
            'new_slide_captions.*.max' => 'Keterangan slideshow terlalu panjang.',
        ];
    }
}
