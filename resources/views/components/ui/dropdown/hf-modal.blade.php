@props(['formId', 'form', 'autoMethods', 'hasErrorForm'])

<div x-data="{ open: @entangle('modalOpen.' . $formId) }" x-show="open" x-cloak
    class="fixed inset-0 flex items-center justify-center bg-black/50 z-50"
    @keydown.escape.window="open = false">
    <div class="bg-white rounded-lg p-6 w-11/12 sm:w-1/3">
        <div class="flex flex-col gap-4">
            <div class="w-full mx-auto" @if(in_array(($form['method'] ?? ''), array_merge(['SF'], $autoMethods))) hidden @endif>
                <label for="finishingMachine" class="block text-sm font-medium text-gray-700">Finishing Procedure</label>
                <select id="finishingMachine" class="mt-1 block w-full border border-black rounded-md px-2 py-1"
                    wire:model="forms.{{ $formId }}.finishingProcedure" required>
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
                <input type="text" wire:model.lazy="forms.{{ $formId }}.hf_id" wire:blur="CheckHf('{{ $formId }}')"
                    class="w-full border p-2 rounded" placeholder="Enter HF ID" maxlength="4" pattern="\d{4}"
                    oninput="this.value = this.value.toUpperCase()">
                @error('forms.' . $formId . '.hf_id')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium">Total Inspect</label>
                <input type="number" wire:model="forms.{{ $formId }}.total_inspect" class="w-full border p-2 rounded" placeholder="Enter Total Inspect">
                @error('forms.' . $formId . '.total_inspect')
                <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </div>

            <button wire:click="saveHF('{{ $formId }}')" @if(!empty($hasErrorForm[$formId])) disabled @endif
                class="bg-green-600 text-white px-4 py-2 rounded mt-2 w-full">Save</button>

            <button wire:click="exitHF('{{ $formId }}')" class="bg-green-600 text-white px-4 py-2 rounded mt-2 w-full">Exit</button>
        </div>
    </div>
</div>