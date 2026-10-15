<?php

namespace App\Services\Helpers;

class DraftService
{
    protected function key(string $draftKey): string
    {
        return "draft.form.$draftKey";
    }

    public function syncComponent(string $draftKey, string $component, array $data): array
    {
        $draft = $this->load($draftKey);
        $draft[$component] = $data;
        $draft['updated_at'] = now()->toDateTimeString();

        session()->put($this->key($draftKey), $draft);
        return $draft;
    }

    public function load(string $draftKey): array
    {
        return session()->get($this->key($draftKey), []);
    }

    public function clear(string $draftKey): void
    {
        session()->forget($this->key($draftKey));
    }

    public function hasDraft(string $draftKey): bool
    {
        return session()->has($this->key($draftKey));
    }
}