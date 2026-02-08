<?php

namespace App\Http\Middleware;

use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'storageUsage' => $request->user() ? $this->resolveStorageUsage() : null,
        ];
    }

    /**
     * @return array<string, int|float>|null
     */
    private function resolveStorageUsage(): ?array
    {
        return Cache::remember('sidebar.storage.usage', now()->addMinutes(5), function (): ?array {
            $path = base_path();
            $total = @disk_total_space($path);
            $free = @disk_free_space($path);

            if ($total === false || $free === false || $total <= 0) {
                return null;
            }

            $used = max(0, $total - $free);

            return [
                'totalBytes' => (int) $total,
                'freeBytes' => (int) $free,
                'usedBytes' => (int) $used,
                'usedPercent' => round(($used / $total) * 100, 1),
            ];
        });
    }
}
