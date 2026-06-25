<div class=" bg-white shadow-lg px-3 py-4 @if($locked) opacity-50 cursor-not-allowed @endif">
     <div class="bg-gray-700 w-full ">
        <p class="text-4xl font-extrabold  text-center text-white p-4 ">Total Inspection</p>
    </div>
    <div class="overflow-x-auto mt-3">
        <table class=" table-auto w-full text-sm text-white bg-gray-800 rounded-lg overflow-hidden">
            <thead class="bg-gray-900 text-white text-left">
                <tr>
                    <th class="px-4 py-2">Inspector Id</th>
                    <th class="px-4 py-2">Inspector Name</th>
                    <th class="px-4 py-2">Total Inspection</th>
                    <th class="px-4 py-2">Date Encode</th>
                </tr>
            </thead>
            <tbody class="bg-gray-700">
                @forelse ( $inspections ?? [] as $inspection )
                <tr>
                    <td class="px-4 py-2">{{ $inspection->updated_by ?? '-' }}</td>
                    <td class="px-4 py-2">{{ $inspection->worker?->employeeName?->名前 ?? '-' }}</td>
                    <td class="px-4 py-2">{{ $inspection->total_inspect ?? '-' }}</td>
                    <td class="px-4 py-2">{{ $inspection->updated_date ?? '-' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-6 py-4 text-center">
                        No data added.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>