<?php

namespace App\Services\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GetLoginService{
    public function checkLoginTime(string $loginId): array
    {

        $assignment = DB::table('LoginAssign')
            ->select('EmployeeID', 'Exempted', 'AllowedTime')
            ->where('LoginID', $loginId)
            ->first();

        if (!$assignment) {
            return [
                'success' => false,
                'message' => 'No Login Assignment! Please inform SE.',
            ];
        }


        if ($assignment->Exempted == 1) {
            return [
                'success' => true,
            ];
        }

        $result = DB::select(
            'EXEC getLoginTimewithWFH ?',
            [$assignment->EmployeeID]
        );

        if (empty($result)) {
            return [
                'success' => false,
                'message' => 'No TIME IN at DTR System.',
            ];
        }

        $login = $result[0];

        if ($login->isFinished == 1) {
            return [
                'success' => false,
                'message' => 'Employee is not present!',
            ];
        }

        if (empty($login->dtrDateTimeInFirst)) {
            return [
                'success' => false,
                'message' => 'No TIME IN at DTR System.',
            ];
        }

        $hours = Carbon::parse($login->dtrDateTimeInFirst)
            ->diffInHours(now());

        if ($hours >= $assignment->AllowedTime) {
            return [
                'success' => false,
                'message' => 'Please check your last TIME IN at DTR System.',
            ];
        }

        return [
            'success' => true,
        ];
    }
}