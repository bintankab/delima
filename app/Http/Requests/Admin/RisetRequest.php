<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RisetRequest extends FormRequest
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
            'organisasi_id' => ['required', 'integer', Rule::exists('organisasis', 'id')],
            'category_id' => ['required', 'integer', Rule::exists('riset_categories', 'id')],
            'title' => ['required', 'string', 'max:255'],
            'peneliti' => ['required', 'string', 'max:255'],
            'abstrak' => ['required', 'string'],
            'latar_belakang' => ['nullable', 'string'],
            'metodologi' => ['nullable', 'string'],
            'tahun' => ['required', 'integer', 'between:2021,'.now()->year],
            'published_at' => ['required', 'date', 'after_or_equal:2021-01-01', 'before_or_equal:today'],
            'cover' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240'],
            'documents' => ['nullable', 'array'],
            'documents.*' => ['file', 'mimes:pdf,doc,docx', 'max:10240'],
            'remove_document_ids' => ['nullable', 'array'],
            'remove_document_ids.*' => ['integer'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'organisasi_id.required' => 'OPD asal wajib dipilih.',
            'organisasi_id.integer' => 'OPD yang dipilih tidak ditemukan.',
            'organisasi_id.exists' => 'OPD yang dipilih tidak ditemukan.',
            'category_id.required' => 'Kategori wajib dipilih.',
            'category_id.integer' => 'Kategori yang dipilih tidak ditemukan.',
            'category_id.exists' => 'Kategori yang dipilih tidak ditemukan.',
            'title.required' => 'Judul wajib diisi.',
            'title.max' => 'Judul terlalu panjang.',
            'peneliti.required' => 'Nama peneliti wajib diisi.',
            'peneliti.max' => 'Nama peneliti terlalu panjang.',
            'abstrak.required' => 'Abstrak wajib diisi.',
            'tahun.required' => 'Tahun wajib diisi.',
            'tahun.integer' => 'Tahun harus berupa angka.',
            'tahun.between' => 'Tahun harus antara :min dan :max.',
            'published_at.required' => 'Tanggal terbit wajib diisi.',
            'published_at.date' => 'Tanggal terbit tidak valid.',
            'published_at.after_or_equal' => 'Tanggal terbit tidak boleh sebelum tahun 2021.',
            'published_at.before_or_equal' => 'Tanggal terbit tidak boleh setelah hari ini.',
            'cover.image' => 'Cover harus berupa gambar.',
            'cover.mimes' => 'Cover harus berformat JPEG, PNG, atau WebP.',
            'cover.max' => 'Ukuran cover maksimal 10 MB.',
            'documents.array' => 'Dokumen harus berupa daftar berkas.',
            'documents.*.file' => 'Setiap dokumen harus berupa berkas.',
            'documents.*.mimes' => 'Dokumen harus berformat PDF, DOC, atau DOCX.',
            'documents.*.max' => 'Ukuran tiap dokumen maksimal 10 MB.',
        ];
    }
}
