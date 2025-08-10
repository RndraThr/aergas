<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SKPhotoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // ✅ FIXED: Use proper Form Request method
        $user = $this->user();
        return $user && $user->hasRole(['sk', 'admin', 'super_admin']);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'reff_id_pelanggan' => 'required|string|exists:calon_pelanggan,reff_id_pelanggan',

            // 4 Foto Wajib SK - semuanya required untuk AI validation
            'ba_pemasangan_sk' => 'required|image|mimes:jpeg,jpg,png|max:10240', // 10MB max
            'foto_pneumatic_sk' => 'required|image|mimes:jpeg,jpg,png|max:10240',
            'foto_valve_krunchis' => 'required|image|mimes:jpeg,jpg,png|max:10240',
            'foto_isometrik_sk' => 'required|image|mimes:jpeg,jpg,png|max:10240',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'reff_id_pelanggan.required' => 'Reference ID pelanggan harus diisi',
            'reff_id_pelanggan.exists' => 'Reference ID pelanggan tidak valid',

            'ba_pemasangan_sk.required' => 'Foto Berita Acara Pemasangan SK harus diupload',
            'ba_pemasangan_sk.image' => 'File Berita Acara Pemasangan SK harus berupa gambar',
            'ba_pemasangan_sk.mimes' => 'Format Berita Acara Pemasangan SK harus JPG, JPEG, atau PNG',
            'ba_pemasangan_sk.max' => 'Ukuran Berita Acara Pemasangan SK maksimal 10MB',

            'foto_pneumatic_sk.required' => 'Foto Pneumatic SK harus diupload',
            'foto_pneumatic_sk.image' => 'File Pneumatic SK harus berupa gambar',
            'foto_pneumatic_sk.mimes' => 'Format Pneumatic SK harus JPG, JPEG, atau PNG',
            'foto_pneumatic_sk.max' => 'Ukuran Pneumatic SK maksimal 10MB',

            'foto_valve_krunchis.required' => 'Foto Valve Krunchis harus diupload',
            'foto_valve_krunchis.image' => 'File Valve Krunchis harus berupa gambar',
            'foto_valve_krunchis.mimes' => 'Format Valve Krunchis harus JPG, JPEG, atau PNG',
            'foto_valve_krunchis.max' => 'Ukuran Valve Krunchis maksimal 10MB',

            'foto_isometrik_sk.required' => 'Foto Isometrik SK harus diupload',
            'foto_isometrik_sk.image' => 'File Isometrik SK harus berupa gambar',
            'foto_isometrik_sk.mimes' => 'Format Isometrik SK harus JPG, JPEG, atau PNG',
            'foto_isometrik_sk.max' => 'Ukuran Isometrik SK maksimal 10MB',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'reff_id_pelanggan' => 'Reference ID Pelanggan',
            'ba_pemasangan_sk' => 'Berita Acara Pemasangan SK',
            'foto_pneumatic_sk' => 'Foto Pneumatic SK',
            'foto_valve_krunchis' => 'Foto Valve Krunchis',
            'foto_isometrik_sk' => 'Foto Isometrik SK',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Clean up reff_id_pelanggan if needed
        if ($this->has('reff_id_pelanggan')) {
            $this->merge([
                'reff_id_pelanggan' => strtoupper(trim($this->input('reff_id_pelanggan')))
            ]);
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Check if all required photos are present
            $requiredPhotos = [
                'ba_pemasangan_sk',
                'foto_pneumatic_sk',
                'foto_valve_krunchis',
                'foto_isometrik_sk'
            ];

            $missingPhotos = [];
            foreach ($requiredPhotos as $photo) {
                if (!$this->hasFile($photo)) {
                    $missingPhotos[] = $this->attributes()[$photo];
                }
            }

            if (!empty($missingPhotos)) {
                $validator->errors()->add('photos', 'Foto yang wajib diupload: ' . implode(', ', $missingPhotos));
            }

            // Check if reff_id already has SK data
            if ($this->has('reff_id_pelanggan') && !$validator->errors()->has('reff_id_pelanggan')) {
                $existingSK = \App\Models\SKData::where('reff_id_pelanggan', $this->input('reff_id_pelanggan'))->first();

                if ($existingSK && $this->route('skData') && $existingSK->id !== $this->route('skData')->id) {
                    $validator->errors()->add('reff_id_pelanggan', 'Data SK untuk customer ini sudah ada');
                }
            }
        });
    }
}
