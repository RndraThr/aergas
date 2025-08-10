<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GasInDataRequest extends FormRequest
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
        $rules = [
            // Customer info (auto-filled, but validate for consistency)
            'reff_id_pelanggan' => 'required|string|exists:calon_pelanggan,reff_id_pelanggan',
            'nama_pelanggan' => 'required|string|max:255',
            'alamat' => 'required|string',
            'rt' => 'nullable|integer|min:1|max:999',
            'rw' => 'nullable|integer|min:1|max:999',
            'kelurahan' => 'required|in:Malaka Jaya,Malaka Sari,Pondok Bambu,Pondok Kelapa,Pondok Kopi,Cipinang Besar Selatan,Cipinang Muara,Dukuh Makasar,Jati,Cempaka',

            // Gas In specific info
            'tanggal_gas_in' => 'required|date|before_or_equal:today',
            'regulator_status' => 'required|in:Ada,Tidak Ada',
            'nomor_seri_mgrt' => 'required|string|max:100',
            'konversi_kompor' => 'required|in:1 Tungku,2 Tungku,3 Tungku,4 Tungku,5 Tungku,6 Tungku,1 Tungku + Oven,2 Tungku + Oven,3 Tungku + Oven,4 Tungku + Oven,5 Tungku + Oven,6 Tungku + Oven,1 Tungku + Oven + WH,2 Tungku + Oven + WH,3 Tungku + Oven + WH,4 Tungku + Oven + WH,5 Tungku + Oven + WH,6 Tungku + Oven + WH,1 Water Heater,2 Water Heater,3 Water Heater,4 Water Heater,5 Water Heater,6 Water Heater,1 Tungku + WH,2 Tungku + WH,3 Tungku + WH,4 Tungku + WH,5 Tungku + WH,6 Tungku + WH',
            'nama_petugas_gas_in' => 'required|string|max:255',

            // E-Signature (base64 encoded image data)
            'tanda_tangan_petugas_gas_in' => 'required|string',

            // Additional fields
            'catatan_teknis' => 'nullable|string|max:1000',
            'instruksi_pelanggan' => 'nullable|string|max:1000',
        ];

        // For create (store), photos are required
        if ($this->isMethod('POST')) {
            $rules = array_merge($rules, [
                'berita_acara_gas_in' => 'required|image|mimes:jpeg,jpg,png|max:10240',
                'foto_stand_meter_bubble_test' => 'required|image|mimes:jpeg,jpg,png|max:10240',
                'foto_valve_sk_bubble_test' => 'required|image|mimes:jpeg,jpg,png|max:10240',
                'foto_rangkaian_meter_pondasi' => 'required|image|mimes:jpeg,jpg,png|max:10240',
                'foto_kompor_menyala_merk' => 'required|image|mimes:jpeg,jpg,png|max:10240',
                'foto_sticker_sosialisasi' => 'required|image|mimes:jpeg,jpg,png|max:10240',
            ]);
        }

        // For update (PUT/PATCH), photos are optional
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules = array_merge($rules, [
                'berita_acara_gas_in' => 'nullable|image|mimes:jpeg,jpg,png|max:10240',
                'foto_stand_meter_bubble_test' => 'nullable|image|mimes:jpeg,jpg,png|max:10240',
                'foto_valve_sk_bubble_test' => 'nullable|image|mimes:jpeg,jpg,png|max:10240',
                'foto_rangkaian_meter_pondasi' => 'nullable|image|mimes:jpeg,jpg,png|max:10240',
                'foto_kompor_menyala_merk' => 'nullable|image|mimes:jpeg,jpg,png|max:10240',
                'foto_sticker_sosialisasi' => 'nullable|image|mimes:jpeg,jpg,png|max:10240',
            ]);

            // For update, signature is optional
            $rules['tanda_tangan_petugas_gas_in'] = 'nullable|string';
        }

        return $rules;
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            // Customer info messages
            'reff_id_pelanggan.required' => 'Reference ID pelanggan harus diisi',
            'reff_id_pelanggan.exists' => 'Reference ID pelanggan tidak valid',
            'nama_pelanggan.required' => 'Nama pelanggan harus diisi',
            'alamat.required' => 'Alamat harus diisi',
            'kelurahan.required' => 'Kelurahan harus dipilih',
            'kelurahan.in' => 'Kelurahan yang dipilih tidak valid',

            // Gas In specific messages
            'tanggal_gas_in.required' => 'Tanggal Gas In harus diisi',
            'tanggal_gas_in.date' => 'Format tanggal Gas In tidak valid',
            'tanggal_gas_in.before_or_equal' => 'Tanggal Gas In tidak boleh di masa depan',
            'regulator_status.required' => 'Status regulator harus dipilih',
            'regulator_status.in' => 'Status regulator tidak valid',
            'nomor_seri_mgrt.required' => 'Nomor seri MGRT harus diisi',
            'konversi_kompor.required' => 'Konversi kompor harus dipilih',
            'konversi_kompor.in' => 'Konversi kompor yang dipilih tidak valid',
            'nama_petugas_gas_in.required' => 'Nama petugas Gas In harus diisi',
            'tanda_tangan_petugas_gas_in.required' => 'Tanda tangan petugas Gas In harus diisi',

            // Photo messages
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
            'nama_pelanggan' => 'Nama Pelanggan',
            'alamat' => 'Alamat',
            'rt' => 'RT',
            'rw' => 'RW',
            'kelurahan' => 'Kelurahan',
            'tanggal_gas_in' => 'Tanggal Gas In',
            'regulator_status' => 'Status Regulator',
            'nomor_seri_mgrt' => 'Nomor Seri MGRT',
            'konversi_kompor' => 'Konversi Kompor',
            'nama_petugas_gas_in' => 'Nama Petugas Gas In',
            'tanda_tangan_petugas_gas_in' => 'Tanda Tangan Petugas Gas In',
            'catatan_teknis' => 'Catatan Teknis',
            'instruksi_pelanggan' => 'Instruksi Pelanggan',
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
        // Clean up and format data
        $cleanData = [];

        // Clean reff_id_pelanggan
        if ($this->has('reff_id_pelanggan')) {
            $cleanData['reff_id_pelanggan'] = strtoupper(trim($this->input('reff_id_pelanggan')));
        }

        // Clean nomor_seri_mgrt
        if ($this->has('nomor_seri_mgrt')) {
            $cleanData['nomor_seri_mgrt'] = strtoupper(trim($this->input('nomor_seri_mgrt')));
        }

        // Clean numeric fields
        $numericFields = ['rt', 'rw'];

        foreach ($numericFields as $field) {
            if ($this->has($field)) {
                $value = $this->input($field);
                $cleanData[$field] = is_numeric($value) ? $value : null;
            }
        }

        // Clean string fields
        $stringFields = ['nama_pelanggan', 'alamat', 'nama_petugas_gas_in', 'catatan_teknis', 'instruksi_pelanggan'];
        foreach ($stringFields as $field) {
            if ($this->has($field)) {
                $cleanData[$field] = trim($this->input($field));
            }
        }

        if (!empty($cleanData)) {
            $this->merge($cleanData);
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // For create operation, check if reff_id already has Gas In data
            if ($this->isMethod('POST') && $this->has('reff_id_pelanggan') && !$validator->errors()->has('reff_id_pelanggan')) {
                $existingGasIn = \App\Models\GasInData::where('reff_id_pelanggan', $this->input('reff_id_pelanggan'))->first();

                if ($existingGasIn) {
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

                    if (!$skCompleted) {
                        $validator->errors()->add('reff_id_pelanggan', 'SK harus diselesaikan terlebih dahulu sebelum Gas In');
                    }

                    if (!$srCompleted) {
                        $validator->errors()->add('reff_id_pelanggan', 'SR harus diselesaikan terlebih dahulu sebelum Gas In');
                    }

                    // Validate MGRT serial number consistency with SR data
                    if ($this->has('nomor_seri_mgrt') && $customer->srData) {
                        if ($this->input('nomor_seri_mgrt') !== $customer->srData->nomor_seri_mgrt) {
                            $validator->errors()->add('nomor_seri_mgrt', 'Nomor seri MGRT harus sama dengan yang ada di data SR');
                        }
                    }
                }
            }

            // Validate signature format (base64 image)
            if ($this->has('tanda_tangan_petugas_gas_in') && !$validator->errors()->has('tanda_tangan_petugas_gas_in')) {
                $signature = $this->input('tanda_tangan_petugas_gas_in');

                // Check if it's base64 encoded image
                if (!empty($signature)) {
                    if (!str_starts_with($signature, 'data:image/')) {
                        $validator->errors()->add('tanda_tangan_petugas_gas_in', 'Format tanda tangan tidak valid');
                    } else {
                        // Check base64 validity
                        $base64Data = explode(',', $signature)[1] ?? '';
                        if (!base64_decode($base64Data, true)) {
                            $validator->errors()->add('tanda_tangan_petugas_gas_in', 'Data tanda tangan tidak valid');
                        }
                    }
                }
            }

            // Validate tanggal_gas_in is not too far in the past
            if ($this->has('tanggal_gas_in')) {
                $tanggalGasIn = $this->input('tanggal_gas_in');
                if ($tanggalGasIn && \Carbon\Carbon::parse($tanggalGasIn)->diffInDays(now()) > 30) {
                    $validator->errors()->add('tanggal_gas_in', 'Tanggal Gas In terlalu lama (maksimal 30 hari yang lalu)');
                }
            }

            // For create operation, validate all required photos are present
            if ($this->isMethod('POST')) {
                $requiredPhotos = [
                    'berita_acara_gas_in' => 'Berita Acara Gas In',
                    'foto_stand_meter_bubble_test' => 'Stand Meter dan Bubble Test',
                    'foto_valve_sk_bubble_test' => 'Valve SK dan Bubble Test',
                    'foto_rangkaian_meter_pondasi' => 'Rangkaian Meter dan Pondasi',
                    'foto_kompor_menyala_merk' => 'Kompor Menyala dan Merk Kompor',
                    'foto_sticker_sosialisasi' => 'Sticker Sosialisasi'
                ];

                $missingPhotos = [];
                foreach ($requiredPhotos as $field => $label) {
                    if (!$this->hasFile($field)) {
                        $missingPhotos[] = $label;
                    }
                }

                if (!empty($missingPhotos)) {
                    $validator->errors()->add('photos', 'Foto yang wajib diupload: ' . implode(', ', $missingPhotos));
                }
            }

            // Validate customer data consistency
            if ($this->has('reff_id_pelanggan') && !$validator->errors()->has('reff_id_pelanggan')) {
                $customer = \App\Models\CalonPelanggan::where('reff_id_pelanggan', $this->input('reff_id_pelanggan'))->first();

                if ($customer) {
                    // Check if provided customer data matches database
                    if ($this->has('nama_pelanggan') && $this->input('nama_pelanggan') !== $customer->nama_pelanggan) {
                        $validator->errors()->add('nama_pelanggan', 'Nama pelanggan tidak sesuai dengan data di database');
                    }

                    if ($this->has('alamat') && $this->input('alamat') !== $customer->alamat) {
                        $validator->errors()->add('alamat', 'Alamat tidak sesuai dengan data di database');
                    }
                }
            }
        });
    }

    /**
     * Get photo data for processing
     */
    public function getPhotoData(): array
    {
        return [
            'berita_acara_gas_in' => $this->file('berita_acara_gas_in'),
            'foto_stand_meter_bubble_test' => $this->file('foto_stand_meter_bubble_test'),
            'foto_valve_sk_bubble_test' => $this->file('foto_valve_sk_bubble_test'),
            'foto_rangkaian_meter_pondasi' => $this->file('foto_rangkaian_meter_pondasi'),
            'foto_kompor_menyala_merk' => $this->file('foto_kompor_menyala_merk'),
            'foto_sticker_sosialisasi' => $this->file('foto_sticker_sosialisasi'),
        ];
    }

    /**
     * Get Gas In specific data
     */
    public function getGasInData(): array
    {
        return [
            'tanggal_gas_in' => $this->input('tanggal_gas_in'),
            'regulator_status' => $this->input('regulator_status'),
            'nomor_seri_mgrt' => $this->input('nomor_seri_mgrt'),
            'konversi_kompor' => $this->input('konversi_kompor'),
            'nama_petugas_gas_in' => $this->input('nama_petugas_gas_in'),
            'tanda_tangan_petugas_gas_in' => $this->input('tanda_tangan_petugas_gas_in'),
            'catatan_teknis' => $this->input('catatan_teknis'),
            'instruksi_pelanggan' => $this->input('instruksi_pelanggan'),
        ];
    }

    /**
     * Get signature data
     */
    public function getSignatureData(): ?string
    {
        return $this->input('tanda_tangan_petugas_gas_in');
    }

    /**
     * Check if signature is provided and valid
     */
    public function hasValidSignature(): bool
    {
        $signature = $this->input('tanda_tangan_petugas_gas_in');

        if (empty($signature)) {
            return false;
        }

        // Check if it's a valid base64 image
        if (!str_starts_with($signature, 'data:image/')) {
            return false;
        }

        $base64Data = explode(',', $signature)[1] ?? '';
        return base64_decode($base64Data, true) !== false;
    }
}
