<?php

namespace App\Http\Middleware;

use App\Enums\TeamStyle;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Tightenco\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): string|null
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $request->user(),
            ],
            'ziggy' => function () use ($request) {
                return array_merge((new Ziggy)->toArray(), [
                    'location' => $request->url(),
                ]);
            },
            'teamStyles' => fn () => [
                'tactical' => [
                    'labels' => TeamStyle::TACTICAL_LABELS,
                    'icons'  => TeamStyle::TACTICAL_ICONS,
                ],
                'philosophy' => [
                    'labels' => TeamStyle::PHILOSOPHY_LABELS,
                    'icons'  => TeamStyle::PHILOSOPHY_ICONS,
                ],
            ],
        ]);
    }
}
