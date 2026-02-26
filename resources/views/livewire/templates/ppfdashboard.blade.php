<div class="w-full flex justify-center mt-3 overflow-x-auto" wire:poll.5s="refreshData">
    <table class="table-auto w-full text-sm text-white bg-gray-800 rounded-lg overflow-hidden">
        <thead class="bg-gray-900 text-white text-center">
            <tr>
                <th class="px-4 py-2">PPFNo</th>
                <th class="px-6 py-2">Inspection Total</th>
                <th class="px-4 py-2">Encode Date</th>
                <th class="px-4 py-2">Action</th>
            </tr>
        </thead>
        <tbody class="bg-gray-700">
            @forelse($ppfdata as $data)
                <tr>
                    <td class="px-4 py-2 text-center">{{ (int)$data->PPFNo }}</td>
                    <td class="px-4 py-2 text-center">{{ $data->total_inspect }} / {{ $data->expct }}</td>
                    <td class="px-4 py-2 text-center">{{ $data->DateEncode }}</td>
                    <td class="px-4 py-2 flex justify-center gap-2">
                        <button class="text-white bg-green-700 px-4 py-2 rounded" wire:click="confirm_ppf('{{ $data->PPFNo }}')">Confirm</button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="px-6 py-4 text-center">No defects added yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
