<div x-data="{
    showFixed: false,
    activeTab: @entangle('activeTab')
}"
    x-init="
    const container = document.getElementById('prencode-scroll-container');
    const target = container ?? window;
    target.addEventListener('scroll', () => {
        showFixed = (container ? container.scrollTop : window.scrollY) > 470;
    });
">

    {{-- TAB HEADERS --}}
    <div class="bg-white shadow-md px-5 pt-4 pb-0 flex gap-0 border-b border-gray-200">
        <button
            @click="activeTab = 'worker'"
            :class="activeTab === 'worker'
                ? 'border-b-2 border-blue-600 text-blue-600 font-semibold'
                : 'text-gray-500 hover:text-gray-700'"
            class="px-5 py-3 text-sm transition-colors duration-150">
            Worker Inspection
        </button>
        <button
            @click="activeTab = 'auto'"
            :class="activeTab === 'auto'
                ? 'border-b-2 border-indigo-600 text-indigo-600 font-semibold'
                : 'text-gray-500 hover:text-gray-700'"
            class="px-5 py-3 text-sm transition-colors duration-150">
            Auto Inspection
        </button>
    </div>

    {{-- WORKER TAB BUTTONS --}}
    <div x-show="activeTab === 'worker'" class="px-5 py-4 flex gap-3 bg-white shadow-sm">
        <button wire:click="addNew" class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm">
            + Add Worker
        </button>
        <button wire:click="addNewDoneRework" class="text-white px-4 py-2 rounded-md text-sm" style="background-color:#0b9af3;">
            + Add Worker For Rework
        </button>
        <button wire:click="addNewPL" class="text-white px-4 py-2 rounded-md text-sm" style="background-color:#02367B;">
            + Add Worker For PL
        </button>
    </div>

    {{-- AUTO INSPECTION TAB BUTTONS --}}
    <div x-show="activeTab === 'auto'" class="px-5 py-4 flex flex-wrap gap-3 bg-white shadow-sm">
        <button wire:click="addNewAutoDimension" class="text-white px-4 py-2 rounded-md text-sm" style="background-color:#4338ca;">
            + Auto Dimension Checking
        </button>
        <button wire:click="addNewAutoSF" class="text-white px-4 py-2 rounded-md text-sm" style="background-color:#0369a1;">
            + Auto SF Inspection
        </button>
        <button wire:click="addNewAutoPLSF" class="text-white px-4 py-2 rounded-md text-sm" style="background-color:#0f766e;">
            + Auto PL/SF Inspection
        </button>
        <button wire:click="addNewAutoNG" class="text-white px-4 py-2 rounded-md text-sm" style="background-color:#b45309;">
            + Auto NG Checking
        </button>
        <button wire:click="addNewAutoDimNG" class="text-white px-4 py-2 rounded-md text-sm" style="background-color:#7c3aed;">
            + Auto Dimension of VI Good from NG
        </button>
        <button wire:click="addNewAutoSFNG" class="text-white px-4 py-2 rounded-md text-sm" style="background-color:#be185d;">
            + Auto SF Dimension of VI Good from NG
        </button>
    </div>

    {{-- FLOATING BAR (on scroll) --}}
    <div x-show="showFixed" x-transition
        class="fixed top-0 left-0 w-full z-50 bg-white shadow-lg px-5 py-4 flex flex-wrap gap-3">
        <template x-if="activeTab === 'worker'">
            <div class="flex gap-3 flex-wrap">
                <button wire:click="addNew" class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm">+ Add Worker</button>
                <button wire:click="addNewDoneRework" class="text-white px-4 py-2 rounded-md text-sm" style="background-color:#0b9af3;">+ Add Worker For Rework</button>
                <button wire:click="addNewPL" class="text-white px-4 py-2 rounded-md text-sm" style="background-color:#02367B;">+ Add Worker For PL</button>
            </div>
        </template>
        <template x-if="activeTab === 'auto'">
            <div class="flex gap-3 flex-wrap">
                <button wire:click="addNewAutoDimension" class="text-white px-4 py-2 rounded-md text-sm" style="background-color:#4338ca;">+ Auto Dimension Checking</button>
                <button wire:click="addNewAutoSF" class="text-white px-4 py-2 rounded-md text-sm" style="background-color:#0369a1;">+ Auto SF Inspection</button>
                <button wire:click="addNewAutoPLSF" class="text-white px-4 py-2 rounded-md text-sm" style="background-color:#0f766e;">+ Auto PL/SF Inspection</button>
                <button wire:click="addNewAutoNG" class="text-white px-4 py-2 rounded-md text-sm" style="background-color:#b45309;">+ Auto NG Checking</button>
                <button wire:click="addNewAutoDimNG" class="text-white px-4 py-2 rounded-md text-sm" style="background-color:#7c3aed;">+ Auto Dimension of VI Good from NG</button>
                <button wire:click="addNewAutoSFNG" class="text-white px-4 py-2 rounded-md text-sm" style="background-color:#be185d;">+ Auto SF Dimension of VI Good from NG</button>
            </div>
        </template>
    </div>

    {{-- CONTENT --}}
    @php
    $autoMethods = ['AUTO_DIM','AUTO_SF','AUTO_PLSF','AUTO_NG','AUTO_DIM_NG','AUTO_SF_NG'];
    @endphp

    <div class="space-y-4 mt-10">

        @foreach($forms as $formId => $form)
        @php
        $isAutoForm = in_array($form['method'] ?? '', $autoMethods);
        $isAutoJs = $isAutoForm ? 'true' : 'false';
        @endphp
        <div
            wire:key="worker-form-{{ $formId }}"
            class="border rounded shadow p-3 relative"
            x-show="{{ $isAutoJs }} === (activeTab === 'auto')"
            x-data="{ open: {{ $form['open'] ? 'true' : 'false' }} }">

            @php
            $method = $form['method'] ?? '';
            $forRework = $form['ForRework'] ?? false;
            $headerBg = match(true) {
            $forRework => '#22d3ee',
            $method === 'PL' => '#02367B',
            $method === 'SF' => '#0284c7',
            $method === 'AUTO_DIM' => '#4338ca',
            $method === 'AUTO_SF' => '#0369a1',
            $method === 'AUTO_PLSF' => '#0f766e',
            $method === 'AUTO_NG' => '#b45309',
            $method === 'AUTO_DIM_NG' => '#7c3aed',
            $method === 'AUTO_SF_NG' => '#be185d',
            default => '#f3f4f6',
            };
            $headerFg = $headerBg === '#f3f4f6' ? '#111827' : '#ffffff';
            @endphp

            {{-- Header: Click to Toggle --}}
            <div class="flex justify-between items-center cursor-pointer px-4 py-2"
                @style(['background-color:' . $headerBg, 'color:' . $headerFg])
                @click="open = !open; $wire.toggle('{{ $formId }}')">

                <span class="font-medium">
                    @if($isAutoForm)
                    {{ $form['Process'] }} &mdash; #{{ $form['hf_id'] ?? 'New' }}
                    @else
                    Worker Form #{{ $form['hf_id'] ?? 'Unknown' }}
                    @endif
                </span>
                <span class="font-medium">Date Created: {{ $form['created_at'] ?? now()->format('Y-m-d') }}</span>
                <span class="font-medium">Date Updated: {{ $form['updated_date'] ?? 'Not Yet Updated' }}</span>

                <div class="flex items-center gap-2">
                    <button
                        @click.stop
                        wire:click="editHF('{{ $formId }}')"
                        class="rounded px-3 py-1 bg-blue-600 text-white text-sm">
                        Edit
                    </button>
                    <button
                        @click.stop
                        wire:click="remove('{{ $formId }}')"
                        class="rounded px-3 py-1 bg-red-600 text-white text-sm">
                        Remove
                    </button>
                    <svg class="w-5 h-5 transform"
                        :class="{ 'rotate-180': open }"
                        fill="none" stroke="currentColor" stroke-width="2"
                        viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </div>
            </div>

            {{-- Dropdown Content with Animation --}}
            <div x-show="open"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 max-h-0"
                x-transition:enter-end="opacity-100 max-h-screen"
                x-transition:leave="transition ease-in duration-300"
                x-transition:leave-start="opacity-100 max-h-screen"
                x-transition:leave-end="opacity-0 max-h-0"
                class="overflow-hidden">

                <div class="flex flex-col gap-4 mt-4 p-4 bg-gray-50 rounded">

                    <div class="flex flex-col sm:flex-row gap-4">
                        {{-- Finishing Procedure — hidden for PL, SF, and Auto methods --}}
                        <div class="w-full sm:w-1/2"
                            @if(in_array(($form['method'] ?? '' ), array_merge(['PL','SF'], $autoMethods))) hidden @endif>
                            <label class="block text-sm font-medium">Finishing Procedure</label>
                            <div class="flex items-center gap-3">
                                <input type="text"
                                    wire:model="forms.{{ $formId }}.finishingProcedure"
                                    class="w-full border bg-gray-500 p-2 rounded"
                                    readonly>
                            </div>
                        </div>

                        <div class="w-full sm:w-1/2">
                            <label class="block text-sm font-medium">HF ID</label>
                            <div class="flex items-center gap-3">
                                <input type="text"
                                    wire:model="forms.{{ $formId }}.hf_id"
                                    class="w-full border bg-gray-500 p-2 rounded"
                                    readonly placeholder="Enter HF ID" maxlength="4" pattern="\d{4}">
                                @if(!empty($form['hf_name']))
                                <p class="text-sm font-medium text-black">{{ $form['hf_name'] }}</p>
                                @endif
                            </div>
                        </div>

                        @error('forms.' . $formId . '.hf_id')
                        <p class="text-red-500 text-sm">{{ $message }}</p>
                        @enderror

                        <div class="w-full sm:w-1/2">
                            <label class="block text-sm font-medium">Total Inspect</label>
                            <input type="number" readonly
                                wire:model="forms.{{ $formId }}.total_inspect"
                                class="w-full border bg-gray-500 p-2 rounded"
                                placeholder="Enter Total Inspect">
                        </div>
                    </div>

                    {{-- Modal --}}
                    <div
                        x-data="{ open: @entangle('modalOpen.' . $formId) }"
                        x-show="open"
                        x-cloak
                        class="fixed inset-0 flex items-center justify-center bg-black/50 z-50"
                        @keydown.escape.window="open = false">
                        <div class="bg-white rounded-lg p-6 w-11/12 sm:w-1/3">
                            <div class="flex flex-col gap-4">

                                {{-- Finishing Procedure — hidden for PL, SF, and Auto methods --}}
                                <div class="w-full mx-auto"
                                    @if(in_array(($form['method'] ?? '' ), array_merge(['SF'], $autoMethods))) hidden @endif>
                                    <label for="finishingMachine" class="block text-sm font-medium text-gray-700">
                                        Finishing Procedure
                                    </label>
                                    <select id="finishingMachine"
                                        class="mt-1 block w-full border border-black rounded-md px-2 py-1"
                                        wire:model="forms.{{ $formId }}.finishingProcedure"
                                        required>
                                        <option value="">--- Select Finishing Procedure ---</option>
                                        <option value="Hand Finishing">Hand Finishing</option>
                                        <option value="Cold Deflushing">Cold Deflashing</option>
                                        <option value="Milling">Milling</option>
                                        <option value="Post Curing">Post Curing</option>
                                        <option value="Cutting">Cutting</option>
                                        <option value="Punching">Punching</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium">HF ID</label>
                                    @if(!empty($form['hf_name']))
                                    <p class="text-sm font-medium text-black">{{ $form['hf_name'] }}</p>
                                    @endif
                                    <input type="text"
                                        wire:model.lazy="forms.{{ $formId }}.hf_id"
                                        wire:blur="CheckHf('{{ $formId }}')"
                                        class="w-full border p-2 rounded"
                                        placeholder="Enter HF ID"
                                        maxlength="4" pattern="\d{4}"
                                        oninput="this.value = this.value.toUpperCase()">
                                    @error('forms.' . $formId . '.hf_id')
                                    <p class="text-red-500 text-sm">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium">Total Inspect</label>
                                    <input type="number"
                                        wire:model="forms.{{ $formId }}.total_inspect"
                                        class="w-full border p-2 rounded"
                                        placeholder="Enter Total Inspect">
                                    @error('forms.' . $formId . '.total_inspect')
                                    <p class="text-red-500 text-sm">{{ $message }}</p>
                                    @enderror
                                </div>

                                <button
                                    wire:click="saveHF('{{ $formId }}')"
                                    @if(!empty($hasErrorForm[$formId])) disabled @endif
                                    class="bg-green-600 text-white px-4 py-2 rounded mt-2 w-full">
                                    Save
                                </button>

                                <button
                                    wire:click="CloseModal('{{ $formId }}')"
                                    class="bg-green-600 text-white px-4 py-2 rounded mt-2 w-full">
                                    Exit
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Defects + Rework --}}
                    <div class="flex flex-col sm:flex-row gap-6 mt-4">
                        <div class="w-full sm:w-1/2 flex justify-center">
                            <livewire:templates.defects
                                :formId="$formId"
                                :loadedDefects="$form['defects']"
                                :loadedSmallDefects="$form['smallDefects']"
                                :dispatchPrefix="'operator'"
                                :key="'defects-'.$formId" />
                        </div>
                        <div class="w-full sm:w-1/2 flex justify-center">
                            <livewire:templates.rework
                                :formId="$formId"
                                :loadedRework="$form['rework']"
                                :dispatchPrefix="'operator'"
                                :key="'reworks-'.$formId" />
                        </div>
                    </div>

                    <div class="w-full">
                        <label class="block text-sm font-medium">Total Good Qty.</label>
                        <input type="text"
                            wire:model="forms.{{ $formId }}.GoodQty"
                            class="w-full border p-2 rounded"
                            readonly>
                    </div>

                    <div class="w-full">
                        <label class="block text-sm font-medium">Remarks</label>
                        <div x-data="{
                            saveTimer: null,
                            buttonTimer: null,
                            typing() {
                                window.dispatchEvent(new CustomEvent('remarks-typing', {
                                    detail: { disabled: true }
                                }));
                                clearTimeout(this.saveTimer);
                                clearTimeout(this.buttonTimer);
                                this.saveTimer = setTimeout(() => {
                                    $wire.saveRemarks('{{ $formId }}');
                                }, 500);
                                this.buttonTimer = setTimeout(() => {
                                    window.dispatchEvent(new CustomEvent('remarks-typing', {
                                        detail: { disabled: false }
                                    }));
                                }, 1000);
                            }
                        }">
                            <input
                                type="text"
                                wire:model="forms.{{ $formId }}.Remarks"
                                @input="typing()"
                                class="w-full border p-2 rounded">
                        </div>
                    </div>

                </div>
            </div>

        </div>
        @endforeach

        @if(session()->has('success'))
        <div
            x-data="{ open: true }"
            x-show="open"
            class="fixed inset-0 flex items-center justify-center bg-gray-800 bg-opacity-50 z-50"
            x-cloak>
            <div class="bg-white rounded-lg shadow-lg w-96 p-6 text-center relative">
                <button @click="open = false" class="absolute top-2 right-2 text-gray-400 hover:text-gray-600">✕</button>
                <h2 class="text-lg font-semibold text-green-600 mb-2">Success</h2>
                <p class="text-gray-700 mb-4">{{ session('success') }}</p>
                <button @click="open = false" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">OK</button>
            </div>
        </div>
        @endif

    </div>
</div>