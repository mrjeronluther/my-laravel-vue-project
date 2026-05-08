<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware {
    protected $rootView = 'app';

    public function version(Request $request): ?string {
        return parent::version($request);
    }

    public function share(Request $request): array {
        return array_merge(parent::share($request), [
            // Matches exactly with triggerAlert() types in Vue
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'alert'   => fn () => $request->session()->get('alert'),
                'error'   => fn () => $request->session()->get('error'),
            ],
        ]);
    }
}