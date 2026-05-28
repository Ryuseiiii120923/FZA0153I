{{--
    Partial: pdf/partials/_table-head.blade.php
    Props: $data (GeneralProcessRecordData)
--}}
<thead>
    {{-- ROW 1: top-level labels --}}
    <tr>
        <th colspan="5"></th>
        <th colspan="{{ $data->totalLargeDefects() + $data->totalSmallDefects() + $data->totalReworks() }}">
            TYPES OF DEFECTS
        </th>
        <th colspan="11"></th>
    </tr>

    {{-- ROW 2: category-group labels + rowspan stubs --}}
    <tr>
        <th rowspan="3">mm</th>
        <th rowspan="3">dd</th>
        <th rowspan="3" class="vertical-header"><div>SHIFT</div></th>
        <th rowspan="3">PROCESS</th>
        <th rowspan="3">TOTAL QTY</th>

        <th colspan="{{ $data->totalLargeDefects() }}">LARGE CATEGORY</th>
        <th colspan="{{ $data->totalSmallDefects() }}">SMALL CATEGORY</th>
        <th colspan="{{ $data->totalReworks() }}">FOR REWORK</th>

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

    {{-- ROW 3: large-category names (vertical) + rework types --}}
    <tr style="height:5px;">
        @foreach ($data->groupedDefects as $largeCategory => $items)
        <th rowspan="2" class="vertical-header"><div>{{ $largeCategory }}</div></th>
        @endforeach

        @foreach ($data->groupedDefects as $largeCategory => $items)
        <th colspan="{{ $items->count() }}">{{ $largeCategory }}</th>
        @endforeach

        @foreach ($data->groupedReworks as $rework)
        <th rowspan="2" class="vertical-header"><div>{{ $rework['type'] }}</div></th>
        @endforeach
    </tr>

    {{-- ROW 4: small-category names (vertical) --}}
    <tr>
        @foreach ($data->groupedDefects as $items)
        @foreach ($items as $defect)
        <th class="vertical-header small-defect-header">
            <div>{{ $defect['small_category'] ?? '' }}</div>
        </th>
        @endforeach
        @endforeach
    </tr>
</thead>
