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
        @php echo $css; @endphp
        .page-break { page-break-after: always; }
    </style>
</head>

<body>

@foreach($rowsByOperation as $operation => $operationRows)
@php $isLast = $loop->last; @endphp

    @include('pdf.partials.gpr-header', [
        'record'    => $record,
        'operation' => $operation,
    ])

    @include('pdf.partials.gpr-table', [
        'operation'     => $operation,
        'operationRows' => $operationRows,
        'opTotals'      => $totalsByOperation[$operation] ?? [],
    ])

    @include('pdf.partials.gpr-footer', [
        'record' => $record,
    ])

    @if(!$isLast)
        <div class="page-break"></div>
    @endif

@endforeach

</body>
</html>