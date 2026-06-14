<?php

namespace App\Policies;

use App\Models\DrugDictionary;
use App\Models\User;

class DrugPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, DrugDictionary $drug): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, DrugDictionary $drug): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, DrugDictionary $drug): bool
    {
        return $user->isAdmin();
    }
}
