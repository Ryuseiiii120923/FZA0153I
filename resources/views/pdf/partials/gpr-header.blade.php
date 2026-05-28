{{--
    Partial: gpr-header.blade.php
    Variables: $record, $operation
--}}

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
            <div style="font-size:12px; font-weight:bold; margin-top:4px;">{{ $operation }}</div>
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
            <span class="check-box {{ $record['is_silicon'] ? '' : 'filled' }}"></span>
            <strong>non-silicon parts</strong>
        </div>
    </div>
    <div class="clearfix"></div>
</div>

<br>