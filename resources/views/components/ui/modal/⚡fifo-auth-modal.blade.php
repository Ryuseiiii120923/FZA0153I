<?php // resources/views/components/fifo-auth-modal.blade.php

use App\Services\Authorization\AutorizationService;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public bool $show = false;
    public string|null $authMessage = null;
    public string|null $ppf = null;

    public string $username = '';
    public string $password = '';
    public string $remark = '';
    public string|null $error = null;

    /**
     * Opens the dialog for a given PPF/message. Any component on the
     * page can trigger this with:
     *   $this->dispatch('open-fifo-auth', ppf: $ppf, message: $message);
     */
    #[On('open-fifo-auth')]
    public function open(string|null $ppf, string $authMessage)
    {
        $this->ppf = $ppf;
        $this->authMessage = $authMessage;
        $this->username = '';
        $this->password = '';
        $this->remark = '';
        $this->error = null;
        $this->show = true;
    }

    public function authorizeOverride()
    {
        $this->error = null;

        $this->validate([
            'username' => 'required|string',
            'password' => 'required|string',
            'remark'   => 'required|string',
        ], [], [
            'username' => 'username',
            'password' => 'password',
            'remark'   => 'remark',
        ]);

        $result = app(AutorizationService::class)->authorize(
            $this->username,
            $this->password,
            'FZA0153I4'
        );

        if (!$result['success']) {
            $this->error = $result['message'];
            $this->password = '';
            return;
        }

        $this->dispatch('FifoOverrideConfirmed', [
            'ppf'             => $this->ppf,
            'advance'         => true,
            'confirmUserCode' => $result['employee']->社員CD,
            'confirmRemark'   => $this->remark,
        ]);

        $this->close();
    }

    public function cancel()
    {
        $this->close();
    }

    private function close()
    {
        $this->show = false;
        $this->authMessage = null;
        $this->ppf = null;
        $this->username = '';
        $this->password = '';
        $this->remark = '';
        $this->error = null;
    }
};
?>

<div
    x-data="{ show: false }"
    x-init="$watch('$wire.show', value => show = value)"
    x-show="show"
    x-cloak
    x-trap.inert.noscroll="show"
    @keydown.escape.window="$wire.cancel()"
    class="fixed inset-0 z-50 flex items-center justify-center"
    style="display: none;">

    <!-- Backdrop -->
    <div class="absolute inset-0 bg-black/50"></div>

    <!-- Dialog -->
    <div
        x-show="show"
        x-transition
        @click.stop
        class="relative bg-white rounded-lg shadow-xl w-6/10 sm:w-96 p-6">

        <h2 class="text-lg font-semibold text-black mb-2">Supervisor Authorization Required</h2>

        @if($authMessage)
        <p class="text-sm text-gray-700 mb-4">{{ $authMessage }}</p>
        @endif

        <form wire:submit.prevent="authorizeOverride" class="space-y-3">
            <div>
                <label for="fifoAuthUsername" class="block text-sm font-medium text-black">Username</label>
                <input
                    type="text"
                    id="fifoAuthUsername"
                    autocomplete="off"
                    wire:model="username"
                    class="mt-1 block w-full border border-black rounded-md px-2 py-1 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('username')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="fifoAuthPassword" class="block text-sm font-medium text-black">Password</label>
                <input
                    type="password"
                    id="fifoAuthPassword"
                    autocomplete="off"
                    wire:model="password"
                    class="mt-1 block w-full border border-black rounded-md px-2 py-1 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('password')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="fifoAuthRemark" class="block text-sm font-medium text-black">Remark</label>
                <textarea
                    id="fifoAuthRemark"
                    rows="2"
                    wire:model="remark"
                    placeholder="Reason for overriding FIFO order"
                    class="mt-1 block w-full border border-black rounded-md px-2 py-1 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                @error('remark')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            @if($error)
            <p class="text-red-500 text-sm">{{ $error }}</p>
            @endif

            <div class="flex justify-end gap-2 pt-2 mt-4">
                <button
                    type="button"
                    wire:click="cancel"
                    class="px-4 py-1 rounded-md border border-gray-400 text-gray-700 hover:bg-gray-100 transition duration-200">
                    Cancel
                </button>
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="authorizeOverride"
                    class="px-4 py-1 bg-[#0F3C89] text-white rounded-md hover:bg-blue-800 transition duration-200">
                    <span wire:loading.remove wire:target="authorizeOverride">Authorize</span>
                    <span wire:loading wire:target="authorizeOverride">Checking...</span>
                </button>
            </div>
        </form>
    </div>
</div>