<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SRDataRequest extends FormRequest
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
        $rules = [
            // Customer info (auto-filled, but validate for consistency)
            'reff_id_pelanggan' => 'required|string|exists:calon_pelanggan,reff_id_pelanggan',
            'nama_pelanggan' => 'required|string|max:255',
            'alamat' => 'required|string',
            'rt' => 'nullable|integer|min:1|max:999',
            'rw' => 'nullable|integer|min:1|max:999',
            'kelurahan' => 'required|in:Malaka Jaya,Malaka Sari,Pondok Bambu,Pondok Kelapa,Pondok Kopi,Cipinang Besar Selatan,Cipinang Muara,Dukuh Makasar,Jati,Cempaka',

            // SR specific info
            'nama_petugas_sr' => 'required|string|max:255',
            'tanggal_instalasi' => 'required|date|before_or_equal:today',
            'merek_mgrt' => 'required|in:GOLDCARD,GTEC,HONEYWELL,QINCHUAN,YAZAKI,ZENNER-DAS,Other',
            'nomor_seri_mgrt' => 'required|string|max:100',

            // Material tracking - all required
            'pipa_pe_20mm' => 'required|numeric|min:0|max:999.99',
            'tapping_saddle' => 'required|integer|min:0|max:999',
            'coupler_pe_20mm' => 'required|integer|min:0|max:999',
            'elbow_pe_20mm' => 'required|integer|min:0|max:999',
            'female_transition_fitting_20mm' => 'required|integer|min:0|max:999',
            'pipa_galvanis_3_4' => 'required|numeric|min:0|max:999.99',
            'ball_valve_3_4' => 'required|integer|min:0|max:999',
            'double_nipple_3_4' => 'required|integer|min:0|max:999',
            'long_elbow_3_4_male_female' => 'required|integer|min:0|max:999',
            'klem_pipa_3_4' => 'required|integer|min:0|max:999',
            'seal_tape' => 'required|integer|min:0|max:999',
            'casing_sr_pipa_galvanis_3_4' => 'nullable|numeric|min:0|max:999.99', // Optional
            'meter_gas_rumah_tangga_unit' => 'required|integer|min:0|max:999',
            'regulator_pcs' => 'required|integer|min:0|max:999',

            // Testing results
            'uji_kebocoran_passed' => 'nullable|boolean',
            'tekanan_uji' => 'nullable|numeric|min:0|max:99.99',
            'hasil_uji_kebocoran' => 'nullable|string|max:1000',

            // Additional notes
            'catatan_pemasangan' => 'nullable|string|max:1000',
        ];

        // For create (store), photos are required
        if ($this->isMethod('POST')) {
            $rules = array_merge($rules, [
                'foto_pneumatic_sr' => 'required|image|mimes:jpeg,jpg,png|max:10240',
                'foto_rangkaian_pondasi_sr' => 'required|image|mimes:jpeg,jpg,png|max:10240',
                'foto_stand_meter' => 'required|image|mimes:jpeg,jpg,png|max:10240',
                'foto_isometrik_sr' => 'required|image|mimes:jpeg,jpg,png|max:10240',
            ]);
        }

        // For update (PUT/PATCH), photos are optional
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules = array_merge($rules, [
                'foto_pneumatic_sr' => 'nullable|image|mimes:jpeg,jpg,png|max:10240',
                'foto_rangkaian_pondasi_sr' => 'nullable|image|mimes:jpeg,jpg,png|max:10240',
                'foto_stand_meter' => 'nullable|image|mimes:jpeg,jpg,png|max:10240',
                'foto_isometrik_sr' => 'nullable|image|mimes:jpeg,jpg,png|max:10240',
            ]);
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

            // SR specific messages
            'nama_petugas_sr.required' => 'Nama petugas SR harus diisi',
            'tanggal_instalasi.required' => 'Tanggal instalasi harus diisi',
            'tanggal_instalasi.date' => 'Format tanggal instalasi tidak valid',
            'tanggal_instalasi.before_or_equal' => 'Tanggal instalasi tidak boleh di masa depan',
            'merek_mgrt.required' => 'Merek MGRT harus dipilih',
            'merek_mgrt.in' => 'Merek MGRT yang dipilih tidak valid',
            'nomor_seri_mgrt.required' => 'Nomor seri MGRT harus diisi',

            // Material messages
            'pipa_pe_20mm.required' => 'Pipa PE 20mm harus diisi',
            'pipa_pe_20mm.numeric' => 'Pipa PE 20mm harus berupa angka',
            'tapping_saddle.required' => 'Tapping Saddle harus diisi',
            'tapping_saddle.integer' => 'Tapping Saddle harus berupa angka bulat',
            'coupler_pe_20mm.required' => 'Coupler PE 20mm harus diisi',
            'elbow_pe_20mm.required' => 'Elbow PE 20mm harus diisi',
            'female_transition_fitting_20mm.required' => 'Female Transition Fitting 20mm x 3/4" harus diisi',
            'pipa_galvanis_3_4.required' => 'Pipa Galvanis 3/4" harus diisi',
            'ball_valve_3_4.required' => 'Ball Valve 3/4" harus diisi',
            'double_nipple_3_4.required' => 'Double Nipple 3/4" harus diisi',
            'long_elbow_3_4_male_female.required' => 'Long Elbow 3/4" Male Female harus diisi',
            'klem_pipa_3_4.required' => 'Klem Pipa 3/4 harus diisi',
            'seal_tape.required' => 'Seal Tape harus diisi',
            'meter_gas_rumah_tangga_unit.required' => 'Meter Gas Rumah Tangga harus diisi',
            'regulator_pcs.required' => 'Regulator harus diisi',

            // Testing messages
            'tekanan_uji.numeric' => 'Tekanan uji harus berupa angka',
            'tekanan_uji.max' => 'Tekanan uji maksimal 99.99 bar',

            // Photo messages
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
            'nama_pelanggan' => 'Nama Pelanggan',
            'alamat' => 'Alamat',
            'rt' => 'RT',
            'rw' => 'RW',
            'kelurahan' => 'Kelurahan',
            'nama_petugas_sr' => 'Nama Petugas SR',
            'tanggal_instalasi' => 'Tanggal Instalasi',
            'merek_mgrt' => 'Merek MGRT',
            'nomor_seri_mgrt' => 'Nomor Seri MGRT',
            'pipa_pe_20mm' => 'Pipa PE 20mm',
            'tapping_saddle' => 'Tapping Saddle',
            'coupler_pe_20mm' => 'Coupler PE 20mm',
            'elbow_pe_20mm' => 'Elbow PE 20mm',
            'female_transition_fitting_20mm' => 'Female Transition Fitting 20mm x 3/4"',
            'pipa_galvanis_3_4' => 'Pipa Galvanis 3/4"',
            'ball_valve_3_4' => 'Ball Valve 3/4"',
            'double_nipple_3_4' => 'Double Nipple 3/4"',
            'long_elbow_3_4_male_female' => 'Long Elbow 3/4" Male Female',
            'klem_pipa_3_4' => 'Klem Pipa 3/4',
            'seal_tape' => 'Seal Tape',
            'casing_sr_pipa_galvanis_3_4' => 'Casing SR Pipa Galvanis 3/4"',
            'meter_gas_rumah_tangga_unit' => 'Meter Gas Rumah Tangga',
            'regulator_pcs' => 'Regulator',
            'uji_kebocoran_passed' => 'Uji Kebocoran',
            'tekanan_uji' => 'Tekanan Uji',
            'hasil_uji_kebocoran' => 'Hasil Uji Kebocoran',
            'catatan_pemasangan' => 'Catatan Pemasangan',
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
        $numericFields = [
            'rt', 'rw', 'pipa_pe_20mm', 'tapping_saddle', 'coupler_pe_20mm', 'elbow_pe_20mm',
            'female_transition_fitting_20mm', 'pipa_galvanis_3_4', 'ball_valve_3_4',
            'double_nipple_3_4', 'long_elbow_3_4_male_female', 'klem_pipa_3_4', 'seal_tape',
            'casing_sr_pipa_galvanis_3_4', 'meter_gas_rumah_tangga_unit', 'regulator_pcs', 'tekanan_uji'
        ];

        foreach ($numericFields as $field) {
            if ($this->has($field)) {
                $value = $this->input($field);
                $cleanData[$field] = is_numeric($value) ? $value : null;
            }
        }

        // Clean string fields
        $stringFields = ['nama_pelanggan', 'alamat', 'nama_petugas_sr', 'hasil_uji_kebocoran', 'catatan_pemasangan'];
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
            // For create operation, check if reff_id already has SR data
            if ($this->isMethod('POST') && $this->has('reff_id_pelanggan') && !$validator->errors()->has('reff_id_pelanggan')) {
                $existingSR = \App\Models\SRData::where('reff_id_pelanggan', $this->input('reff_id_pelanggan'))->first();

                if ($existingSR) {
                    $validator->errors()->add('reff_id_pelanggan', 'Data SR untuk customer ini sudah ada');
                }
            }

            // Check MGRT serial number uniqueness
            if ($this->has('nomor_seri_mgrt') && !$validator->errors()->has('nomor_seri_mgrt')) {
                $query = \App\Models\SRData::where('nomor_seri_mgrt', $this->input('nomor_seri_mgrt'));

                // For update, exclude current record
                if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
                    $srData = $this->route('srData');
                    if ($srData) {
                        $query->where('id', '!=', $srData->id);
                    }
                }

                if ($query->exists()) {
                    $validator->errors()->add('nomor_seri_mgrt', 'Nomor seri MGRT sudah digunakan');
                }
            }

            // Validate material quantities are reasonable
            $materials = [
                'pipa_pe_20mm' => 'Pipa PE 20mm',
                'tapping_saddle' => 'Tapping Saddle',
                'coupler_pe_20mm' => 'Coupler PE 20mm',
                'elbow_pe_20mm' => 'Elbow PE 20mm',
                'female_transition_fitting_20mm' => 'Female Transition Fitting',
                'pipa_galvanis_3_4' => 'Pipa Galvanis 3/4"',
                'ball_valve_3_4' => 'Ball Valve 3/4"',
                'double_nipple_3_4' => 'Double Nipple 3/4"',
                'long_elbow_3_4_male_female' => 'Long Elbow 3/4"',
                'klem_pipa_3_4' => 'Klem Pipa 3/4',
                'seal_tape' => 'Seal Tape',
                'meter_gas_rumah_tangga_unit' => 'MGRT Unit',
                'regulator_pcs' => 'Regulator'
            ];

            foreach ($materials as $field => $label) {
                $value = $this->input($field);
                if ($value !== null && $value > 100) {
                    $validator->errors()->add($field, "Jumlah {$label} terlalu besar (maksimal 100)");
                }
            }

            // Validate tanggal_instalasi is not too far in the past
            if ($this->has('tanggal_instalasi')) {
                $tanggalInstalasi = $this->input('tanggal_instalasi');
                if ($tanggalInstalasi && \Carbon\Carbon::parse($tanggalInstalasi)->diffInDays(now()) > 365) {
                    $validator->errors()->add('tanggal_instalasi', 'Tanggal instalasi terlalu lama (maksimal 1 tahun yang lalu)');
                }
            }

            // For create operation, validate all required photos are present
            if ($this->isMethod('POST')) {
                $requiredPhotos = [
                    'foto_pneumatic_sr' => 'Foto Pneumatic SR',
                    'foto_rangkaian_pondasi_sr' => 'Foto Rangkaian & Pondasi SR',
                    'foto_stand_meter' => 'Foto Stand Meter',
                    'foto_isometrik_sr' => 'Foto Isometrik SR'
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

            // Validate testing data consistency
            if ($this->has('uji_kebocoran_passed') && $this->input('uji_kebocoran_passed') && !$this->has('tekanan_uji')) {
                $validator->errors()->add('tekanan_uji', 'Tekanan uji harus diisi jika uji kebocoran lulus');
            }
        });
    }

    /**
     * Get data for material tracking integration with Gudang Data
     */
    public function getMaterialData(): array
    {
        return [
            'pipa_pe_20mm' => $this->input('pipa_pe_20mm'),
            'tapping_saddle' => $this->input('tapping_saddle'),
            'coupler_pe_20mm' => $this->input('coupler_pe_20mm'),
            'elbow_pe_20mm' => $this->input('elbow_pe_20mm'),
            'female_transition_fitting_20mm' => $this->input('female_transition_fitting_20mm'),
            'pipa_galvanis_3_4' => $this->input('pipa_galvanis_3_4'),
            'ball_valve_3_4' => $this->input('ball_valve_3_4'),
            'double_nipple_3_4' => $this->input('double_nipple_3_4'),
            'long_elbow_3_4_male_female' => $this->input('long_elbow_3_4_male_female'),
            'klem_pipa_3_4' => $this->input('klem_pipa_3_4'),
            'seal_tape' => $this->input('seal_tape'),
            'casing_sr_pipa_galvanis_3_4' => $this->input('casing_sr_pipa_galvanis_3_4'),
            'meter_gas_rumah_tangga_unit' => $this->input('meter_gas_rumah_tangga_unit'),
            'regulator_pcs' => $this->input('regulator_pcs'),
        ];
    }

    /**
     * Get photo data for processing
     */
    public function getPhotoData(): array
    {
        return [
            'foto_pneumatic_sr' => $this->file('foto_pneumatic_sr'),
            'foto_rangkaian_pondasi_sr' => $this->file('foto_rangkaian_pondasi_sr'),
            'foto_stand_meter' => $this->file('foto_stand_meter'),
            'foto_isometrik_sr' => $this->file('foto_isometrik_sr'),
        ];
    }

    /**
     * Get MGRT data for tracking
     */
    public function getMgrtData(): array
    {
        return [
            'merek_mgrt' => $this->input('merek_mgrt'),
            'nomor_seri_mgrt' => $this->input('nomor_seri_mgrt'),
            'tanggal_instalasi' => $this->input('tanggal_instalasi'),
            'nama_petugas_sr' => $this->input('nama_petugas_sr'),
        ];
    }

    /**
     * Get testing data
     */
    public function getTestingData(): array
    {
        return [
            'uji_kebocoran_passed' => $this->input('uji_kebocoran_passed'),
            'tekanan_uji' => $this->input('tekanan_uji'),
            'hasil_uji_kebocoran' => $this->input('hasil_uji_kebocoran'),
        ];
    }
}
