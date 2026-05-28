<!DOCTYPE html>
<html>
@php
/** Rotates text vertically by inserting a <br> between each character. */
function vText(string $text): string {
return implode('<br>', mb_str_split($text));
}

$css = file_get_contents(resource_path('css/genpro.css'));

// Partition rows: VI rows first, then every other operation group after the totals row.
$rowsByOperation = collect($data->rows)->groupBy('operation');
$viRows = $rowsByOperation->get('VI', collect());
$hfRows = $rowsByOperation->get('HF', collect());
$otherRows = $rowsByOperation->except('VI');
$lastViRow = $viRows->last();
$lasthfRow = $hfRows->last();
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

    @include('pdf.partials._header', ['data' => $data])

    {{-- MAIN TABLE --}}
    <table class="defect-table">

        @include('pdf.partials._table-head', ['data' => $data])

        <tbody>

            {{-- Other process rows (HF, QC, etc.) — rendered after totals, grouped by operation --}}
            @foreach ($otherRows as $process => $processRows)
            @foreach ($processRows as $row)
            @include('pdf.partials._table-row', [
            'row' => $row,
            'groupedDefects' => $data->groupedDefects,
            'groupedReworks' => $data->groupedReworks,
            ])
            @endforeach
            @endforeach
            @if ($hfRows->isNotEmpty())
            @include('pdf.partials._totals-row', [
            'data' => $data,
            'lasthfRow' => $lasthfRow,
            ])
            @endif


            {{-- VI rows --}}
            @forelse ($viRows as $row)
            @include('pdf.partials._table-row', [
            'row' => $row,
            'groupedDefects' => $data->groupedDefects,
            'groupedReworks' => $data->groupedReworks,
            ])
            @empty
            <tr>
                <td colspan="{{ 16 + $data->totalLargeDefects() + $data->totalSmallDefects() + $data->totalReworks() }}"
                    class="text-center">
                    No Records Found
                </td>
            </tr>
            @endforelse

            {{-- Totals row (VI only) --}}
            @if ($viRows->isNotEmpty())
            @include('pdf.partials._totals-row', [
            'data' => $data,
            'lastViRow' => $lastViRow,
            ])
            @endif



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
                {{ $data->checkedBy }}
            </td>
        </tr>
    </table>

    <div class="footer-note">
        Note: Process record shall be confirmed by group leader every end of the shift
        either it is finished or partial lot.
    </div>

    <div class="footer-note text-right" style="font-size:7px;">
        FQCX34-D12-8
    </div>

</body>

</html>