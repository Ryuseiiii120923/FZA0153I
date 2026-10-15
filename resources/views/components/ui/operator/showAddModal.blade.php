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