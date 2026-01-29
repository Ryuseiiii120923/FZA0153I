<div class="flex justify-center mt-8">

    <div wire:init = 'LoadPPF'></div>
    <div class="overflow-x-auto bg-gray-800 rounded-lg shadow-md border border-gray-700 w-9/12">
        <table class="table-auto w-full text-sm text-white bg-gray-800 rounded-lg overflow-hidden">
            <thead class="bg-gray-900 text-left">
                <tr>
                    <th class="px-6 py-5">PPFNo</th>
                    <th class="px-6 py-5">Date</th>
                    <th class="px-6 py-5">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($ppfrecord as $record)
                <tr class="border-b border-gray-700">
                    <td class="px-4 py-2">{{ (int) $record->PPFNo }}</td>
                    <td class="px-4 py-2">{{ $record->DateEncode }}</td>
                     <td class="px-4 py-2 flex gap-2">

                    <button 
                        wire:click="editPPF('{{ $record->PPFNo }}')"
                        class="bg-green-700 hover:bg-green-800 text-white px-4 py-2 rounded">
                        Edit
                    </button>


                    <button 
                        wire:click="deletePPF('{{ $record->PPFNo }}')"
                        class="bg-red-500 hover:bg-red-700 text-white px-4 py-2 rounded">
                        Delete
                    </button>
                </td>
                </tr>
                @empty
                <td colspan="3" class="px-4 py-2 text-center">No records found</td>
                @endforelse
            </tbody>
        </table>
    </div>

</div>