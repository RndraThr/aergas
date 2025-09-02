<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Models\CalonPelanggan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema; // <— TAMBAH INI
use Illuminate\Support\Arr;
use Carbon\Carbon;


class NotificationService
{
    /**
     * Buat satu notifikasi untuk user.
     *
     * Wajib: user_id, type, title, message
     * Opsional: priority (low|medium|high|urgent), data (array), is_read (bool), read_at (datetime)
     */

    /**
     * Kirim notifikasi ke admin/role terkait saat ada pendaftaran pelanggan baru.
     *
     * @param CalonPelanggan $customer
     * @param array{
     *   roles?: string[],        // default: ['super_admin','admin','validasi','tracer']
     *   user_ids?: int[],        // override penerima spesifik (opsional)
     *   priority?: string,       // low|medium|high|urgent
     *   type?: string,           // default: 'customer_registered'
     *   title?: string|null,     // default: otomatis
     *   message?: string|null,   // default: otomatis
     *   extra?: array            // tambahan data untuk kolom 'data'
     * } $options
     * @return int jumlah notifikasi yang dibuat
     */
    public function createNotification(array $payload): Notification
    {
        // Sanitasi & default
        $data = [
            'user_id'  => Arr::get($payload, 'user_id'),
            'type'     => trim((string) Arr::get($payload, 'type', 'general')),
            'title'    => trim((string) Arr::get($payload, 'title', '')),
            'message'  => trim((string) Arr::get($payload, 'message', '')),
            'priority' => $this->normalizePriority(Arr::get($payload, 'priority', 'medium')),
            'data'     => Arr::get($payload, 'data', []),
            'is_read'  => (bool) Arr::get($payload, 'is_read', false),
            'read_at'  => Arr::get($payload, 'read_at'),
        ];

        if (empty($data['user_id'])) {
            throw new \InvalidArgumentException('user_id wajib diisi untuk membuat notifikasi.');
        }
        if ($data['title'] === '' || $data['message'] === '') {
            throw new \InvalidArgumentException('title dan message wajib diisi untuk membuat notifikasi.');
        }

        // Normalisasi read_at
        if ($data['is_read'] && empty($data['read_at'])) {
            $data['read_at'] = now();
        }
        if (!$data['is_read']) {
            $data['read_at'] = null;
        }

        /** @var Notification $notification */
        $notification = DB::transaction(function () use ($data) {
            return Notification::create($data);
        });

        // Optional: kirim ke channel lain (broadcast/telegram/email) di sini jika diperlukan

        return $notification->refresh();
    }

    /**
     * Shorthand method for backward compatibility
     */
    public function create(string $type, string $title, string $message, array $data = [], string $priority = 'medium'): int
    {
        // Send to all admins and tracers by default
        return $this->sendToRoles(
            ['admin', 'super_admin', 'tracer'],
            $type,
            $title,
            $message,
            $priority,
            $data
        );
    }

    public function notifyNewCustomerRegistration(CalonPelanggan $customer, array $options = []): int
    {
        $type     = $options['type']     ?? 'customer_registered';
        $priority = $this->normalizePriority($options['priority'] ?? 'medium');

        $title = $options['title'] ?? 'Pendaftaran Calon Pelanggan Baru';
        $message = $options['message'] ?? sprintf(
            'Pelanggan %s (%s) terdaftar dan menunggu proses validasi.',
            $customer->nama_pelanggan ?? '-',
            $customer->reff_id_pelanggan ?? '-'
        );

        // Tentukan penerima
        $recipients = collect();

        if (!empty($options['user_ids']) && is_array($options['user_ids'])) {
            $recipients = User::query()
                ->whereIn('id', $options['user_ids'])
                ->get(['id']);
        } else {
            $roles = $options['roles'] ?? ['super_admin','admin','validasi','tracer'];
            $recipients = $this->getUsersByRoles($roles);
        }

        if ($recipients->isEmpty()) {
            return 0;
        }

        $now = now();
        $rows = $recipients->map(function ($u) use ($customer, $type, $title, $message, $priority, $options, $now) {
            return [
                'user_id'   => $u->id,
                'type'      => $type,
                'title'     => $title,
                'message'   => $message,
                'priority'  => $priority,
                'data'      => [
                    'reff_id_pelanggan' => $customer->reff_id_pelanggan,
                    'nama_pelanggan'    => $customer->nama_pelanggan,
                    'wilayah_area'      => $customer->wilayah_area ?? null,
                    'progress_status'   => $customer->progress_status ?? null,
                    'status'            => $customer->status ?? null,
                    'link' => $this->customerLink($customer->reff_id_pelanggan),
                    'extra'             => $options['extra'] ?? [],
                ],
                'is_read'   => false,
                'read_at'   => null,
                'created_at'=> $now,
                'updated_at'=> $now,
            ];
        })->all();

        // Bulk insert untuk efisiensi
        Notification::insert($rows);

        return count($rows);
    }

