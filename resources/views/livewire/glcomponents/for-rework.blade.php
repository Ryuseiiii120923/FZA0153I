<div class="bg-white px-3 py-4 rounded-lg w-full max-w-1xl @if($locked) opacity-50 cursor-not-allowed @endif">
    <div class="bg-white shadow-md max-w-3xl mx-auto px-3 py-4 rounded-lg h-[500px] flex flex-col">
        <div class="bg-gray-700 w-full">
            <p class="text-4xl font-extrabold text-center text-white p-4">For Rework</p>
        </div>
        <div class="overflow-x-auto mt-3 max-h-[300px] overflow-y-auto">
            <table class="table-auto w-full text-sm text-white rounded-lg bg-gray-800 overflow-hidden mx-auto">
                <thead class="bg-gray-900 text-white text-left">
                    <tr>
                        <th class="px-4 py-2">Total Rework</th>
                        <th class="px-4 py-2">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($pendingReworks ?? [] as $data)
                    <tr>
                        <td class="px-4 py-2">{{ $data->total_rework  ?? 0 }}</td>
                        <td class="px-4 py-2 flex justify-start gap-2">
                            <button
                                class="text-white bg-blue-700 px-4 py-2 rounded"
                                wire:click="ProceedRework({{ $data->PPFNo }})"
                                @if($locked) disabled @endif>
                                Proceed To Rework
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>