<div
    class="w-full"
    x-data="{
        toast: '',
        showToast(msg){ this.toast = msg; setTimeout(() => this.toast = '', 3000) }
    }"
    @operator-saved.window="showToast('Operator enrolled successfully.')"
    @operator-deleted.window="showToast('Operator removed.')">

    @if (session()->has('successNoRefresh'))
    <div
        x-data="{ show: true }"
        x-init="setTimeout(() => show = false, 3000)"
        x-show="show"
        x-transition
      class="fixed inset-0 flex items-start justify-center pt-5 z-50 mt-5 pointer-events-none"
        x-cloak>
        
        <div class="flex items-center justify-center gap-3 bg-green-500 text-white px-5 py-4 rounded-xl shadow-xl min-w-[320px]">

            {{-- Icon --}}
            <svg xmlns="http://www.w3.org/2000/svg"
                class="w-5 h-5 mt-0.5 flex-shrink-0"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2">
                <path stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M5 13l4 4L19 7" />
            </svg>

            {{-- Content --}}
            <div class="flex-1">
                <h2 class="text-sm font-semibold">Success</h2>
                <p class="text-sm text-green-100">
                    {{ session('success') }}
                </p>
            </div>

            {{-- Close --}}
            <button
                @click="show = false"
                class="text-white/70 hover:text-white">
                ✕
            </button>

        </div>
    </div>
    @endif

    {{-- Toast --}}
    <div
        x-show="toast !== ''"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="mb-4 flex items-center gap-2 px-4 py-3 rounded-lg
               bg-green-50 border border-green-200 text-green-800 text-sm"
        style="display:none">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 flex-shrink-0" fill="none"
            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
        </svg>
        <span x-text="toast"></span>
    </div>

    {{-- Panel Header --}}
    <div class="mb-6">
        <h2 class="text-lg font-semibold text-white">Enroll Operator</h2>
        <p class="text-sm text-gray-500 mt-1">Register and manage operators in the system.</p>
    </div>

    {{-- Toolbar --}}
    <div class="flex items-center gap-3 mb-5 flex-wrap">
        <div class="relative flex-1 min-w-[180px]">
            <span class="absolute inset-y-0 left-3 flex items-center text-gray-400 pointer-events-none">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 1 0 5 11a6 6 0 0 0 12 0z" />
                </svg>
            </span>
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="Search by name or ID…"
                class="w-full pl-9 pr-4 py-2 text-sm bg-white text-gray-800
                       border border-gray-300 rounded-lg shadow-sm
                       focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
        </div>
        <button
            type="button"
            wire:click="openModal"
            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold
                   bg-[#0F3C89] text-white rounded-lg hover:bg-blue-800
                   transition-colors duration-150 shadow-sm whitespace-nowrap">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
            New Operator
        </button>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">

        @forelse ($records as $record)
        @php
        $words = explode(' ', trim($record->OperatorName));
        $initials = strtoupper(
        count($words) >= 2
        ? substr($words[0], 0, 1) . substr(end($words), 0, 1)
        : substr($words[0], 0, 2)
        );
        @endphp

        <div
            wire:key="op-{{ $record->OperatorID }}"
            class="bg-white border border-gray-200 rounded-xl p-4
                       flex items-center gap-3
                       hover:border-blue-300 hover:bg-blue-50
                       transition-colors duration-150 group">
            <div class="flex-shrink-0 w-10 h-10 rounded-full bg-blue-100
                            flex items-center justify-center text-sm font-semibold text-blue-800">
                {{ $initials }}
            </div>

            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-gray-800 truncate">{{ $record->OperatorName }}</p>
                <p class="text-xs text-gray-500 mt-0.5">ID: {{ $record->OperatorID }}</p>
                <span class="inline-block mt-1 text-xs px-2 py-0.5 rounded-md bg-green-100 text-green-800">
                    Inspector
                </span>
            </div>

            <div class="flex flex-col gap-1 opacity-0 group-hover:opacity-100 transition-opacity duration-150">
                {{-- Operate button --}}
                <button
                    type="button"
                    wire:click="operateOperator('{{ $record->OperatorID }}')"
                    title="Operate"
                    class="w-10 h-10 flex items-center justify-center rounded-md
                               border border-gray-200 text-gray-500
                               hover:text-blue-700 hover:border-blue-300 hover:bg-blue-50
                               transition-colors duration-150">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-5.197-3.01A1 1 0 008 9.054v5.892a1 1 0 001.555.832l5.197-3.01a1 1 0 000-1.664z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </button>

                {{-- Delete button --}}
                <button
                    type="button"
                    wire:click="setDeleting({{ $record->OperatorID }})"
                    title="Remove"
                    class="w-10 h-10 flex items-center justify-center rounded-md
                               border border-gray-200 text-gray-500
                               hover:text-red-700 hover:border-red-300 hover:bg-red-50
                               transition-colors duration-150">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </button>
            </div>
        </div>

        @empty
        <div
            wire:key="op-empty"
            class="col-span-full flex flex-col items-center justify-center py-16 text-gray-400">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 mb-3 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <p class="text-sm">No operators enrolled yet.</p>
            <p class="text-xs mt-1">Click <strong>New Operator</strong> to add one.</p>
        </div>
        @endforelse

        {{-- Always in the DOM — hidden when empty, never inserted/removed --}}
        <button
            wire:key="op-add-card"
            type="button"
            wire:click="openModal"
            @class([ 'bg-white border border-dashed border-gray-300 rounded-xl p-4' , 'flex items-center justify-center gap-2 text-sm text-gray-400' , 'hover:text-blue-600 hover:border-blue-400 hover:bg-blue-50' , 'transition-colors duration-150' , 'hidden'=> $totalCount === 0,
            ])
            >
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
            Add operator
        </button>
    </div>

    @if($totalCount > 0)
    <p class="mt-4 text-xs text-gray-400">
        {{ $totalCount }} {{ Str::plural('operator', $totalCount) }} enrolled
    </p>
    @endif

    @if($showAdd)
    <div
        class="fixed inset-0 z-50 flex items-center justify-center"
        x-data
        @keydown.escape.window="$wire.call('closeModal')">
        {{-- Backdrop --}}
        <div
            class="absolute inset-0 bg-black/50"
            wire:click="closeModal"></div>

        {{-- Panel --}}
        <div class="relative z-10 w-full max-w-md mx-4 bg-white rounded-2xl shadow-xl overflow-hidden">

            {{-- Header --}}
            <div class="flex items-center justify-between px-6 py-4 bg-[#0F3C89]">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-white">Enroll New Operator</h3>
                        <p class="text-xs text-white/70">Enter the operator ID to auto-fill the name.</p>
                    </div>
                </div>
                <button type="button" wire:click="closeModal" class="text-white/60 hover:text-white transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Body --}}
            <div class="px-6 py-5 space-y-4">

                {{-- Operator ID --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">
                        Operator ID <span class="text-red-500">*</span>
                    </label>
                    <div class="flex gap-2">
                        <input
                            type="text"
                            wire:model.live.debounce.500ms="operatorID"
                            placeholder="e.g. 8050"
                            class="flex-1 px-3 py-2 text-sm bg-gray-50 border border-gray-300 rounded-lg
                                       text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500
                                       @error('operatorID') border-red-400 bg-red-50 @enderror" />
                        <button
                            type="button"
                            wire:click="FetchWorkerName"
                            wire:loading.attr="disabled"
                            wire:target="FetchWorkerName"
                            class="px-3 py-2 text-xs font-semibold bg-gray-100 border border-gray-300
                                       text-gray-700 rounded-lg hover:bg-gray-200 transition-colors
                                       disabled:opacity-50 whitespace-nowrap">
                            <span wire:loading wire:target="FetchWorkerName">
                                <svg class="animate-spin w-3.5 h-3.5 inline text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                            </span>
                            <span wire:loading.remove wire:target="FetchWorkerName">Fetch Name</span>
                        </button>
                    </div>
                    @error('operatorID')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Operator Name --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">
                        Operator Name <span class="text-red-500">*</span>
                    </label>
                    <input
                        readonly
                        type="text"
                        wire:model="operatorName"
                        placeholder="Auto-filled"
                        class="w-full px-3 py-2 text-sm bg-gray-50 border border-gray-300 rounded-lg
                                   text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500
                                   @error('operatorName') border-red-400 bg-red-50 @enderror" />
                    @error('operatorName')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

            </div>

            {{-- Footer --}}
            <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-100 bg-gray-50">
                <button
                    type="button"
                    wire:click="closeModal"
                    class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300
                               rounded-lg hover:bg-gray-100 transition-colors duration-150">
                    Cancel
                </button>
                <button
                    type="button"
                    wire:click="save"
                    wire:loading.attr="disabled"
                    wire:target="save"
                    @error('operatorID') disabled @enderror
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold
                               bg-[#0F3C89] text-white rounded-lg hover:bg-blue-800
                               disabled:opacity-60 transition-colors duration-150">
                    <span wire:loading wire:target="save">
                        <svg class="animate-spin w-4 h-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                    </span>
                    <span wire:loading.remove wire:target="save">Enroll</span>
                </button>
            </div>

        </div>
    </div>
    @endif

    @if($showConfirm)
    <div
        class="fixed inset-0 z-50 flex items-center justify-center"
        x-data
        @keydown.escape.window="$wire.call('cancelDelete')">
        <div class="absolute inset-0 bg-black/50" wire:click="cancelDelete"></div>

        <div class="relative z-10 w-full max-w-sm mx-4 bg-white rounded-2xl shadow-xl overflow-hidden">
            <div class="flex flex-col items-center px-6 pt-6 pb-4 text-center">
                <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </div>
                <h3 class="text-base font-semibold text-gray-800">Remove Operator</h3>
                <p class="text-sm text-gray-500 mt-1">
                    This operator will be permanently removed. This action cannot be undone.
                </p>
            </div>

            <div class="flex items-center justify-center gap-3 px-6 py-4 border-t border-gray-100 bg-gray-50">
                <button
                    type="button"
                    wire:click="cancelDelete"
                    class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300
                               rounded-lg hover:bg-gray-100 transition-colors duration-150">
                    Cancel
                </button>
                <button
                    type="button"
                    wire:click="deleteOperator"
                    wire:loading.attr="disabled"
                    wire:target="deleteOperator"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold
                               bg-red-600 text-white rounded-lg hover:bg-red-700
                               disabled:opacity-60 transition-colors duration-150">
                    <span wire:loading wire:target="deleteOperator">
                        <svg class="animate-spin w-4 h-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                    </span>
                    <span wire:loading.remove wire:target="deleteOperator">Yes, remove</span>
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════
         PRENCODE MODAL — opens when an operator is being operated
    ═══════════════════════════════════════════════════════════ --}}
    @if($showPrencodeVI)
    <div
        class="fixed inset-0 z-50 flex flex-col bg-white overflow-hidden"
        x-data
        @keydown.escape.window="$wire.call('closePrencode')">

        {{-- Modal Header --}}
        <div class="flex items-center justify-between px-6 py-4 bg-[#0F3C89] text-white shadow-md shrink-0">
            <div class="flex items-center gap-3">
                {{-- Avatar --}}
                @php
                $words = explode(' ', trim($activeOperatorName ?? ''));
                $initials = strtoupper(
                count($words) >= 2
                ? substr($words[0], 0, 1) . substr(end($words), 0, 1)
                : substr($words[0], 0, 2)
                );
                @endphp
                <div class="w-9 h-9 rounded-full bg-white/20 flex items-center justify-center text-sm font-bold">
                    {{ $initials }}
                </div>
                <div>
                    <p class="text-sm font-semibold leading-tight">{{ $activeOperatorName }}</p>
                    <p class="text-xs text-blue-200 leading-tight">ID: {{ $activeOperatorID }}</p>
                </div>
            </div>

            <button
                type="button"
                wire:click="closePrencode"
                class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold
                       bg-white/10 hover:bg-white/20 rounded-lg transition-colors duration-150">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
                Close
            </button>
        </div>

        {{-- Modal Body — renders the full Prencode component --}}
        <div class="flex-1 overflow-y-auto bg-gray-50" id="prencode-scroll-container">
            <livewire:pages.operator.prencode
                :key="'prencode-' . $activeOperatorID"
                :operatorID="$activeOperatorID"
                :operateByGl="true"
                systemname="ProcessRecord" />
        </div>

    </div>
    @endif
</div>