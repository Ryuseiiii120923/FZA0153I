<?php

namespace App\Services\Authorization;

use App\Services\Helpers\GetLoginService;
use Illuminate\Support\Facades\DB;

class AutorizationService{
    public function hasAuthority(string $userId, string $moduleId = "FZA0153I4"){
        $authorize = DB::table('SpecialAuthorization')->where('UserID', $userId)->where('ProgramID', $moduleId);

        if($authorize){
            return True;
        }else{
            return False;
        }
    }

   public function authorize(
    string $userId,
    string $password,
    string $moduleId
): array {

    $login = app(GetLoginService::class)->checkLoginTime($userId);

    if (!$login['success']) {
        return $login;
    }

    // 2. Validate username/password
    $employee = DB::table('社員')
        ->join('PASWORD', '社員.社員CD', '=', 'PASWORD.社員CD')
        ->where('社員.社員CD', $userId)
        ->where('PASWORD.PASSWORD', $password)
        ->select('社員.社員CD', '社員.名前')
        ->first();

    if (!$employee) {
        return [
            'success' => false,
            'message' => 'Invalid Username and Password combination!',
        ];
    }
    if (!$this->hasAuthority($userId, $moduleId)) {
        return [
            'success' => false,
            'message' => "Sorry! You're not allowed to use this feature!",
        ];
    }

    return [
        'success' => true,
        'employee' => $employee,
    ];
}


}