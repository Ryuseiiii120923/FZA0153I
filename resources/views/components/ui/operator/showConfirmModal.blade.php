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