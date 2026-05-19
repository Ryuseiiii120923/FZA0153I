<div  class="w-full"
    x-data
    @open-pdf.window="window.open($event.detail.url, '_blank')">

    {{-- Header --}}
    <div class="mb-6">
        <h2 class="text-lg font-semibold text-white">Generate Process Record</h2>
        <p class="text-sm text-gray-500 mt-1">Search a PPF number and generate its process record PDF.</p>
    </div>

    {{-- Toolbar: Search + Per-page --}}
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        {{-- Search --}}
        <div class="flex items-center gap-2 w-full sm:w-auto sm:flex-1">
            <div class="relative flex-1">
                <span class="absolute inset-y-0 left-3 flex items-center text-gray-400 pointer-events-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M21 21l-4.35-4.35M17 11A6 6 0 1 0 5 11a6 6 0 0 0 12 0z" />
                    </svg>
                </span>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search PPF number..."
                    class="w-full min-w-0 pl-9 pr-4 py-2 text-sm border border-gray-300 rounded-lg shadow-sm
                           focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
            </div>
            @if($search)
            <button
                wire:click="$set('search', '')"
                class="text-sm text-white border border-gray-300 rounded-lg hover:text-gray-700 px-2 py-1 rounded-lg hover:bg-gray-100 transition whitespace-nowrap">
                Clear
            </button>
            @endif
        </div>

        {{-- Per-page selector --}}
        <div class="flex items-center gap-2 text-sm text-gray-800">
            <label for="perPage" class="text-white">Rows per page:</label>
            <select
                id="perPage"
                wire:model.live="perPage"
                class="border border-gray-300 rounded-lg px-2 py-1.5 text-sm
                       focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
        </div>

    </div>

    {{-- Table --}}
    <div class="w-full overflow-x-auto rounded-xl border border-gray-200 shadow-sm">
        <table class="w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-[#0F3C89] text-white">
                <tr>
                    <th class="px-5 py-3 text-left font-semibold tracking-wide w-12">#</th>
                    <th class="px-5 py-3 text-left font-semibold tracking-wide">PPF Number</th>
                    <th class="px-5 py-3 text-left font-semibold tracking-wide">Latest Date Encode</th>
                    <th class="px-5 py-3 text-center font-semibold tracking-wide">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">

                @forelse($records as $row)
                <tr class="hover:bg-blue-50 transition-colors duration-150">

                    {{-- Global row number --}}
                    <td class="px-5 py-3 text-gray-400 font-mono text-xs">
                        {{ ($records->currentPage() - 1) * $records->perPage() + $loop->iteration }}
                    </td>

                    {{-- PPF Number --}}
                    <td class="px-5 py-3 font-semibold text-gray-800">
                        {{ $row->PPFNo_str }}
                    </td>

                    {{-- Latest Date Encode --}}
                    <td class="px-5 py-3 text-gray-500">
                        {{ $row->DateEncode ?? '—' }}
                    </td>

                    {{-- Generate PR button --}}
                    <td class="px-5 py-3 text-center">
                        <button
                            type="button"
                            wire:click="exportPdf('{{ $row->PPFNo_str }}')"
                            wire:loading.attr="disabled"
                            wire:target="exportPdf('{{ $row->PPFNo_str }}')"
                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold
                                       bg-[#0F3C89] text-white hover:bg-blue-800
                                       disabled:opacity-50 disabled:cursor-not-allowed
                                       transition-colors duration-150 shadow-sm">
                            <span wire:loading wire:target="exportPdf('{{ $row->PPFNo_str }}')">
                                <svg class="animate-spin w-3.5 h-3.5 text-white"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                        stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                            </span>
                            <span wire:loading.remove wire:target="exportPdf('{{ $row->PPFNo_str }}')">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h4a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
                                </svg>
                            </span>
                            Generate PR
                        </button>
                    </td>

                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-5 py-12 text-center text-gray-400">
                        <div class="flex flex-col items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-gray-300"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0121 9.414V19a2 2 0 01-2 2z" />
                            </svg>
                            <span class="text-sm">
                                @if($search)
                                No PPF found matching <strong>"{{ $search }}"</strong>
                                @else
                                No defect records found.
                                @endif
                            </span>
                        </div>
                    </td>
                </tr>
                @endforelse

            </tbody>
        </table>
    </div>

    {{-- Pagination footer --}}
    @if($records->total() > 0)
    <div class="mt-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-sm text-white">
        <p>
            Showing
            <span class="font-medium text-white">{{ $records->firstItem() }}</span>–<span class="font-medium text-white-700">{{ $records->lastItem() }}</span>
            of
            <span class="font-medium text-white">{{ $records->total() }}</span>
            PPF {{ Str::plural('number', $records->total()) }}
            @if($search)
            matching <span class="font-medium text-white">"{{ $search }}"</span>
            @endif
        </p>
        <div>
            {{ $records->links() }}
        </div>
    </div>
    @endif

</div>