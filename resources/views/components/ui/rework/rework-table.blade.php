@props(['reworkss', 'systemName', 'formId', 'locked'])

<div class="overflow-x-auto mt-3 mx-3">
    <table class="table-auto w-full text-sm text-white bg-gray-800 rounded-lg overflow-hidden">
        <thead class="bg-gray-900 text-white text-left">
            <tr>
                @if($systemName != 'operator')
                    <th class="px-4 py-2">Inspector ID</th>
                    <th class="px-4 py-2">Inspector Name</th>
                @endif
                <th class="px-4 py-2">HFNo</th>
                <th class="px-4 py-2">RWK Defect</th>
                <th class="px-4 py-2">Qty</th>
                <th class="px-4 py-2">Total Insp</th>
                @if($systemName != 'operator')
                    <th class="px-4 py-2">Date Encode</th>
                @else
                    <th class="px-4 py-2">Action</th>
                @endif
            </tr>
        </thead>
        <tbody class="bg-gray-700">
            @forelse ($reworkss as $reworks)
            <tr wire:key="rework-{{ $reworks['hfno'] }}-{{ $reworks['type'] }}">
                @if($systemName != 'operator')
                    <td class="px-4 py-2">{{ $reworks['operatorid'] ?? '' }}</td>
                    <td class="px-4 py-2">{{ $reworks['operatorname'] ?? '' }}</td>
                @endif
                <td class="px-4 py-2">{{ $reworks['hfno'] }}</td>
                <td class="px-4 py-2">{{ $reworks['type'] }}</td>
                <td class="px-4 py-2">{{ $reworks['quan'] }}</td>
                <td class="px-4 py-2">{{ $reworks['totalinsp'] }}</td>

                @if($systemName != 'operator')
                    <td class="px-4 py-2">{{ $reworks['dateEncode'] ?? '' }}</td>
                @else
                    <td class="px-4 py-2">
                        <x-ui.rework.edit-rework-modal
                            :rework="$reworks"
                            :form-id="$formId"
                            :locked="$locked" />
                    </td>
                @endif
            </tr>
            @empty
            <tr>
                <td colspan="{{ $systemName != 'operator' ? 7 : 5 }}" class="px-6 py-4 text-center">
                    No rework added yet.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>