<div class="w-full px-2 sm:px-6">

    {{-- Panel Header --}}
    <div class="mb-6">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Enroll Operator</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Register and manage operators in the system.</p>
    </div>

    {{-- Toolbar: Search + Add button --}}
    <div class="flex items-center gap-3 mb-5 flex-wrap">

        {{-- Search --}}
        <div class="relative flex-1 min-w-[180px]">
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
                placeholder="Search by name or ID…"
                class="w-full pl-9 pr-4 py-2 text-sm border border-gray-300 rounded-lg shadow-sm
                       focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                       dark:bg-gray-800 dark:border-gray-600 dark:text-gray-100"
            />
        </div>

        {{-- Add New Operator button --}}
        <button
            type="button"
            wire:click="openAddModal"
            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold
                   bg-[#0F3C89] text-white rounded-lg hover:bg-blue-800
                   transition-colors duration-150 shadow-sm whitespace-nowrap"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            New Operator
        </button>

    </div>

    {{-- Operator Card Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">

        @forelse ($records as $record)

            @php
                // Generate initials from operator name for the avatar
                $words    = explode(' ', trim($record->OperatorName));
                $initials = strtoupper(
                    count($words) >= 2
                        ? substr($words[0], 0, 1) . substr(end($words), 0, 1)
                        : substr($words[0], 0, 2)
                );
            @endphp

            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700
                        rounded-xl p-4 flex items-center gap-3
                        hover:border-blue-300 hover:bg-blue-50 dark:hover:bg-gray-700
                        transition-colors duration-150 group">

                {{-- Avatar --}}
                <div class="flex-shrink-0 w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900
                            flex items-center justify-center
                            text-sm font-semibold text-blue-800 dark:text-blue-200">
                    {{ $initials }}
                </div>

                {{-- Info --}}
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 truncate">
                        {{ $record->OperatorName }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        ID: {{ $record->OperatorID }}
                    </p>
                    <span class="inline-block mt-1 text-xs px-2 py-0.5 rounded-md
                                 bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                        {{ $record->Role ?? 'Inspector' }}
                    </span>
                </div>

                {{-- Actions --}}
                <div class="flex flex-col gap-1 opacity-0 group-hover:opacity-100 transition-opacity duration-150">
                    <button
                        type="button"
                        wire:click="openEditModal({{ $record->id }})"
                        title="Edit"
                        class="w-7 h-7 flex items-center justify-center rounded-md
                               border border-gray-200 dark:border-gray-600
                               text-gray-500 hover:text-blue-700 hover:border-blue-300
                               hover:bg-blue-50 dark:hover:bg-blue-900 transition-colors duration-150"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5
                                   m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </button>
                    <button
                        type="button"
                        wire:click="confirmDelete({{ $record->id }})"
                        title="Remove"
                        class="w-7 h-7 flex items-center justify-center rounded-md
                               border border-gray-200 dark:border-gray-600
                               text-gray-500 hover:text-red-700 hover:border-red-300
                               hover:bg-red-50 dark:hover:bg-red-900 transition-colors duration-150"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858
                                   L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>

            </div>

        @empty

            {{-- Empty state --}}
            <div class="col-span-full flex flex-col items-center justify-center py-16 text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 mb-3 text-gray-300"
                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857
                           M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857
                           m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <p class="text-sm">No operators enrolled yet.</p>
                <p class="text-xs mt-1">Click <strong>New Operator</strong> to add one.</p>
            </div>

        @endforelse

        {{-- Quick-add ghost card --}}
        @if($records->count() > 0)
            <button
                type="button"
                wire:click="openAddModal"
                class="border border-dashed border-gray-300 dark:border-gray-600
                       rounded-xl p-4 flex items-center justify-center gap-2
                       text-sm text-gray-400 hover:text-blue-600 hover:border-blue-400
                       hover:bg-blue-50 dark:hover:bg-gray-700
                       transition-colors duration-150"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                Add operator
            </button>
        @endif

    </div>

    {{-- Count --}}
    @if($records->count() > 0)
        <p class="mt-4 text-xs text-gray-400">
            {{ $records->count() }} {{ Str::plural('operator', $records->count()) }} enrolled
        </p>
    @endif

</div>