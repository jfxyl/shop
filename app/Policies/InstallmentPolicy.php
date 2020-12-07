<?php

namespace App\Policies;

use App\App\Models\Installment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class InstallmentPolicy
{
    use HandlesAuthorization;

    public function own(User $user,Installment $installment)
    {
        return $user->id === $installment->user_id;
    }
}
