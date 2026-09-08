<?php

namespace App\Policies;

use App\Models\License;
use App\Models\User;

class LicensePolicy
{
    /**
     * Staff hanya boleh melihat — tidak boleh create/edit/suspend/reactivate apa pun.
     */
    public function viewAny(User $user): bool
    {
        return true; // semua role boleh lihat daftar license
    }

    public function view(User $user, License $license): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, License $license): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, License $license): bool
    {
        // Hapus license adalah aksi destruktif — hanya super admin
        return $user->isSuperAdmin();
    }

    /**
     * Kill-switch: suspend adalah aksi sensitif, minimal role admin.
     */
    public function suspend(User $user, License $license): bool
    {
        return $user->isAdmin();
    }

    /**
     * Reactivate juga sensitif — client yang di-reactivate salah bisa berarti
     * membuka akses untuk client yang seharusnya masih dikunci (tunggakan, dll).
     */
    public function reactivate(User $user, License $license): bool
    {
        return $user->isAdmin();
    }

    /**
     * Bulk suspend punya blast radius lebih besar — batasi ke super admin saja.
     */
    public function bulkSuspend(User $user): bool
    {
        return $user->isSuperAdmin();
    }
}
