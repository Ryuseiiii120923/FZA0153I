<?php
// app/Services/ProcessRecordApiService.php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;

class ProcessRecordApiService
{
    public function fetchPdf(string $ppf): string
    {
        $response = Http::withHeaders([
                'X-API-Key' => config('services.process_record_api.key'),
            ])
            ->timeout(30)
            ->get(config('services.process_record_api.base_url') . "/process-record/{$ppf}/pdf");

        if ($response->failed()) {
            throw new \RuntimeException(
                "Failed to fetch process record for PPF {$ppf}: " . $response->json('message', $response->status())
            );
        }

        return $response->body(); // raw PDF bytes
    }
}