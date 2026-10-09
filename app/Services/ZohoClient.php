<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ZohoClient
{
    public function get(string $path, array $query = []): array
    {
        $query['organization_id'] = config('zoho.organization_id');

        if (! app()->runningUnitTests()) {
            usleep(650000); // ponytail: Zoho ~100 req/min; drop if they raise the cap
        }

        $response = Http::withToken($this->token(), 'Zoho-oauthtoken')
            ->acceptJson()
            ->timeout(30)
            ->retry(5, 2000, fn ($e) => method_exists($e, 'response') && $e->response?->status() === 429)
            ->get(rtrim((string) config('zoho.base_url'), '/').$path, $query);

        $payload = $response->json() ?? [];
        if (! $response->successful() || (int) ($payload['code'] ?? 1) !== 0) {
            throw new RuntimeException($payload['message'] ?? 'Zoho request failed: '.$path);
        }

        return $payload;
    }

    public function post(string $path, ?array $body = null): array
    {
        if (! app()->runningUnitTests()) {
            usleep(650000); // ponytail: Zoho ~100 req/min; drop if they raise the cap
        }

        $pending = Http::withToken($this->token(), 'Zoho-oauthtoken')
            ->acceptJson()
            ->asJson()
            ->timeout(30)
            ->retry(5, 2000, fn ($e) => method_exists($e, 'response') && $e->response?->status() === 429)
            ->withQueryParameters(['organization_id' => config('zoho.organization_id')]);

        $url = rtrim((string) config('zoho.base_url'), '/').$path;
        $response = $body === null ? $pending->post($url) : $pending->post($url, $body);

        $payload = $response->json() ?? [];
        if (! $response->successful() || (int) ($payload['code'] ?? 1) !== 0) {
            throw new RuntimeException($payload['message'] ?? 'Zoho request failed: '.$path);
        }

        return $payload;
    }

    public function paginate(string $path, string $key, array $query = []): array
    {
        $page = 1;
        $rows = [];

        do {
            $payload = $this->get($path, $query + ['page' => $page, 'per_page' => 200]);
            $rows = array_merge($rows, $payload[$key] ?? []);
            $more = (bool) ($payload['page_context']['has_more_page'] ?? false);
            $page++;
        } while ($more);

        return $rows;
    }

    public function token(): string
    {
        return Cache::remember('zoho.access_token', 3000, function () {
            $response = Http::asForm()->timeout(20)->post((string) config('zoho.token_url'), [
                'refresh_token' => config('zoho.refresh_token'),
                'client_id' => config('zoho.client_id'),
                'client_secret' => config('zoho.client_secret'),
                'grant_type' => 'refresh_token',
            ])->json() ?? [];

            $token = $response['access_token'] ?? '';
            if ($token === '') {
                throw new RuntimeException($response['error'] ?? 'Zoho token refresh failed');
            }

            return $token;
        });
    }
}
