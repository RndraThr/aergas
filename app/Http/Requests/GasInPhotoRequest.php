<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GasInPhotoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        return $user && $user->hasRole(['gas_in', 'admin', 'super_admin']);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'reff_id_pelanggan' => 'required|string|exists:calon_pelanggan,reff_id_pelanggan',

            // 6 Foto Wajib Gas In - semuanya required untuk AI validation
            'berita_acara_gas_in' => 'required|image|mimes:jpeg,jpg,png|max:10240', // 10MB max
            'foto_stand_meter_bubble_test' => 'required|image|mimes:jpeg,jpg,png|max:10240',
            'foto_valve_sk_bubble_test' => 'required|image|mimes:jpeg,jpg,png|max:10240',
            'foto_rangkaian_meter_pondasi' => 'required|image|mimes:jpeg,jpg,png|max:10240',
            'foto_kompor_menyala_merk' => 'required|image|mimes:jpeg,jpg,png|max:10240',
            'foto_sticker_sosialisasi' => 'required|image|mimes:jpeg,jpg,png|max:10240',
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

            'berita_acara_gas_in.required' => 'Foto Berita Acara Gas In harus diupload',
            'berita_acara_gas_in.image' => 'File Berita Acara Gas In harus berupa gambar',
            'berita_acara_gas_in.mimes' => 'Format Berita Acara Gas In harus JPG, JPEG, atau PNG',
            'berita_acara_gas_in.max' => 'Ukuran Berita Acara Gas In maksimal 10MB',

            'foto_stand_meter_bubble_test.required' => 'Foto Stand Meter dan Bubble Test harus diupload',
            'foto_stand_meter_bubble_test.image' => 'File Stand Meter dan Bubble Test harus berupa gambar',
            'foto_stand_meter_bubble_test.mimes' => 'Format Stand Meter dan Bubble Test harus JPG, JPEG, atau PNG',
            'foto_stand_meter_bubble_test.max' => 'Ukuran Stand Meter dan Bubble Test maksimal 10MB',

            'foto_valve_sk_bubble_test.required' => 'Foto Valve SK dan Bubble Test harus diupload',
            'foto_valve_sk_bubble_test.image' => 'File Valve SK dan Bubble Test harus berupa gambar',
            'foto_valve_sk_bubble_test.mimes' => 'Format Valve SK dan Bubble Test harus JPG, JPEG, atau PNG',
            'foto_valve_sk_bubble_test.max' => 'Ukuran Valve SK dan Bubble Test maksimal 10MB',

            'foto_rangkaian_meter_pondasi.required' => 'Foto Rangkaian Meter dan Pondasi harus diupload',
            'foto_rangkaian_meter_pondasi.image' => 'File Rangkaian Meter dan Pondasi harus berupa gambar',
            'foto_rangkaian_meter_pondasi.mimes' => 'Format Rangkaian Meter dan Pondasi harus JPG, JPEG, atau PNG',
            'foto_rangkaian_meter_pondasi.max' => 'Ukuran Rangkaian Meter dan Pondasi maksimal 10MB',

            'foto_kompor_menyala_merk.required' => 'Foto Kompor Menyala dan Merk Kompor harus diupload',
            'foto_kompor_menyala_merk.image' => 'File Kompor Menyala dan Merk Kompor harus berupa gambar',
            'foto_kompor_menyala_merk.mimes' => 'Format Kompor Menyala dan Merk Kompor harus JPG, JPEG, atau PNG',
            'foto_kompor_menyala_merk.max' => 'Ukuran Kompor Menyala dan Merk Kompor maksimal 10MB',

            'foto_sticker_sosialisasi.required' => 'Foto Sticker Sosialisasi harus diupload',
            'foto_sticker_sosialisasi.image' => 'File Sticker Sosialisasi harus berupa gambar',
            'foto_sticker_sosialisasi.mimes' => 'Format Sticker Sosialisasi harus JPG, JPEG, atau PNG',
            'foto_sticker_sosialisasi.max' => 'Ukuran Sticker Sosialisasi maksimal 10MB',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'reff_id_pelanggan' => 'Reference ID Pelanggan',
            'berita_acara_gas_in' => 'Berita Acara Gas In',
            'foto_stand_meter_bubble_test' => 'Stand Meter dan Bubble Test',
            'foto_valve_sk_bubble_test' => 'Valve SK dan Bubble Test',
            'foto_rangkaian_meter_pondasi' => 'Rangkaian Meter dan Pondasi',
            'foto_kompor_menyala_merk' => 'Kompor Menyala dan Merk Kompor',
            'foto_sticker_sosialisasi' => 'Sticker Sosialisasi',
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
                'berita_acara_gas_in',
                'foto_stand_meter_bubble_test',
                'foto_valve_sk_bubble_test',
                'foto_rangkaian_meter_pondasi',
                'foto_kompor_menyala_merk',
                'foto_sticker_sosialisasi'
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

            // Check if reff_id already has Gas In data
            if ($this->has('reff_id_pelanggan') && !$validator->errors()->has('reff_id_pelanggan')) {
                $existingGasIn = \App\Models\GasInData::where('reff_id_pelanggan', $this->input('reff_id_pelanggan'))->first();

                if ($existingGasIn && $this->route('gasInData') && $existingGasIn->id !== $this->route('gasInData')->id) {
                    $validator->errors()->add('reff_id_pelanggan', 'Data Gas In untuk customer ini sudah ada');
                }
            }

            // Check if SK and SR are completed (business requirement)
            if ($this->has('reff_id_pelanggan') && !$validator->errors()->has('reff_id_pelanggan')) {
                $customer = \App\Models\CalonPelanggan::with(['skData', 'srData'])
                    ->where('reff_id_pelanggan', $this->input('reff_id_pelanggan'))
                    ->first();

                if ($customer) {
                    $skCompleted = $customer->skData && $customer->skData->status === 'approved';
                    $srCompleted = $customer->srData && $customer->srData->status === 'approved';

                    if (!$skCompleted || !$srCompleted) {
                        $validator->errors()->add('reff_id_pelanggan', 'SK dan SR harus diselesaikan terlebih dahulu sebelum Gas In');
                    }
                }
            }
        });
    }
}
