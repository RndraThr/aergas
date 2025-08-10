<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SRPhotoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // ✅ FIXED: Use proper Form Request method
        $user = $this->user();
        return $user && $user->hasRole(['sr', 'admin', 'super_admin']);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'reff_id_pelanggan' => 'required|string|exists:calon_pelanggan,reff_id_pelanggan',

            // 4 Foto Wajib SR - semuanya required untuk AI validation
            'foto_pneumatic_sr' => 'required|image|mimes:jpeg,jpg,png|max:10240', // 10MB max
            'foto_rangkaian_pondasi_sr' => 'required|image|mimes:jpeg,jpg,png|max:10240',
            'foto_stand_meter' => 'required|image|mimes:jpeg,jpg,png|max:10240',
            'foto_isometrik_sr' => 'required|image|mimes:jpeg,jpg,png|max:10240',
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

            'foto_pneumatic_sr.required' => 'Foto Pneumatic SR harus diupload',
            'foto_pneumatic_sr.image' => 'File Pneumatic SR harus berupa gambar',
            'foto_pneumatic_sr.mimes' => 'Format Pneumatic SR harus JPG, JPEG, atau PNG',
            'foto_pneumatic_sr.max' => 'Ukuran Pneumatic SR maksimal 10MB',

            'foto_rangkaian_pondasi_sr.required' => 'Foto Rangkaian & Pondasi SR harus diupload',
            'foto_rangkaian_pondasi_sr.image' => 'File Rangkaian & Pondasi SR harus berupa gambar',
            'foto_rangkaian_pondasi_sr.mimes' => 'Format Rangkaian & Pondasi SR harus JPG, JPEG, atau PNG',
            'foto_rangkaian_pondasi_sr.max' => 'Ukuran Rangkaian & Pondasi SR maksimal 10MB',

            'foto_stand_meter.required' => 'Foto Stand Meter harus diupload',
            'foto_stand_meter.image' => 'File Stand Meter harus berupa gambar',
            'foto_stand_meter.mimes' => 'Format Stand Meter harus JPG, JPEG, atau PNG',
            'foto_stand_meter.max' => 'Ukuran Stand Meter maksimal 10MB',

            'foto_isometrik_sr.required' => 'Foto Isometrik SR harus diupload',
            'foto_isometrik_sr.image' => 'File Isometrik SR harus berupa gambar',
            'foto_isometrik_sr.mimes' => 'Format Isometrik SR harus JPG, JPEG, atau PNG',
            'foto_isometrik_sr.max' => 'Ukuran Isometrik SR maksimal 10MB',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'reff_id_pelanggan' => 'Reference ID Pelanggan',
            'foto_pneumatic_sr' => 'Foto Pneumatic SR',
            'foto_rangkaian_pondasi_sr' => 'Foto Rangkaian & Pondasi SR',
            'foto_stand_meter' => 'Foto Stand Meter',
            'foto_isometrik_sr' => 'Foto Isometrik SR',
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
                'foto_pneumatic_sr',
                'foto_rangkaian_pondasi_sr',
                'foto_stand_meter',
                'foto_isometrik_sr'
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

            // Check if reff_id already has SR data
            if ($this->has('reff_id_pelanggan') && !$validator->errors()->has('reff_id_pelanggan')) {
                $existingSR = \App\Models\SRData::where('reff_id_pelanggan', $this->input('reff_id_pelanggan'))->first();

                if ($existingSR && $this->route('srData') && $existingSR->id !== $this->route('srData')->id) {
                    $validator->errors()->add('reff_id_pelanggan', 'Data SR untuk customer ini sudah ada');
                }
            }
        });
    }
}