    /**
     * Tandai 1 notifikasi user sebagai dibaca.
     *
     * @return bool true jika sukses/perubahan terjadi, false jika notifikasi tidak ditemukan (milik user tsb).
     */
    public function markAsRead(int $notificationId, int $userId): bool
    {
        /** @var Notification|null $n */
        $n = Notification::where('user_id', $userId)->find($notificationId);
        if (!$n) {
            return false;
        }

        if ($n->is_read) {
            return true; // sudah dibaca, anggap sukses
        }

        $n->is_read = true;
        $n->read_at = now();
        $n->save();

        return true;
    }

    // Tambahan di NotificationService (DI DALAM class)

    public function notifyTracerPhotoPending(string $reffId, string $module): int
    {
        $cust = CalonPelanggan::find($reffId);
        $title = 'Foto menunggu review Tracer';
        $message = sprintf(
            'Modul %s untuk %s (%s) siap ditinjau Tracer.',
            strtoupper($module),
            $cust->nama_pelanggan ?? '-',
            $reffId
        );

        return $this->sendToRoles(
            ['tracer','validasi','admin','super_admin'],
            'photo_tracer_pending',
            $title,
            $message,
            'medium',
            [
                'reff_id_pelanggan' => $reffId,
                'module' => $module,
                'link' => $this->customerLink($reffId),
            ]
        );
    }

    public function notifyAdminCgpReview(string $reffId, string $module): int
    {
        $cust = CalonPelanggan::find($reffId);
        $title = 'Foto menunggu review CGP';
        $message = sprintf(
            'Modul %s untuk %s (%s) menunggu persetujuan CGP.',
            strtoupper($module),
            $cust->nama_pelanggan ?? '-',
            $reffId
        );

        return $this->sendToRoles(
            ['admin','super_admin'],
            'photo_cgp_pending',
            $title,
            $message,
            'high',
            [
                'reff_id_pelanggan' => $reffId,
                'module' => $module,
                'link' => $this->customerLink($reffId),
            ]
        );
    }

    public function notifyPhotoApproved(string $reffId, string $module, string $photoField): int
    {
        $cust = CalonPelanggan::find($reffId);
        $title = 'Foto disetujui';
        $message = sprintf(
            'Foto "%s" pada modul %s untuk %s (%s) telah disetujui.',
            $photoField,
            strtoupper($module),
            $cust->nama_pelanggan ?? '-',
            $reffId
        );

        // kirim ke tracer & admin
        return $this->sendToRoles(
            ['tracer','admin','super_admin'],
            'photo_approved',
            $title,
            $message,
            'medium',
            [
                'reff_id_pelanggan' => $reffId,
                'module' => $module,
                'photo_field' => $photoField,
                'link' => $this->customerLink($reffId),
            ]
        );
    }

    public function notifyPhotoRejection(string $reffId, string $module, string $photoField, string $reason): int
    {
        $cust = CalonPelanggan::find($reffId);
        $title = 'Foto ditolak';
        $message = sprintf(
            'Foto "%s" pada modul %s untuk %s (%s) ditolak. Alasan: %s',
            $photoField,
            strtoupper($module),
            $cust->nama_pelanggan ?? '-',
            $reffId,
            $reason
        );

        // utamakan tim lapangan (tracer) & admin
        return $this->sendToRoles(
            ['tracer','admin','super_admin'],
            'photo_rejected',
            $title,
            $message,
            'high',
            [
                'reff_id_pelanggan' => $reffId,
                'module' => $module,
                'photo_field' => $photoField,
                'reason' => $reason,
                'link' => $this->customerLink($reffId),
            ]
        );
    }

