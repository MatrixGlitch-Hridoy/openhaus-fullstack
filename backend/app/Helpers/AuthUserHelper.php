<?php

namespace App\Helpers;

use App\Models\User;

class AuthUserHelper
{
    // get authenticated user from DB
    public function GetAuthUser()
    {
        $publicHelper = new PublicHelper();
        $token = $publicHelper->GetAndDecodeJWT();

        $userID = $token->data->userID;
        $user = User::find($userID);
        return $user;
    }
}
