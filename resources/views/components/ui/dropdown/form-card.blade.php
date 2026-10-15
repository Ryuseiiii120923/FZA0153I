@props(['formId', 'form', 'autoMethods', 'hasErrorForm','systemName'])

@php
$isAutoForm = in_array($form['method'] ?? '', $autoMethods);
$isAutoJs = $isAutoForm ? 'true' : 'false';
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

<div wire:key="worker-form-{{ $formId }}" class="border rounded shadow p-3 relative"
    x-show="{{ $isAutoJs }} === (activeTab === 'auto')"
    x-data="{ open: {{ $form['open'] ? 'true' : 'false' }} }"
    {{ $form['scroll_target'] ?? false ? 'data-scroll-target' : '' }}>

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
            <button @click.stop wire:click="editHF('{{ $formId }}')" class="rounded px-3 py-1 bg-blue-600 text-white text-sm">Edit</button>
            <button @click.stop wire:click="remove('{{ $formId }}')" class="rounded px-3 py-1 bg-red-600 text-white text-sm">Remove</button>
            <svg class="w-5 h-5 transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"></path>
            </svg>
        </div>
    </div>

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
                <div class="w-full sm:w-1/2" @if(in_array(($form['method'] ?? '' ), array_merge(['PL','SF'], $autoMethods))) hidden @endif>
                    <label class="block text-sm font-medium">Finishing Procedure</label>
                    <div class="flex items-center gap-3">
                        <input type="text" wire:model="forms.{{ $formId }}.finishingProcedure" class="w-full border bg-gray-500 p-2 rounded" readonly>
                    </div>
                </div>

                <div class="w-full sm:w-1/2">
                    <label class="block text-sm font-medium">HF ID</label>
                    <div class="flex items-center gap-3">
                        <input type="text" wire:model="forms.{{ $formId }}.hf_id" class="w-full border bg-gray-500 p-2 rounded" readonly placeholder="Enter HF ID" maxlength="4" pattern="\d{4}">
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
                    <input type="number" readonly wire:model="forms.{{ $formId }}.total_inspect" class="w-full border bg-gray-500 p-2 rounded" placeholder="Enter Total Inspect">
                </div>
            </div>

            <x-ui.dropdown.hf-modal :form-id="$formId" :form="$form" :auto-methods="$autoMethods" :has-error-form="$hasErrorForm" />

            <div class="flex flex-col sm:flex-row gap-6 mt-4">
                <div class="w-full sm:w-1/2 flex justify-center">
                    <livewire:partials::defects
                        :formId="$formId"
                        :loadedDefects="$form['defects']"
                        :loadedSmallDefects="$form['smallDefects']"
                        :dispatchPrefix="'operator'"
                        :key="'defects-'.$formId"
                        :systemname="$systemName" />
                </div>
                <div class="w-full sm:w-1/2 flex justify-center">
                    <livewire:partials::rework
                        :formId="$formId"
                         :loadedRework="$form['rework']"
                        :dispatchPrefix="'operator'"
                        :key="'reworks-'.$formId"
                        :systemname="$systemName" />
                </div>
            </div>

            <div class="w-full">
                <label class="block text-sm font-medium">Total Good Qty.</label>
                <input type="text" wire:model="forms.{{ $formId }}.GoodQty" class="w-full border p-2 rounded" readonly>
            </div>

            <div class="w-full">
                <label class="block text-sm font-medium">Remarks</label>
                <div x-data="{
                    saveTimer: null,
                    buttonTimer: null,
                    typing() {
                        window.dispatchEvent(new CustomEvent('remarks-typing', { detail: { disabled: true } }));
                        clearTimeout(this.saveTimer);
                        clearTimeout(this.buttonTimer);
                        this.saveTimer = setTimeout(() => { $wire.saveRemarks('{{ $formId }}'); }, 500);
                        this.buttonTimer = setTimeout(() => {
                            window.dispatchEvent(new CustomEvent('remarks-typing', { detail: { disabled: false } }));
                        }, 1000);
                    }
                }">
                    <input type="text" wire:model="forms.{{ $formId }}.Remarks" @input="typing()" class="w-full border p-2 rounded">
                </div>
            </div>
        </div>
    </div>
</div>