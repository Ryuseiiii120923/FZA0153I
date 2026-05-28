{{--
    Partial: pdf/partials/_header.blade.php
    Props: $data (GeneralProcessRecordData)
--}}
<table style="width:100%; border-collapse:collapse; border:none;">
    <tr>
        {{-- LEFT: record meta --}}
        <td style="width:35%; border:none; vertical-align:top; font-size:10px;">
            <table style="width:75%; border:none;">
                @foreach ([
                    'PPFNO.'         => $data->ppfNo,
                    'LOT NO.'        => $data->lotNo,
                    'MIXING LOT NO.' => $data->mixingLotNo,
                    'PART NUMBER'    => $data->partNumber,
                    'MOLDING DIE NUMBER' => $data->moldingDieNumber,
                    'MACHINE NUMBER' => $data->machineNumber,
                ] as $label => $value)
                <tr>
                    <td style="border:none; width:45%;"><strong>{{ $label }}:</strong></td>
                    <td style="border:none; border-bottom:1px solid #000;">{{ $value }}</td>
                </tr>
                @endforeach
            </table>
        </td>

        {{-- CENTER: title --}}
        <td style="width:30%; border:none; text-align:center; vertical-align:middle;">
            <div style="font-size:16px; font-weight:bold;">GENERAL PROCESS RECORD</div>
        </td>

        {{-- RIGHT: inspection meta + checkboxes --}}
        <td style="width:35%; border:none; vertical-align:top; font-size:10px;">
            <table style="width:100%; border:none;">
                <tr>
                    <td style="border:none; width:40%;"><strong>MONTH/YEAR:</strong></td>
                    <td style="border:none; border-bottom:1px solid #000;">{{ $data->monthYear }}</td>
                </tr>
                <tr>
                    <td style="border:none;"><strong>INSPECTION GROUP:</strong></td>
                    <td style="border:none; border-bottom:1px solid #000;">{{ $data->inspectionGroup }}</td>
                </tr>
            </table>

            <div class="right-options">
                @php
                    $checkboxes = [
                        [$data->noViCheck, 'NO check if 100% VI'],
                        [$data->viGood,    'Check if 200% VI of GOOD parts'],
                        [$data->viNg,      'Check if 200% VI of NG parts'],
                        [$data->rework,    'Check if REWORK'],
                    ];
                @endphp
                @foreach ($checkboxes as [$checked, $label])
                <div class="check-group" @if ($loop->first) style="margin-top:10px;" @endif>
                    <span class="check-box {{ $checked ? 'filled' : '' }}"></span>
                    {{ $label }}
                </div>
                @endforeach
            </div>
        </td>
    </tr>
</table>

<br>

<div class="top-options">
    <div class="left-options">
        <div class="check-group">
            <span class="check-box {{ $data->isSilicon ? 'filled' : '' }}"></span>
            <strong>silicon parts</strong>
            &nbsp;&nbsp;&nbsp;&nbsp;
            <span class="check-box {{ $data->isSilicon ? '' : 'filled' }}"></span>
            <strong>non-silicon parts</strong>
        </div>
    </div>
    <div class="clearfix"></div>
</div>

<br>
