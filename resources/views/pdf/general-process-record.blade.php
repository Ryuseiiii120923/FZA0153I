<!DOCTYPE html>
<html>
@php
function vText(string $text): string {
return implode('<br>', mb_str_split($text));
}
$css = file_get_contents(resource_path('css/genpro.css'));
@endphp

<head>
    <meta charset="utf-8">
    <title>General Process Record</title>
    <style>
        @php echo $css;
        @endphp
    </style>
</head>

<body>

    <table style="width:100%; border-collapse:collapse; border:none;">
        <tr>
            <!-- LEFT -->
            <td style="width:35%; border:none; vertical-align:top; font-size:10px;">
                <table style="width:75%; border:none;">
                    <tr>
                        <td style="border:none; width:45%;"><strong>PPFNO.:</strong></td>
                        <td style="border:none; border-bottom:1px solid #000;">{{ $record['ppf_no'] ?? '' }}</td>
                    </tr>
                    <tr>
                        <td style="border:none; width:45%;"><strong>LOT NO.:</strong></td>
                        <td style="border:none; border-bottom:1px solid #000;">{{ $record['lot_no'] ?? '' }}</td>
                    </tr>
                    <tr>
                        <td style="border:none; width:45%;"><strong>MIXING LOT NO.:</strong></td>
                        <td style="border:none; border-bottom:1px solid #000;">{{ $record['mixing_lot_no'] ?? '' }}</td>
                    </tr>
                    <tr>
                        <td style="border:none; width:45%;"><strong>PART NUMBER:</strong></td>
                        <td style="border:none; border-bottom:1px solid #000;">{{ $record['part_number'] ?? '' }}</td>
                    </tr>
                    <tr>
                        <td style="border:none;"><strong>MOLDING DIE NUMBER:</strong></td>
                        <td style="border:none; border-bottom:1px solid #000;">{{ $record['molding_die_number'] ?? '' }}</td>
                    </tr>
                    <tr>
                        <td style="border:none;"><strong>MACHINE NUMBER:</strong></td>
                        <td style="border:none; border-bottom:1px solid #000; text-align:left;">{{ $record['machine_number'] ?? '' }}</td>
                    </tr>
                </table>
            </td>

            <!-- CENTER -->
            <td style="width:30%; border:none; text-align:center; vertical-align:middle;">
                <div style="font-size:16px; font-weight:bold;">GENERAL PROCESS RECORD</div>
            </td>

            <!-- RIGHT -->
            <td style="width:35%; border:none; vertical-align:top; font-size:10px;">
                <table style="width:100%; border:none;">
                    <tr>
                        <td style="border:none; width:40%;"><strong>MONTH/YEAR:</strong></td>
                        <td style="border:none; border-bottom:1px solid #000;">{{ $record['month_year'] ?? '' }}</td>
                    </tr>
                    <tr>
                        <td style="border:none;"><strong>INSPECTION GROUP:</strong></td>
                        <td style="border:none; border-bottom:1px solid #000;">{{ $record['inspection_group'] ?? '' }}</td>
                    </tr>
                </table>
                <div class="right-options">
                    <div class="check-group" style="margin-top: 10px;">
                        <span class="check-box {{ $record['no_vi_check'] ? 'filled' : '' }}"></span>
                        NO check if 100% VI
                    </div>
                    <div class="check-group">
                        <span class="check-box {{ $record['vi_good'] ? 'filled' : '' }}"></span>
                        Check if 200% VI of GOOD parts
                    </div>
                    <div class="check-group">
                        <span class="check-box {{ $record['vi_ng'] ? 'filled' : '' }}"></span>
                        Check if 200% VI of NG parts
                    </div>
                    <div class="check-group">
                        <span class="check-box {{ $record['rework'] ? 'filled' : '' }}"></span>
                        Check if REWORK
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <br>

    <div class="top-options">
        <div class="left-options">
            <div class="check-group">
                <span class="check-box {{ $record['is_silicon'] ? 'filled' : '' }}"></span>
                <strong>silicon parts</strong>
                &nbsp;&nbsp;&nbsp;&nbsp;
                <span class="check-box {{ $record['is_silicon'] ? '' : 'filled'}}"></span>
                <strong>non-silicon parts</strong>
            </div>
        </div>
        <div class="clearfix"></div>
    </div>

    <br>

    {{-- MAIN TABLE --}}
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
                <th rowspan="3" class="vertical-header">
                    <div>SHIFT</div>
                </th>
                <th rowspan="3">PROCESS</th>
                <th rowspan="3">TOTAL QTY</th>

                <th colspan="{{ $totalLargeDefects }}">LARGE CATEGORY</th>
                <th colspan="{{ $totalSmallDefects }}">SMALL CATEGORY</th>
                <th colspan="{{ $totalReworks }}">FOR REWORK</th>

                <th rowspan="3">GOOD QTY</th>
                <th rowspan="3">NG QTY</th>
                <th rowspan="3">NG %</th>
                <th rowspan="3" class="vertical-header">
                    <div>NQR CRITERIA (%)</div>
                </th>
                <th rowspan="3" class="vertical-header">
                    <div>NQR JUDGEMENT</div>
                </th>
                <th rowspan="3" class="vertical-header">
                    <div>Handfinisher No.</div>
                </th>
                <th rowspan="3" class="vertical-header">
                    <div>Visual Inspector No.</div>
                </th>
                <th rowspan="3" class="vertical-header">
                    <div>Feedback Receipt</div>
                </th>
                <th rowspan="3" class="vertical-header">
                    <div>NG Parts Status</div>
                </th>
                <th rowspan="3" class="vertical-header">
                    <div>GL Confirmation</div>
                </th>
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
                    <div>{{ $defect['small_category'] }}</div>
                </th>
                @endforeach
                @endforeach
            </tr>
        </thead>

        <tbody>

            @forelse($rows as $row)
            <tr>
                <td class="text-center">{{ $row['mm'] ?? '' }}</td>
                <td class="text-center">{{ $row['dd'] ?? '' }}</td>
                <td class="text-center">{{ $row['shift'] ?? '' }}</td>
                <td class="text-center">{{ $row['process'] ?? '' }}</td>
                <td class="text-center">{{ $row['total_quantity'] ?? 0 }}</td>

                {{-- LARGE CATEGORY VALUES --}}
                @foreach($groupedDefects as $largeCategory => $items)
                @php
                $largeqty = collect($row['defects'])
                ->first(function ($item) use ($largeCategory, $row) {
                return $item['hf_id'] === $row['hf_id']
                && $item['large_category'] === $largeCategory;
                });
                @endphp
                <td class="text-center">{{ $largeqty['large_qty'] ?? '' }}</td>
                @endforeach

                {{-- SMALL CATEGORY VALUES --}}
                @foreach($groupedDefects as $largeCategory => $items)
                @foreach($items as $defect)
                @php
                // Use ->first() not ->sum() so duplicate small_category names
                // (e.g. Cut under Cut) each show their own qty, not a combined total
                $smallqty = collect($row['defects'])
                ->first(function ($item) use ($largeCategory, $defect, $row) {
                return $item['hf_id'] === $row['hf_id']
                && $item['large_category'] === $largeCategory
                && $item['small_category'] === $defect['small_category'];
                });
                @endphp
                <td class="text-center">{{ $smallqty['small_qty'] ?? '' }}</td>
                @endforeach
                @endforeach

                {{-- REWORK --}}
                @foreach($groupedReworks as $groupedRework)
                @php
                $reworkQty = collect($row['reworks'])
                    ->first(function ($item) use ($groupedRework) {
                        return $item['type'] === $groupedRework['type'];
                    });
                @endphp
                <td class="text-center">{{ $reworkQty['qty'] ?? '' }}</td>
                @endforeach

                <td class="text-center">{{ $row['total_good_qty'] ?? 0 }}</td>
                <td class="text-center">{{ $row['total_ng_qty'] ?? 0 }}</td>
                <td class="text-center">{{ $row['ng_percent'] ?? 0 }}%</td>
                <td class="text-center">{{ $row['nor_criteria'] ?? '' }}</td>
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
                <td colspan="{{ 16 + $totalLargeDefects + $totalSmallDefects + $totalReworks }}"
                    class="text-center">
                    No Records Found
                </td>
            </tr>
            @endforelse
        </tbody>

    </table>

    <br>

    {{-- FOOTER --}}
    <table>
        <tr>
            <td width="70%">
                <strong>Legend:</strong>
                E - Excess Quantity &nbsp;&nbsp;
                M - Missing Quantity
            </td>
            <td width="30%">
                <strong>Checked (Head/Staff):</strong>
                {{ $record['checked_by'] ?? '' }}
            </td>
        </tr>
    </table>

    <div class="footer-note">
        Note: Process record shall be confirmed by group leader every end of the shift either it is finished or partial lot.
    </div>

</body>

</html>