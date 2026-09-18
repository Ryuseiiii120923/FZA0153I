@props(['defects' => [], 'smallDefects' => [], 'readOnly' => false])

<div class="overflow-x-auto mt-3">
    <table class="table-auto w-full text-sm text-white bg-gray-800 rounded-lg overflow-hidden">
        <thead class="bg-gray-900 text-white text-left">
            <tr>
                <th class="px-4 py-2">Inspector ID</th>
                <th class="px-4 py-2">Inspector Name</th>
                <th class="px-6 py-2">Defect Type</th>
                <th class="px-4 py-2">Quantity</th>
                <th class="px-4 py-2">Endcode Date</th>
                <th class="px-4 py-2">Process</th>
            </tr>
        </thead>
        <tbody class="bg-gray-700">
            @forelse($defects as $defect)
            <tr wire:key="defect-{{ $defect['operatorid'] }}-{{ $defect['type'] }}">
                <td class="px-4 py-2">{{ $defect['operatorid'] }}</td>
                <td class="px-4 py-2">{{ $defect['operatorname'] }}</td>
                <td class="px-4 py-2">{{ $defect['type'] }}</td>
                <td class="px-4 py-2">{{ $defect['qty'] > 0 ? $defect['qty'] : '' }}</td>
                <td class="px-4 py-2">{{ $defect['dateEncode'] }}</td>
                <td class="px-4 py-2">{{ $defect['Process'] }}</td>
            </tr>

            @php $processKey = strtolower(trim($defect['Process'] ?? '')); @endphp
            @foreach($smallDefects[$defect['type']][$processKey][$defect['operatorid']] ?? [] as $sDefect)
            <tr class="bg-gray-500" wire:key="smalldefect-{{ $defect['operatorid'] }}-{{ $sDefect['type'] }}">
                <td class="px-8 py-1"></td>
                <td class="px-8 py-1"></td>
                <td class="px-9 py-1">- {{ $sDefect['type'] }}</td>
                <td class="px-4 py-1">{{ $sDefect['qty'] }}</td>
                <td colspan="2" class="px-4 py-1 flex justify-center gap-2">
                    <x-ui.defect.operator-small-edit
                        :defect="$defect" :process-key="$processKey" :s-defect="$sDefect" :read-only="$readOnly" />
                </td>
            </tr>
            @endforeach

            @empty
            <tr><td colspan="6" class="px-6 py-4 text-center">No defects added yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>