    /**
     * Batch proses approve/reject berdasarkan action.
     *
     * @param int[]        $photoIds
     * @param 'tracer_approve'|'tracer_reject'|'cgp_approve'|'cgp_reject' $action
     * @param int          $actorUserId
     * @param array{notes?:?string,reason?:?string} $payload
     * @return array{total:int,successful:int,failed:int,results:array<int,array{id:int,success:bool,message:string}>}
     */


    public function notifyModuleCompletion(string $reffId, string $module): int
    {
        $cust = CalonPelanggan::find($reffId);
        $title = 'Modul selesai';
        $message = sprintf(
            'Modul %s untuk %s (%s) selesai. Lanjutkan ke tahap berikutnya.',
            strtoupper($module),
            $cust->nama_pelanggan ?? '-',
            $reffId
        );

        return $this->sendToRoles(
            ['admin','super_admin','validasi','tracer'],
            'module_completed',
            $title,
            $message,
            'medium',
            [
                'reff_id_pelanggan' => $reffId,
                'module' => $module,
                'link' => $this->customerLink($reffId),
            ]
        );
    }




    protected function getUsersByRoles(array $roles): \Illuminate\Support\Collection
    {
        // Jika pakai Spatie (akan berhasil bila trait HasRoles dipasang di User)
        if (method_exists(User::class, 'role')) {
            return User::role($roles)->get(['id']);
        }

        // Fallback: kolom 'role' di tabel users
        $q = User::query();

        if (Schema::hasColumn('users', 'role')) { // <— GANTI KE FACADE
            $q->whereIn('role', $roles);
        }

        return $q->get(['id']);
    }

    /**
     * Tandai semua notifikasi user sebagai dibaca.
     *
     * @return int jumlah notifikasi yang ditandai dibaca
     */
    public function markAllAsRead(int $userId): int
    {
        return Notification::where('user_id', $userId)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    /**
     * Statistik ringkas notifikasi user.
     *
     * @return array{
     *   total:int,
     *   unread:int,
     *   read:int,
     *   last_7_days:int,
     *   latest_at:?Carbon,
     *   latest_unread_at:?Carbon
     * }
     */
    public function getUserNotificationStats(int $userId): array
    {
        $base = Notification::where('user_id', $userId);

        $total   = (clone $base)->count();
        $unread  = (clone $base)->where('is_read', false)->count();
        $read    = $total - $unread;
        $last7   = (clone $base)->where('created_at', '>=', now()->subDays(7))->count();
        $latest  = (clone $base)->max('created_at');
        $latestUnread = (clone $base)->where('is_read', false)->max('created_at');

        return [
            'total'            => (int) $total,
            'unread'           => (int) $unread,
            'read'             => (int) $read,
            'last_7_days'      => (int) $last7,
            'latest_at'        => $latest ? Carbon::parse($latest) : null,
            'latest_unread_at' => $latestUnread ? Carbon::parse($latestUnread) : null,
        ];
    }

    /* ========================= Helpers ========================= */

    private function normalizePriority(?string $priority): string
    {
        $allowed = ['low','medium','high','urgent'];
        $p = strtolower((string) $priority);
        return in_array($p, $allowed, true) ? $p : 'medium';
    }
    private function sendToRoles(array $roles, string $type, string $title, string $message, string $priority = 'medium', array $data = []): int
    {
        $recipients = $this->getUsersByRoles($roles);
        if ($recipients->isEmpty()) {
            return 0;
        }

        $count = 0;
        
        // Use individual creates to ensure proper JSON casting
        // For better performance in production, consider using a queue
        foreach ($recipients as $user) {
            try {
                Notification::create([
                    'user_id'   => $user->id,
                    'type'      => $type,
                    'title'     => $title,
                    'message'   => $message,
                    'priority'  => $this->normalizePriority($priority),
                    'data'      => $data, // Will be automatically cast to JSON
                    'is_read'   => false,
                    'read_at'   => null,
                ]);
                $count++;
            } catch (\Exception $e) {
                Log::error('Failed to create notification', [
                    'user_id' => $user->id,
                    'type' => $type,
                    'error' => $e->getMessage()
                ]);
                // Continue with other notifications
            }
        }
        
        return $count;
    }

    private function customerLink(?string $reff): string
    {
        try {
            if ($reff && app('router')->has('customers.show')) {
                return route('customers.show', $reff);
            }
        } catch (\Throwable $e) {
            // ignore
        }
        return $reff ? url('/customers/'.$reff) : url('/customers');
    }

}
