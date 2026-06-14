<?php

namespace App\Policies;

use App\Models\Prescription;
use App\Models\User;

class PrescriptionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Prescription $prescription): bool
    {
        return $user->isAdmin() || $prescription->uploaded_by === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function review(User $user, Prescription $prescription): bool
    {
        return $user->isAdmin() || $prescription->uploaded_by === $user->id;
    }

    public function delete(User $user, Prescription $prescription): bool
    {
        return $user->isAdmin();
    }
}
