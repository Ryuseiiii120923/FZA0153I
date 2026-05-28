{{--
    Partial: pdf/partials/_totals-row.blade.php
    Props:
      $data           (GeneralProcessRecordData)
      $lastViRow      (array|null)  — last VI row, for NQR display
--}}
@php
    $totalGood  = $data->totals['summary']['total_good'] ?? 0;
    $totalNg    = $data->totals['summary']['total_ng']   ?? 0;
    $denominator = $totalGood + $totalNg;
    $totalNgPct = $denominator > 0
        ? number_format(($totalNg / $denominator) * 100, 2)
        : '0.00';

    $largeDefectTotals = collect($data->totals['large_defects'])->keyBy('defect');
    $smallDefectTotals = collect($data->totals['small_defects'])
        ->keyBy(fn($item) => $item['large_defect'] . '||' . $item['small_defect']);
    $reworkTotals = collect($data->totals['reworks'])->keyBy('rework_type');
@endphp

<tr style="font-weight:bold; background-color:#f0f0f0;">
    <td class="text-center" colspan="4"><strong>TOTAL</strong></td>
    <td class="text-center">{{ $data->totals['total_qty'] ?? 0 }}</td>

    {{-- Large-category totals --}}
    @foreach ($data->groupedDefects as $largeCategory => $items)
    @php $largeCatTotal = $largeDefectTotals->get($largeCategory)['total_qty'] ?? 0; @endphp
    <td class="text-center">{{ $largeCatTotal ?: '' }}</td>
    @endforeach

    {{-- Small-category totals --}}
    @foreach ($data->groupedDefects as $largeCategory => $items)
    @foreach ($items as $defect)
    @php
        $key           = $defect['small_category'] !== null
            ? $largeCategory . '||' . $defect['small_category']
            : null;
        $smallCatTotal = $key !== null ? ($smallDefectTotals->get($key)['total_qty'] ?? 0) : 0;
    @endphp
    <td class="text-center">{{ $smallCatTotal ?: '' }}</td>
    @endforeach
    @endforeach

    {{-- Rework totals --}}
    @foreach ($data->groupedReworks as $groupedRework)
    @php $reworkTotal = $reworkTotals->get($groupedRework['type'])['total_qty'] ?? 0; @endphp
    <td class="text-center">{{ $reworkTotal ?: '' }}</td>
    @endforeach

    <td class="text-center">{{ $totalGood }}</td>
    <td class="text-center">{{ $totalNg }}</td>
    <td class="text-center">{{ $totalNgPct }}%</td>
    <td class="text-center">{{ $row['nqr_criteria']        ?? '' }}</td>
    <td class="text-center">{{ $lastViRow['nqr_judgement'] ?? '' }}</td>
    <td colspan="5"></td>
    <td class="text-center">{{ $data->totals['remarks'] ?? '' }}</td>
</tr>
