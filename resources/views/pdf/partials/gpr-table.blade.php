{{--
    Partial: gpr-table.blade.php
    Variables: $operation, $operationRows, $opTotals
    Inherited from parent scope: $groupedDefects, $groupedReworks,
                                 $totalLargeDefects, $totalSmallDefects, $totalReworks
--}}

<table class="defect-table">

    <thead>
        {{-- ROW 1 --}}
        <tr>
            <th colspan="5"></th>
            <th colspan="{{ $totalLargeDefects + $totalSmallDefects + $totalReworks }}">
                TYPES OF DEFECTS
            </th>
            <th colspan="11"></th>
        </tr>

        {{-- ROW 2 --}}
        <tr>
            <th rowspan="3">mm</th>
            <th rowspan="3">dd</th>
            <th rowspan="3" class="vertical-header"><div>SHIFT</div></th>
            <th rowspan="3">PROCESS</th>
            <th rowspan="3">TOTAL QTY</th>

            <th colspan="{{ $totalLargeDefects }}">LARGE CATEGORY</th>
            <th colspan="{{ $totalSmallDefects }}">SMALL CATEGORY</th>
            <th colspan="{{ $totalReworks }}">FOR REWORK</th>

            <th rowspan="3">GOOD QTY</th>
            <th rowspan="3">NG QTY</th>
            <th rowspan="3">NG %</th>
            <th rowspan="3" class="vertical-header"><div>NQR CRITERIA (%)</div></th>
            <th rowspan="3" class="vertical-header"><div>NQR JUDGEMENT</div></th>
            <th rowspan="3" class="vertical-header"><div>Handfinisher No.</div></th>
            <th rowspan="3" class="vertical-header"><div>Visual Inspector No.</div></th>
            <th rowspan="3" class="vertical-header"><div>Feedback Receipt</div></th>
            <th rowspan="3" class="vertical-header"><div>NG Parts Status</div></th>
            <th rowspan="3" class="vertical-header"><div>GL Confirmation</div></th>
            <th rowspan="3">REMARKS</th>
        </tr>

        {{-- ROW 3 --}}
        <tr style="height: 5px;">
            @foreach($groupedDefects as $largeCategory => $items)
            <th rowspan="2" class="vertical-header">
                <div>{{ $largeCategory }}</div>
            </th>
            @endforeach

            @foreach($groupedDefects as $largeCategory => $items)
            <th colspan="{{ $items->count() }}">{{ $largeCategory }}</th>
            @endforeach

            @foreach($groupedReworks as $rework)
            <th rowspan="2" class="vertical-header">
                <div>{{ $rework['type'] }}</div>
            </th>
            @endforeach
        </tr>

        {{-- ROW 4 --}}
        <tr>
            @foreach($groupedDefects as $largeCategory => $items)
            @foreach($items as $defect)
            <th class="vertical-header small-defect-header">
                <div>{{ $defect['small_category'] ?? '' }}</div>
            </th>
            @endforeach
            @endforeach
        </tr>
    </thead>

    <tbody>

    {{-- DATA ROWS --}}
    @forelse($operationRows as $row)
    <tr>
        <td class="text-center">{{ $row['mm'] ?? '' }}</td>
        <td class="text-center">{{ $row['dd'] ?? '' }}</td>
        <td class="text-center">{{ $row['shift'] ?? '' }}</td>
        <td class="text-center">{{ $row['process'] ?? '' }}</td>
        <td class="text-center">{{ $row['total_quantity'] ?? 0 }}</td>

        @foreach($groupedDefects as $largeCategory => $items)
        @php
        $largeqty = collect($row['defects'])->first(function ($item) use ($largeCategory, $row) {
            return $item['hf_id'] === $row['hf_id']
                && $item['updated_by'] === $row['updated_by']
                && $item['large_category'] === $largeCategory
                && $item['small_category'] === null;
        });
        @endphp
        <td class="text-center">{{ $largeqty['large_qty'] ?? '' }}</td>
        @endforeach

        @foreach($groupedDefects as $largeCategory => $items)
        @foreach($items as $defect)
        @php
        $smallqty = collect($row['defects'])->first(function ($item) use ($largeCategory, $defect, $row) {
            return $item['hf_id'] === $row['hf_id']
                && $item['updated_by'] === $row['updated_by']
                && $item['large_category'] === $largeCategory
                && $item['small_category'] === $defect['small_category']
                && $item['small_category'] !== null;
        });
        @endphp
        <td class="text-center">{{ $smallqty['small_qty'] ?? '' }}</td>
        @endforeach
        @endforeach

        @foreach($groupedReworks as $groupedRework)
        @php
        $reworkQty = collect($row['reworks'])->first(function ($item) use ($groupedRework) {
            return $item['type'] === $groupedRework['type'];
        });
        @endphp
        <td class="text-center">{{ $reworkQty['qty'] ?? '' }}</td>
        @endforeach

        <td class="text-center">{{ $row['total_good_qty'] ?? 0 }}</td>
        <td class="text-center">{{ $row['total_ng_qty'] ?? 0 }}</td>
        <td class="text-center">{{ $row['ng_percent'] ?? 0 }}%</td>
        <td class="text-center">{{ $row['nqr_criteria'] ?? '' }}</td>
        <td class="text-center">{{ $row['nqr_judgement'] ?? '' }}</td>
        <td class="text-center">{{ $row['handfinisher_no'] ?? '' }}</td>
        <td class="text-center">{{ $row['visual_inspector_no'] ?? '' }}</td>
        <td class="text-center">{{ $row['feedback_receipt'] ?? '' }}</td>
        <td class="text-center">{{ $row['ng_parts_status'] ?? '' }}</td>
        <td class="text-center">{{ $row['gl_confirmation'] ?? '' }}</td>
        <td class="text-center">{{ $row['remarks'] ?? '' }}</td>
    </tr>
    @empty
    <tr>
        <td colspan="{{ 16 + $totalLargeDefects + $totalSmallDefects + $totalReworks }}" class="text-center">
            No Records Found
        </td>
    </tr>
    @endforelse

    {{-- TOTALS ROW --}}
    @if($operationRows->isNotEmpty() && !empty($opTotals))
    @php
        $totalGoodSum      = $opTotals['summary']['total_good'] ?? 0;
        $totalNgSum        = $opTotals['summary']['total_ng'] ?? 0;
        $totalDenominator  = $totalGoodSum + $totalNgSum;
        $totalNgPercent    = $totalDenominator > 0
            ? number_format(($totalNgSum / $totalDenominator) * 100, 2)
            : '0.00';
        $totalQtySum       = $totalGoodSum + $totalNgSum;
        $largeDefectTotals = collect($opTotals['large_defects'] ?? [])->keyBy('defect');
        $smallDefectTotals = collect($opTotals['small_defects'] ?? [])
            ->keyBy(fn($item) => $item['large_defect'] . '||' . $item['small_defect']);
        $reworkTotals      = collect($opTotals['reworks'] ?? [])->keyBy('rework_type');
        $lastRow           = $operationRows->last();
    @endphp
    <tr style="font-weight:bold; background-color:#f0f0f0;">
        <td class="text-center" colspan="4"><strong>TOTAL</strong></td>
        <td class="text-center">{{ $totalQtySum }}</td>

        @foreach($groupedDefects as $largeCategory => $items)
        @php $largeCatTotal = $largeDefectTotals->get($largeCategory)['total_qty'] ?? 0; @endphp
        <td class="text-center">{{ $largeCatTotal ?: '' }}</td>
        @endforeach

        @foreach($groupedDefects as $largeCategory => $items)
        @foreach($items as $defect)
        @php
            $key           = $defect['small_category'] !== null ? $largeCategory . '||' . $defect['small_category'] : null;
            $smallCatTotal = $key !== null ? ($smallDefectTotals->get($key)['total_qty'] ?? 0) : 0;
        @endphp
        <td class="text-center">{{ $smallCatTotal ?: '' }}</td>
        @endforeach
        @endforeach

        @foreach($groupedReworks as $groupedRework)
        @php $reworkTotal = $reworkTotals->get($groupedRework['type'])['total_qty'] ?? 0; @endphp
        <td class="text-center">{{ $reworkTotal ?: '' }}</td>
        @endforeach

        <td class="text-center">{{ $totalGoodSum }}</td>
        <td class="text-center">{{ $totalNgSum }}</td>
        <td class="text-center">{{ $totalNgPercent }}%</td>
        <td class="text-center">{{ $lastRow['nqr_criteria'] ?? '' }}</td>
        <td class="text-center">{{ $lastRow['nqr_judgement'] ?? '' }}</td>
        <td colspan="5"></td>
        <td class="text-center">{{ $lastRow['remarks'] ?? '' }}</td>
    </tr>
    @endif

    </tbody>

</table>