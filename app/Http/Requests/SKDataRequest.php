<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SKDataRequest extends FormRequest
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
        $rules = [
            // Customer info (auto-filled, but validate for consistency)
            'reff_id_pelanggan' => 'required|string|exists:calon_pelanggan,reff_id_pelanggan',
            'nama_pelanggan' => 'required|string|max:255',
            'alamat' => 'required|string',
            'rt' => 'nullable|integer|min:1|max:999',
            'rw' => 'nullable|integer|min:1|max:999',
            'kelurahan' => 'required|in:Malaka Jaya,Malaka Sari,Pondok Bambu,Pondok Kelapa,Pondok Kopi,Cipinang Besar Selatan,Cipinang Muara,Dukuh Makasar,Jati,Cempaka',

            // SK specific info
            'nama_petugas_sk' => 'required|string|max:255',
            'tanggal_instalasi' => 'required|date|before_or_equal:today',

            // Material tracking - all required
            'pipa_half_inch_galvanized' => 'required|numeric|min:0|max:999.99',
            'long_elbow_3_4_male_female' => 'required|integer|min:0|max:999',
            'elbow_3_4_to_half_male_female' => 'required|integer|min:0|max:999',
            'elbow_half_inch' => 'required|integer|min:0|max:999',
            'ball_valve_half_inch' => 'required|integer|min:0|max:999',
            'double_nipple_half_inch' => 'nullable|integer|min:0|max:999',
            'sock_draft_galvanis_half_inch' => 'required|integer|min:0|max:999',
            'klem_pipa_half_inch' => 'required|integer|min:0|max:999',
            'seal_tape_roll' => 'required|integer|min:0|max:999',

            // Additional notes
            'catatan_tambahan' => 'nullable|string|max:1000',
        ];

        // For create (store), photos are required
        if ($this->isMethod('POST')) {
            $rules = array_merge($rules, [
                'ba_pemasangan_sk' => 'required|image|mimes:jpeg,jpg,png|max:10240',
                'foto_pneumatic_sk' => 'required|image|mimes:jpeg,jpg,png|max:10240',
                'foto_valve_krunchis' => 'required|image|mimes:jpeg,jpg,png|max:10240',
                'foto_isometrik_sk' => 'required|image|mimes:jpeg,jpg,png|max:10240',
            ]);
        }

        // For update (PUT/PATCH), photos are optional
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules = array_merge($rules, [
                'ba_pemasangan_sk' => 'nullable|image|mimes:jpeg,jpg,png|max:10240',
                'foto_pneumatic_sk' => 'nullable|image|mimes:jpeg,jpg,png|max:10240',
                'foto_valve_krunchis' => 'nullable|image|mimes:jpeg,jpg,png|max:10240',
                'foto_isometrik_sk' => 'nullable|image|mimes:jpeg,jpg,png|max:10240',
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

            // SK specific messages
            'nama_petugas_sk.required' => 'Nama petugas SK harus diisi',
            'tanggal_instalasi.required' => 'Tanggal instalasi harus diisi',
            'tanggal_instalasi.date' => 'Format tanggal instalasi tidak valid',
            'tanggal_instalasi.before_or_equal' => 'Tanggal instalasi tidak boleh di masa depan',

            // Material messages
            'pipa_half_inch_galvanized.required' => 'Pipa 1/2" Hot Drip Galvanized harus diisi',
            'pipa_half_inch_galvanized.numeric' => 'Pipa 1/2" Hot Drip Galvanized harus berupa angka',
            'long_elbow_3_4_male_female.required' => 'Long Elbow 3/4" Male Female harus diisi',
            'long_elbow_3_4_male_female.integer' => 'Long Elbow 3/4" Male Female harus berupa angka bulat',
            'elbow_3_4_to_half_male_female.required' => 'Elbow 3/4" to 1/2" Male Female harus diisi',
            'elbow_half_inch.required' => 'Elbow 1/2" harus diisi',
            'ball_valve_half_inch.required' => 'Ball Valve 1/2" harus diisi',
            'sock_draft_galvanis_half_inch.required' => 'Sock Draft Galvanis 1/2" harus diisi',
            'klem_pipa_half_inch.required' => 'Klem Pipa 1/2 harus diisi',
            'seal_tape_roll.required' => 'Seal Tape harus diisi',

            // Photo messages
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
            'nama_pelanggan' => 'Nama Pelanggan',
            'alamat' => 'Alamat',
            'rt' => 'RT',
            'rw' => 'RW',
            'kelurahan' => 'Kelurahan',
            'nama_petugas_sk' => 'Nama Petugas SK',
            'tanggal_instalasi' => 'Tanggal Instalasi',
            'pipa_half_inch_galvanized' => 'Pipa 1/2" Hot Drip Galvanized',
            'long_elbow_3_4_male_female' => 'Long Elbow 3/4" Male Female',
            'elbow_3_4_to_half_male_female' => 'Elbow 3/4" to 1/2" Male Female',
            'elbow_half_inch' => 'Elbow 1/2"',
            'ball_valve_half_inch' => 'Ball Valve 1/2"',
            'double_nipple_half_inch' => 'Double Nipple 1/2"',
            'sock_draft_galvanis_half_inch' => 'Sock Draft Galvanis 1/2"',
            'klem_pipa_half_inch' => 'Klem Pipa 1/2',
            'seal_tape_roll' => 'Seal Tape',
            'catatan_tambahan' => 'Catatan Tambahan',
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
        // Clean up and format data
        $cleanData = [];

        // Clean reff_id_pelanggan
        if ($this->has('reff_id_pelanggan')) {
            $cleanData['reff_id_pelanggan'] = strtoupper(trim($this->input('reff_id_pelanggan')));
        }

        // Clean numeric fields
        $numericFields = [
            'rt', 'rw', 'pipa_half_inch_galvanized', 'long_elbow_3_4_male_female',
            'elbow_3_4_to_half_male_female', 'elbow_half_inch', 'ball_valve_half_inch',
            'double_nipple_half_inch', 'sock_draft_galvanis_half_inch',
            'klem_pipa_half_inch', 'seal_tape_roll'
        ];

        foreach ($numericFields as $field) {
            if ($this->has($field)) {
                $value = $this->input($field);
                $cleanData[$field] = is_numeric($value) ? $value : null;
            }
        }

        // Clean string fields
        $stringFields = ['nama_pelanggan', 'alamat', 'nama_petugas_sk', 'catatan_tambahan'];
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
            // For create operation, check if reff_id already has SK data
            if ($this->isMethod('POST') && $this->has('reff_id_pelanggan') && !$validator->errors()->has('reff_id_pelanggan')) {
                $existingSK = \App\Models\SKData::where('reff_id_pelanggan', $this->input('reff_id_pelanggan'))->first();

                if ($existingSK) {
                    $validator->errors()->add('reff_id_pelanggan', 'Data SK untuk customer ini sudah ada');
                }
            }

            // Validate material quantities are reasonable
            $materials = [
                'pipa_half_inch_galvanized' => 'Pipa 1/2"',
                'long_elbow_3_4_male_female' => 'Long Elbow 3/4"',
                'elbow_3_4_to_half_male_female' => 'Elbow 3/4" to 1/2"',
                'elbow_half_inch' => 'Elbow 1/2"',
                'ball_valve_half_inch' => 'Ball Valve 1/2"',
                'sock_draft_galvanis_half_inch' => 'Sock Draft Galvanis 1/2"',
                'klem_pipa_half_inch' => 'Klem Pipa 1/2',
                'seal_tape_roll' => 'Seal Tape'
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
                    'ba_pemasangan_sk' => 'Berita Acara Pemasangan SK',
                    'foto_pneumatic_sk' => 'Foto Pneumatic SK',
                    'foto_valve_krunchis' => 'Foto Valve Krunchis',
                    'foto_isometrik_sk' => 'Foto Isometrik SK'
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
     * Get data for material tracking integration with Gudang Data
     */
    public function getMaterialData(): array
    {
        return [
            'pipa_half_inch_galvanized' => $this->input('pipa_half_inch_galvanized'),
            'long_elbow_3_4_male_female' => $this->input('long_elbow_3_4_male_female'),
            'elbow_3_4_to_half_male_female' => $this->input('elbow_3_4_to_half_male_female'),
            'elbow_half_inch' => $this->input('elbow_half_inch'),
            'ball_valve_half_inch' => $this->input('ball_valve_half_inch'),
            'double_nipple_half_inch' => $this->input('double_nipple_half_inch'),
            'sock_draft_galvanis_half_inch' => $this->input('sock_draft_galvanis_half_inch'),
            'klem_pipa_half_inch' => $this->input('klem_pipa_half_inch'),
            'seal_tape_roll' => $this->input('seal_tape_roll'),
        ];
    }

    /**
     * Get photo data for processing
     */
    public function getPhotoData(): array
    {
        return [
            'ba_pemasangan_sk' => $this->file('ba_pemasangan_sk'),
            'foto_pneumatic_sk' => $this->file('foto_pneumatic_sk'),
            'foto_valve_krunchis' => $this->file('foto_valve_krunchis'),
            'foto_isometrik_sk' => $this->file('foto_isometrik_sk'),
        ];
    }
}
