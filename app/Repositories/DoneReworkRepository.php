<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class DoneReworkRepository
{
      protected $workerRepo;
     public function __construct(WorkerRepository $workerRepo)
    {
      $this->workerRepo = $workerRepo;
    }
    public function saveMainForm(array $data)
    {
        try {
            $exists = DB::table('dr_forms')
                ->where('ppfno', $data['ppfno'])
                ->exists();

            if ($exists) {
                throw new \Exception("PPF already exists: " . $data['ppfno']);
            }
            return DB::table('dr_forms')->insertGetId([
                'hf_id' => $data['hf_id'],
                'total_inspect' => $data['total_inspect'],
                'created_at' => now(),
                'updated_by' => $data['encoder'],
                'ppfno' => $data['ppfno'],
                'GoodQty' => $data['goodQty'],
            ]);
        } catch (\Throwable $e) {
            throw new \Exception("Failed to save DR form: " . $e->getMessage());
        }
    }

    public function saveDefects(int $hfId, array $defects, int $ppfno, string $encoder)
    {
        try {
             $exists = DB::table('dr_defect')
                ->where('ppfno', $ppfno)
                ->exists();

            if ($exists) {
                throw new \Exception("PPF already exists: " . $ppfno);
            }
            foreach ($defects as $defect) {
                if (empty($defect['type'])) {
                    throw new \Exception("Defect type cannot be empty.");
                }
                DB::table('dr_defect')->insert([
                    'hf_id' => $hfId,
                    'defect' => $defect['type'],
                    'qty' => $defect['qty'],
                    'updated_by' => $encoder,
                    'ppfno' => $ppfno,
                ]);

                DB::table('Inspector_Defect')->insert([
                    'PPFNo' => $ppfno,
                    'InspectorID' => $hfId,
                    'insp_name' => $this->workerRepo->getWorkerName($hfId)->名前 ?? 'Unknown',
                    'Defect' => $defect['type'],
                    'Quantity' => $defect['qty'],
                    'DateEncode' => now(),
                    'Process' => 'HF',
                    'EncodeProcess' => 'reRework',
                ]);
            }
        } catch (\Throwable $e) {
            throw new \Exception("Failed to save defects: " . $e->getMessage());
        }
    }

    public function saveReworks(int $hfId, array $reworks, string $ppfno, string $encoder)
    {
        try {
             $exists = DB::table('dr_rework')
                ->where('ppfno', $ppfno)
                ->exists();

            if ($exists) {
                throw new \Exception("PPF already exists: " . $ppfno);
            }
            foreach ($reworks as $rework) {
                if (empty($rework['type'])) {
                    throw new \Exception("Rework type cannot be empty.");
                }
                DB::table('dr_rework')->insert([
                    'hf_id' => $hfId,
                    'rework_type' => $rework['type'],
                    'qty' => $rework['quan'],
                    'updated_by' => $encoder,
                    'ppfno' => $ppfno,
                    'hfno' => $rework['hfno'],
                    'totalinsp' => $rework['totalinsp'],
                ]);
            }
        } catch (\Throwable $e) {
            throw new \Exception("Failed to save reworks: " . $e->getMessage());
        }
    }

    public function saveSmallDefects(int $hfId, array $smalldefects, string $ppfno, string $encoder)
    {
        try {
             $exists = DB::table('dr_small')
                ->where('ppfno', $ppfno)
                ->exists();

            if ($exists) {
                throw new \Exception("PPF already exists: " . $ppfno);
            }
            foreach ($smalldefects as $large => $smalls) {
                foreach ($smalls as $small) {
                    if (empty($small['type'])) {
                        throw new \Exception("Small defect type for {$large} cannot be empty.");
                    }
                    DB::table('dr_small')->insert([
                        'hf_id' => $hfId,
                        'large_defect' => $large,
                        'small_defect' => $small['type'],
                        'qty' => $small['qty'],
                        'updated_by' => $encoder,
                        'ppfno' => $ppfno,
                    ]);

                    DB::table('Inspector_SmallDefect')->insert([
                        'PPFNo' => $ppfno,
                        'InspectorID' => $encoder,
                        'LargeDefect' => $large,
                        'SmallDefect' => $small['type'],
                        'Quantity' => $small['qty'],
                        'DateEncode' => now(),
                        'Process' => 'HF',
                        'EncodeProcess' => 'reRework',
                    ]);
                }
            }
        } catch (\Throwable $e) {
            throw new \Exception("Failed to save small defects: " . $e->getMessage());
        }
    }

    public function updateFlag($ppf)
    {
        try {
            Db::table('hf_rework')
                ->where('ppfno', $ppf)
                ->update([
                    'FlgDone' => 1
                ]);
        } catch (\Throwable $e) {
            throw new \Exception("Update to update Flag: " . $e->getMessage());
        }
    }

    public function fetchFlag($ppf){
        try{
           $flgDone =  DB::table('hf_rework')
            ->where('PPFNo', $ppf)
             ->value('FlgDone');

             $proceedToRework = DB::table('hf_rework')
             ->where('PPFNo', $ppf)
                ->value('ProceedToRework');

            return [
                'FlgDone' => (bool) $flgDone,
                'ProceedToRework' => (bool) $proceedToRework
            ];
        }catch (\Throwable $e){
            throw new \Exception("Failed to Fetch: " . $e->getMessage());
        }
    }
}
