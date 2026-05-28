{{--
    Partial: gpr-footer.blade.php
    Variables: $record
--}}

<br>

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

<div class="footer-note text-right" style="font-size:7px;">
    FQCX34-D12-8
</div>