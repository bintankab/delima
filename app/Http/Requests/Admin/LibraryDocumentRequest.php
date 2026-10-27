<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LibraryDocumentRequest extends FormRequest
{
    public const TYPES = ['regulasi', 'panduan', 'laporan', 'lainnya'];

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $document = $this->route('library_document');
        $isSuperAdmin = $this->user()->hasRole('super-admin');

        return [
            'title' => ['required', 'string', 'max:255'],
            'nomor_dokumen' => ['nullable', 'string', 'max:100'],
            'penyusun' => ['nullable', 'string', 'max:255'],
            'tahun_terbit' => ['nullable', 'integer', 'between:2000,'.(now()->year + 1)],
            'tanggal_terbit' => ['nullable', 'date'],
            'description' => ['nullable', 'string'],
            'type' => ['required', Rule::in(self::TYPES)],
            'is_public' => ['boolean'],
            'organisasi_id' => [
                $isSuperAdmin ? 'nullable' : 'prohibited',
                'integer',
                Rule::exists('organisasis', 'id'),
            ],
            'file' => [$document ? 'nullable' : 'required', 'file', 'mimes:pdf,doc,docx,xls,xlsx', 'max:20480'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Judul dokumen wajib diisi.',
            'title.max' => 'Judul dokumen terlalu panjang.',
            'tahun_terbit.between' => 'Tahun terbit tidak valid.',
            'type.required' => 'Jenis dokumen wajib dipilih.',
            'type.in' => 'Jenis dokumen tidak valid.',
            'organisasi_id.exists' => 'Organisasi yang dipilih tidak ditemukan.',
            'organisasi_id.prohibited' => 'Admin OPD tidak dapat memilih organisasi lain.',
            'file.required' => 'Berkas dokumen wajib diunggah.',
            'file.mimes' => 'Berkas harus berformat PDF, DOC, DOCX, XLS, atau XLSX.',
            'file.max' => 'Ukuran berkas maksimal 20 MB.',
        ];
    }
}
