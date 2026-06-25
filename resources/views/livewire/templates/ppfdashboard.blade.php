<div class="w-full flex flex-col justify-center mt-3 overflow-x-auto">
    <div class="w-full flex justify-end mb-3">
        <input
            type="text"
            wire:model.live.debounce.400ms="search"
            placeholder="Search PPF No..."
            class="border border-gray-400 rounded px-3 py-2 text-black w-full sm:w-64">
    </div>
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
                    <button class="text-white bg-green-700 px-4 py-2 rounded" wire:loading.attr="disabled" wire:click.throttle.10000ms="confirm_ppf('{{ $data->PPFNo }}')">Confirm</button>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="4" class="px-6 py-4 text-center">No defects added yet.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
    
    <div class="w-full">
        {{ $ppfdata->links() }}
    </div>
</div>