<div class="bg-white rounded-lg w-full max-w-1xl mx-auto py-4 @if($locked) opacity-50 cursor-not-allowed @endif">
      <div class="bg-white shadow-md max-w-3xl mx-auto px-3 py-4 rounded-lg h-[500px] flex flex-col">
        <div class="space-y-4 mt-6">
            <div class="bg-gray-700 w-9/12 mx-auto">
                <p class="text-4xl font-extrabold text-center text-white p-4">Done Rework</p>
            </div>
            <div class="overflow-x-auto mt-3">
                <table class="table-auto w-9/12 text-sm text-white rounded-lg bg-gray-800 overflow-hidden mx-auto">
                    <thead class="bg-gray-900 text-white text-left">
                        <tr>
                            <th class="px-4 py-2">Inspector Id</th>
                            <th class="px-4 py-2">Inspector Name</th>
                            <th class="px-4 py-2">HF Id</th>
                            <th class="px-4 py-2">HF Name</th>
                            <th class="px-4 py-2">Total Inspect</th>
                            <th class="px-4 py-2">Total Good Quantity</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($doneReworks ?? [] as $data)
                        <tr>
                            <td class="px-4 py-2">{{ $data->updated_by ?? '' }}</td>
                            <td class="px-4 py-2">{{ $data->updatedByWorker?->employeeName?->名前 ?? '' }}</td>
                            <td class="px-4 py-2">{{ $data->hf_id  ?? '' }}</td>
                            <td class="px-4 py-2">{{ $data->worker?->employeeName?->名前 ?? '' }}</td>
                            <td class="px-4 py-2">{{ $data->total_inspect  ?? 0 }}</td>
                            <td class="px-4 py-2">{{ $data->GoodQty?? 0 }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>