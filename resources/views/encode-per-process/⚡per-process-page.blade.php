<?php

use Livewire\Component;

new class extends Component
{
    public string $action = '';
    
    public function setAction($action){

    }
};
?>

<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="flex justify-center gap-3 p-6">
        @foreach([
        'add' => ['ti-plus', 'Add/Update', 'blue'],
        'delete' => ['ti-trash', 'Delete', 'red'],
        ] as $key => [$icon, $label, $color])
        <button
            wire:click="setAction('{{ $key }}')"
            @class([ 'flex flex-col items-center gap-1.5 py-3 flex-1 rounded-xl border-2 text-sm font-medium transition-all' , 'border-gray-200 text-gray-500 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-400'=> $action !== $key,
            'border-blue-600 bg-blue-50 text-blue-700' => $action === $key && $key === 'add',
            'border-red-700 bg-red-50 text-red-700' => $action === $key && $key === 'delete',
            ])>
            <i class="ti {{ $icon }} text-xl"></i>
            <span>{{ $label }}</span>
        </button>
        @endforeach
    </div>
    <div class="w-full justify-center">
        <div class="w-full flex flex-col gap-4 mt-3">
            <x-ui.round-notification />
        <livewire:partials::opeartor.operator-table/>
        </div>
    </div>


    <div class="flex items-center justify-center mt-4 @if($this->selectedPpf === 0) opacity-50 cursor-not-allowed @endif">
        @if($action !== '' && $action !== 'view')
        <div class="flex justify-center p-6">
            <button
                wire:click="submit"
                @if($action=='delete' ) @click.prevent="if (confirm('Are you sure you want to delete this ppf?')) $wire.submit()" @endif
                @class([ 'px-12 py-2.5 rounded-lg text-white text-sm font-medium transition' , 'bg-blue-700 hover:bg-blue-800'=> $action === 'add',
                'bg-green-700 hover:bg-green-800' => $action === 'edit',
                'bg-red-700 hover:bg-red-800' => $action === 'delete',
                ])>
                {{ match($action) {
            'add'    => 'Submit',
            'delete' => 'Confirm Delete',
            default  => 'Submit'
        } }}
            </button>
        </div>
        @endif
    </div>
</div>