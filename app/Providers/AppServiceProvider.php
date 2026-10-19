<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Mcp\Client;
use Laravel\Mcp\Facades\Mcp;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Mcp::registerClient('github', function () {
            $client = Client::web((string) config('services.github_mcp.url', 'https://api.githubcopilot.com/mcp/'))
                ->withToken((string) config('services.github.token'));

            $toolsets = config('services.github_mcp.toolsets');
            if (! empty($toolsets)) {
                $client->withHeaders([
                    'X-MCP-Toolsets' => (string) $toolsets,
                ]);
            }

            return $client;
        });
    }
}
