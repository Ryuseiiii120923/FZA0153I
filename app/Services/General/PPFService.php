<?php

namespace App\Services\General;

use App\Repositories\General\InitialDataRepository;
use App\Repositories\General\PPFRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PPFService
{

    public function __construct(protected PPFRepository $ppfRepo, protected InitialDataRepository $initialDataRepository) {}

    private const FIFO_EXEMPT_PARTS = [
        '166753-0011',
        '1293450010',
        'M256C09101D',
        '166997-0011',
    ];

    /**
     * Whether a part number is exempt from the FIFO encoding-order check.
     */
    public function isFifoExempt(?string $partno): bool
    {
        return in_array($partno, self::FIFO_EXEMPT_PARTS, true);
    }

    /**
     * Whether a PPF is locked because it has already been encoded to
     * Final Inspection (and is not currently flagged for re-inspection).
     * Was previously a raw DB::table() query inside Checkppf.php.
     */
   

    /**
     * Compares the actual inspected total against the expected quantity
     * and returns how much it was over ('excess') or short ('lack').
     * Was previously duplicated verbatim in Checkppf::AcceptTotal() and
     * Checkppf::RejectTotal().
     *
     * @return array{excess: int, lack: int}
     */
    public function calculateQtyVariance(int|string|null $totalInspected, int|string|null $expected): array
    {
        $totalInspected = (int) $totalInspected;
        $expected = (int) $expected;

        return [
            'excess' => max(0, $totalInspected - $expected),
            'lack'   => max(0, $expected - $totalInspected),
        ];
    }
}
