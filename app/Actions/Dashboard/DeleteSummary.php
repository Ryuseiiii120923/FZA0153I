<?php

namespace App\Actions\Dashboard;

use App\Models\AddDefect;
use App\Models\AddRwk;
use App\Models\RejectQty;
use App\Models\SmallDef;
use App\Models\ViCheck;

class DeleteSummary
{
    public function execute(int $ppf)
    {
        $deleted = AddDefect::where('PPFNo', $ppf)->delete();

        SmallDef::where('PPFNo', $ppf)->delete();
        AddRwk::where('PPFNo', $ppf)->delete();
        RejectQty::where('ppfno', $ppf)->delete();
        ViCheck::where('PPFNO', $ppf)
            ->update([
                'QtyOut' => null,
                'NGQty' => null,
                'Excess' => null,
                'Lacking' => null,
                'Rework' => null,
                'Sample' => null,
                'EncoderOut' => null,
                'Plant' => null,
                'InspectionDate' => null,
                'Dateout' => null,
            ]);
        if ($deleted) {

            return ['success' => 'Deleted successfully!'];
        } else {
            return ['error' => 'Failed! PPF not found or already deleted.'];
        }
    }
}
