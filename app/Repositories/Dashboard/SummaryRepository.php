<?php

namespace App\Repositories\Dashboard;

use Illuminate\Support\Facades\DB;

class SummaryRepository
{
    /* ------------------------------------------------------------------
     | VICheck (update-only)
     |------------------------------------------------------------------*/
    public function getViCheck(int $ppf): ?object
    {
        return DB::table('VICheck')
            ->select('QtyOut', 'NGQty', 'Excess', 'Lacking', 'Rework', 'Sample', 'EncoderOut', 'Plant', 'InspectionDate', 'DateOut')
            ->where('PPFNO', $ppf)
            ->first();
    }

    public function updateViCheck(int $ppf, array $data): void
    {
        DB::table('VICheck')->where('PPFNo', $ppf)->update($data);
    }

    /* ------------------------------------------------------------------
     | Upserts (add + edit share the same path)
     |------------------------------------------------------------------*/
    public function syncRework(int $ppf, array $rows): void
    {
        $this->upsertAndPrune('DefectRWK', 'PPFNo', $ppf, $rows, ['HFNo', 'Defect']);
    }

    public function syncDefects(int $ppf, array $rows): void
    {
        // Encoder / DateEncode stay as first saved; everything else is refreshed on edit.
        $this->upsertAndPrune('Defect', 'PPFNo', (float) $ppf, $rows, ['Defect'], ['DateEncode', 'Encoder']);
    }

    public function syncSmallDefects(int $ppf, array $rows): void
    {
        $this->upsertAndPrune('DefectSMALL', 'PPFNo', $ppf, $rows, ['LargeDefect', 'SmallDefect']);
    }

    public function upsertInspectionGroup(int $ppf, string $operation, string $groupName): void
    {
        DB::table('inspection_groups')->upsert(
            [['ppfno' => $ppf, 'operation' => $operation, 'group_name' => $groupName]],
            ['ppfno', 'operation'],
            ['group_name']
        );
    }

    /* ------------------------------------------------------------------
     | Stored procedure
     |------------------------------------------------------------------*/
    public function runDefectAddRoutine(int $ppf): void
    {
        DB::statement('EXEC FZA153I_DEFECT_ADD_RTN ?', [$ppf]);
    }

    /* ------------------------------------------------------------------
     | Helpers
     |------------------------------------------------------------------*/

    /**
     * Upsert $rows on ($ppfColumn + $keyCols), then delete this PPF's rows whose key is not in $rows.
     * $keepOnUpdate = columns written on insert but never overwritten on update.
     */
    private function upsertAndPrune(string $table, string $ppfColumn, mixed $ppf, array $rows, array $keyCols, array $keepOnUpdate = []): void
    {
        if ($rows) {
            $update = array_values(array_diff(array_keys($rows[0]), [$ppfColumn, ...$keyCols, ...$keepOnUpdate]));
            DB::table($table)->upsert($rows, [$ppfColumn, ...$keyCols], $update);
        }

        $incoming = array_map(fn ($r) => $this->rowKey($r, $keyCols), $rows);

        foreach (DB::table($table)->where($ppfColumn, $ppf)->get($keyCols) as $existing) {
            $existing = (array) $existing;

            if (! in_array($this->rowKey($existing, $keyCols), $incoming, true)) {
                $q = DB::table($table)->where($ppfColumn, $ppf);
                foreach ($keyCols as $c) {
                    $q->where($c, $existing[$c]);
                }
                $q->delete();
            }
        }
    }

    private function rowKey(array $row, array $cols): string
    {
        return implode("\0", array_map(fn ($c) => trim((string) ($row[$c] ?? '')), $cols));
    }
}