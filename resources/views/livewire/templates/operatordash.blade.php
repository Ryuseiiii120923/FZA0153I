<div>
    <div class="flex justify-center mt-8">
        @if (session()->has('failed'))
        <div
            x-data="{ open: true }"
            x-show="open"
            class="fixed inset-0 flex items-center justify-center bg-gray-800 bg-opacity-50 z-50"
            x-cloak>
            <div class="bg-white rounded-lg shadow-lg w-96 p-6 text-center relative">
                <!-- Close Button -->
                <button
                    @click="open = false"
                    class="absolute top-2 right-2 text-gray-400 hover:text-gray-600">
                    ✕
                </button>

                <!-- Modal Content -->
                <h2 class="text-lg font-semibold text-red-600 mb-2">Failed</h2>
                <p class="text-gray-700 mb-4">{{ session('failed') }}</p>

                <button
                    @click="open = false;
            location.reload();
            "
                    class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded">
                    OK
                </button>
            </div>
        </div>
        @endif
        <div wire:init="LoadPPF"></div>

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
                            <button wire:click="editPPF('{{ $record->PPFNo }}')" class="bg-green-700 hover:bg-green-800 text-white px-4 py-2 rounded">
                                Edit
                            </button>
                            <button wire:click="deletePPF('{{ $record->PPFNo }}')" class="bg-red-500 hover:bg-red-700 text-white px-4 py-2 rounded">
                                Delete
                            </button>
                            <button wire:click="viewPPF('{{ $record->PPFNo }}')" class="bg-yellow-500 hover:bg-yellow-700 text-white px-4 py-2 rounded">
                                View
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="px-3 py-5 text-center">No records found</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>
    @if ($loading)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div class="bg-white p-6 rounded-lg shadow-lg">
            ⏳ Processing...
        </div>
    </div>
    @endif
</div>