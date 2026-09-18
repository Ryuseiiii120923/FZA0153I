<?php

use Livewire\Component;

new class extends Component
{
    public string $action = '';
    public int $selectedPpf = 0;

    public function setAction(string $action)
    {
        $this->action = $action;
        $this->dispatch('action-changed', $action);
    }
};
?>

<div>
    <div class="flex justify-center gap-3 p-6">
        @foreach([
        'add' => ['ti-plus', 'Add', 'blue'],
        'edit' => ['ti-edit', 'Update', 'green'],
        'view' => ['ti-eye', 'View', 'yellow'],
        'delete' => ['ti-trash', 'Delete', 'red'],
        ] as $key => [$icon, $label, $color])
        <button
            wire:click="setAction('{{ $key }}')"
            @class([ 'flex flex-col items-center gap-1.5 py-3 flex-1 rounded-xl border-2 text-sm font-medium transition-all' , 'border-gray-200 text-gray-500 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-400'=> $action !== $key,
            'border-blue-600 bg-blue-50 text-blue-700' => $action === $key && $key === 'add',
            'border-green-700 bg-green-50 text-green-700' => $action === $key && $key === 'edit',
            'border-yellow-500 bg-yellow-50 text-yellow-700' => $action === $key && $key === 'view',
            'border-red-700 bg-red-50 text-red-700' => $action === $key && $key === 'delete',
            ])>
            <i class="ti {{ $icon }} text-xl"></i>
            <span>{{ $label }}</span>
        </button>
        @endforeach
    </div>

    <div class="mt-3 w-full rounded-lg">
        <livewire:partials::ppf-look-up />
        <livewire:partials::dashboard.total-inspection />
        <div class="flex flex-col lg:flex-row gap-6 mt-4 items-start justify-center w-full">
            <div class="w-full lg:w-1/2 flex justify-center">
                <livewire:partials::defects />
            </div>
            <div class="w-full lg:w-1/2 flex justify-center">
                <livewire:partials::rework />
            </div>
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
            'edit'   => 'Update',
            'delete' => 'Confirm Delete',
            default  => 'Submit'
        } }}
            </button>
        </div>
        @endif
    </div>
</div>