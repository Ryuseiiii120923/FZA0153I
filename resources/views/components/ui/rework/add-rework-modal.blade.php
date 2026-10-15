@props(['stagedReworks', 'formId', 'hfno', 'hfname', 'totalInsp', 'rework', 'reworkss', 'readOnly'])

<div x-show="openAddRework" x-cloak x-trap.noscroll="openAddRework" x-transition
    class="fixed inset-0 flex items-center justify-center z-50 p-4">
    <div x-show="openAddRework" x-transition.opacity class="fixed inset-0 bg-black bg-opacity-50 z-40"></div>

    <div class="relative bg-white rounded-xl shadow-xl w-full max-w-3xl z-50 flex flex-col max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 shrink-0">
            <div>
                <h3 class="text-xl font-bold text-gray-900">Add Rework</h3>
                <p class="text-sm text-gray-500 mt-0.5">
                    @if(count($stagedReworks) > 0)
                    <span class="text-blue-600 font-medium">{{ count($stagedReworks) }} entr{{ count($stagedReworks) > 1 ? 'ies' : 'y' }} staged</span> — add more or confirm
                    @else
                    Select a defect type and quantity, then stage or confirm
                    @endif
                </p>
            </div>
            <button type="button" wire:click="resetReworkModal"
                class="text-gray-400 hover:text-gray-700 rounded-lg w-8 h-8 flex justify-center items-center hover:bg-gray-100 transition">
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                </svg>
            </button>
        </div>

        <div class="flex flex-1 justify-center gap-6 px-6 py-4">
            <div class="w-3/4 border-r border-gray-200 px-4 py-4 flex flex-col gap-3">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-black mb-1">HF No.</label>
                        <div class="flex items-center gap-2">
                            <input type="text" readonly x-ref="firstInput"
                                class="bg-gray-100 block w-full border border-gray-300 rounded-md px-2 py-1 text-sm"
                                wire:blur="CheckHf"
                                wire:model.lazy="hfno.{{ $formId }}">
                            @if(!empty($hfno[$formId]))
                            <span class="text-xs font-medium text-gray-700 whitespace-nowrap">{{ $hfname[$formId] ?? '' }}</span>
                            @endif
                        </div>
                        @error('hfno.' . $formId) <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-black mb-1">Total Insp Qty.</label>
                        <input type="number" readonly
                            class="bg-gray-100 block w-full border border-gray-300 rounded-md px-2 py-1 text-sm"
                            wire:model="totalInsp.{{ $formId }}">
                        @error('totalInsp.' . $formId) <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                @if(count($stagedReworks) > 0)
                <div class="border border-blue-200 bg-blue-50 rounded-lg px-4 py-3 space-y-2">
                    <p class="text-xs font-semibold text-blue-600 uppercase tracking-widest mb-1">Staged (ready to confirm)</p>
                    @foreach($stagedReworks as $si => $staged)
                    <div class="flex items-center justify-between gap-2 bg-white border border-blue-100 rounded-lg px-3 py-2">
                        <div class="flex items-center gap-2 flex-1 min-w-0">
                            <span class="text-sm font-semibold text-gray-800 truncate">{{ $staged['type'] }}</span>
                            <span class="text-xs bg-blue-100 text-blue-700 font-bold px-2 py-0.5 rounded-full shrink-0">qty: {{ $staged['quan'] }}</span>
                        </div>
                        <button type="button" wire:click="removeStagedRework({{ $si }})"
                            class="text-red-500 hover:text-red-700 text-xs px-2 py-1 rounded hover:bg-red-50 transition shrink-0">
                            ✕
                        </button>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

            <div class="w-3/4 border-r border-gray-200 px-4 py-4 flex flex-col gap-3">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest">
                    {{ count($stagedReworks) > 0 ? 'Add Another Entry' : 'Rework Entry' }}
                </p>

                <div>
                    <label class="block text-sm font-medium text-black mb-1">Rework Defect</label>
                    <select wire:model="newRework"
                        class="block w-full border border-gray-300 rounded-md px-2 py-1.5 text-sm focus:ring-2 focus:ring-blue-400 focus:outline-none">
                        <option value="">-- Select Rework --</option>
                        @foreach ($rework as $r)
                        @php
                        $alreadyStaged = collect($stagedReworks)->contains('type', $r->DefectType);
                        $alreadyCommitted = collect($reworkss)->contains(fn($rw) => ($rw['hfno'] ?? '') === ($hfno[$formId] ?? '') && ($rw['type'] ?? '') === $r->DefectType);
                        @endphp
                        <option value="{{ $r->DefectType }}" @if($alreadyStaged || $alreadyCommitted) disabled @endif>
                            {{ $r->DefectType }}{{ ($alreadyStaged || $alreadyCommitted) ? ' (added)' : '' }}
                        </option>
                        @endforeach
                    </select>
                    @error('newRework') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-black mb-1">Quantity</label>
                    <input type="number" min="1"
                        class="block w-full border border-gray-300 rounded-md px-2 py-1.5 text-sm focus:ring-2 focus:ring-blue-400 focus:outline-none"
                        wire:model="newQuan">
                    @error('newQuan') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>

                <button type="button" wire:click="stageRework"
                    class="w-full px-4 py-2 rounded-lg border-2 border-blue-500 text-blue-600 text-sm font-semibold hover:bg-blue-50 transition">
                    + Stage This Entry
                </button>
            </div>
        </div>

        <div class="px-6 py-4 border-t border-gray-100 shrink-0 flex justify-end gap-3">
            <button type="button" wire:click="addRework" @click="openAddRework = false" @if($readOnly) disabled @endif
                class="px-5 py-2 rounded-lg bg-[#0F3C89] text-white text-sm font-medium hover:bg-blue-800 transition disabled:opacity-50">
                Confirm &amp; Add{{ count($stagedReworks) > 0 ? ' (' . count($stagedReworks) . ')' : '' }}
            </button>
        </div>
    </div>
</div>