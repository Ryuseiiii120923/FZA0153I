{{--
    Partial: pdf/partials/_table-row.blade.php
    Props:
      $row            (array)                          — normalised row data
      $groupedDefects (Collection<string, Collection>) — column definitions
      $groupedReworks (Collection<array{type:string}>) — rework column definitions

    group-start      → border-left:  3px double #000  (first large-category cell)
    group-separator  → border-right: 3px double #000  (last large / last small cell)
    group-end        → border-right: 3px double #000  (last rework cell)
--}}
<tr>
    <td class="text-center">{{ $row['mm']             ?? '' }}</td>
    <td class="text-center">{{ $row['dd']             ?? '' }}</td>
    <td class="text-center">{{ $row['shift']          ?? '' }}</td>
    <td class="text-center">{{ $row['process']        ?? '' }}</td>
    <td class="text-center">{{ $row['total_quantity'] ?? 0  }}</td>

    {{-- Large-category defect quantities --}}
    @foreach ($groupedDefects as $largeCategory => $items)
    @php
        $largeEntry = collect($row['defects'])->first(
            fn($d) => $d['hf_id']         === $row['hf_id']
                   && $d['updated_by']     === $row['updated_by']
                   && $d['large_category'] === $largeCategory
                   && $d['small_category'] === null
        );
    @endphp
    <td class="text-center {{ $loop->first ? 'group-start' : '' }} {{ $loop->last ? 'group-separator' : '' }}">
        {{ $largeEntry['large_qty'] ?? '' }}
    </td>
    @endforeach

    {{-- Small-category defect quantities --}}
    @foreach ($groupedDefects as $largeCategory => $items)
    @foreach ($items as $defect)
    @php
        $smallEntry = collect($row['defects'])->first(
            fn($d) => $d['hf_id']         === $row['hf_id']
                   && $d['updated_by']     === $row['updated_by']
                   && $d['large_category'] === $largeCategory
                   && $d['small_category'] === $defect['small_category']
                   && $d['small_category'] !== null
        );
    @endphp
    <td class="text-center {{ $loop->parent->last && $loop->last ? 'group-separator' : '' }}">
        {{ $smallEntry['small_qty'] ?? '' }}
    </td>
    @endforeach
    @endforeach

    {{-- Rework quantities --}}
    @foreach ($groupedReworks as $groupedRework)
    @php
        $reworkEntry = collect($row['reworks'])->first(
            fn($r) => $r['type'] === $groupedRework['type']
        );
    @endphp
    <td class="text-center {{ $loop->last ? 'group-end' : '' }}">
        {{ $reworkEntry['qty'] ?? '' }}
    </td>
    @endforeach

    <td class="text-center">{{ $row['total_good_qty']      ?? 0  }}</td>
    <td class="text-center">{{ $row['total_ng_qty']        ?? 0  }}</td>
    <td class="text-center">{{ $row['ng_percent']          ?? 0  }}%</td>
    <td class="text-center"></td>
    <td class="text-center"></td>
    <td class="text-center">{{ $row['handfinisher_no']     ?? '' }}</td>
    <td class="text-center">{{ $row['visual_inspector_no'] ?? '' }}</td>
    <td class="text-center">{{ $row['feedback_receipt']    ?? '' }}</td>
    <td class="text-center">{{ $row['ng_parts_status']     ?? '' }}</td>
    <td class="text-center">{{ $row['gl_confirmation']     ?? '' }}</td>
    <td class="text-center">{{ $row['remarks']             ?? '' }}</td>
</tr>