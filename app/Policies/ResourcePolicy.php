<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ResourcePolicy
{
    use HandlesAuthorization;

    public function before(User $user, $ability)
    {
        if ($user->role->name == 'administrator') {
            return true;
        }
    }

    public function view(User $user)
    {
        return in_array($user->role->name, ['accountant', 'reception', 'user']);
    }

    public function create(User $user)
    {
        return in_array($user->role->name, ['reception']);
    }

    public function update(User $user)
    {
        return in_array($user->role->name, ['reception']);
    }

    public function delete(User $user)
    {
        return in_array($user->role->name, []);
    }
}
