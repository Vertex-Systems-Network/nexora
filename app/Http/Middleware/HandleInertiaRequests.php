<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\AdminNotification;
use App\Models\EnterpriseOrganization;
use App\Models\EnterpriseOrganizationMember;
use App\Models\User;
use App\Nexora\Enterprise\Services\TenantContext;
use Illuminate\Support\Facades\Schema;

use App\Nexora\Foundation\Contracts\AdminNavigationContract;
use App\Nexora\Foundation\Contracts\SettingsContract;
use App\Nexora\Installation\InstallationState;
use App\Nexora\Cloud\Services\RuntimeDeploymentIdentity;
use Illuminate\Http\Request;
use Inertia\Middleware;

final class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        if (! app(InstallationState::class)->isInstalled()) {
            $manifest = public_path('build/manifest.json');
            $hash = is_file($manifest) ? hash_file('sha256', $manifest) : false;

            return is_string($hash) && $hash !== ''
                ? $hash
                : 'bootstrap-'.(string) config('nexora.version', 'unknown');
        }

        return app(RuntimeDeploymentIdentity::class)->assetVersion();
    }

    public function share(Request $request): array
    {
        if (! app(InstallationState::class)->isInstalled()) {
            return [
                ...parent::share($request),
                'app' => [
                    'name' => config('app.name', 'Nexora'),
                    'environment' => app()->environment(),
                    'deployment' => [
                        'mode' => 'bootstrap',
                        'version' => (string) config('nexora.version', 'unknown'),
                        'generation' => (string) config('installer.source.expected_generation', 'unknown'),
                    ],
                ],
                'auth' => ['user' => null],
                'adminNavigation' => [],
                'appearance' => ['theme' => 'system', 'primary' => '#7C3AED', 'density' => 'comfortable', 'radius' => 'medium'],
                'notifications' => ['unread' => 0],
                'enterprise' => ['current' => null, 'available' => [], 'memberRole' => null, 'impersonation' => null],
                'localization' => $this->localization(),
                'flash' => [
                    'success' => fn () => $request->session()->get('success'),
                    'error' => fn () => $request->session()->get('error'),
                    'warning' => fn () => $request->session()->get('warning'),
                ],
            ];
        }
        $settings = app(SettingsContract::class);
        $navigation = app(AdminNavigationContract::class);
        $user = $request->user();

        return [
            ...parent::share($request),
            'app' => [
                'name' => $settings->get('app.name', config('app.name', 'Nexora')),
                'environment' => app()->environment(),
                'deployment' => app(RuntimeDeploymentIdentity::class)->current(),
            ],
            'auth' => [
                'user' => fn () => $user ? [
                    ...$user->only('id', 'name', 'email', 'email_verified_at', 'status', 'timezone', 'locale'),
                    'permissions' => $user->permissionSlugs(),
                ] : null,
            ],
            'adminNavigation' => fn () => $user?->canAccessAdmin()
                ? array_values(array_filter($navigation->all(), static fn (array $item): bool => ! isset($item['permission']) || $user->hasPermission((string) $item['permission'])))
                : [],
            'appearance' => [
                'theme' => $settings->get('appearance.theme', 'system'),
                'primary' => $settings->get('appearance.primary', '#7C3AED'),
                'density' => $settings->get('appearance.density', 'comfortable'),
                'radius' => $settings->get('appearance.radius', 'medium'),
            ],
            'notifications' => [
                'unread' => fn () => $user?->hasPermission('notifications.view')
                    ? AdminNotification::query()
                        ->where('user_id', $user->id)
                        ->whereNull('read_at')
                        ->count()
                    : 0,
            ],
            'enterprise' => function () use ($request, $user): array {
                if ($user === null || ! Schema::hasTable('nx_enterprise_organizations')) return ['current'=>null,'available'=>[],'memberRole'=>null,'impersonation'=>null];
                $context = app(TenantContext::class);
                $current = $context->organization();
                $available = $user->hasRole('super-admin')
                    ? EnterpriseOrganization::query()->where('status','active')->orderByDesc('is_default')->orderBy('name')->get(['id','name','slug'])
                    : EnterpriseOrganization::query()->where('status','active')->whereHas('members', fn ($q) => $q->where('user_id',$user->id)->where('status','active'))->orderBy('name')->get(['id','name','slug']);
                $memberRole = $current ? EnterpriseOrganizationMember::query()->where('organization_id',$current->id)->where('user_id',$user->id)->where('status','active')->value('role') : null;
                $actorId=(int)$request->session()->get('nexora.enterprise.impersonator_id',0);
                $actor=$actorId>0?User::query()->find($actorId):null;
                return [
                    'current'=>$current?->only(['id','name','slug','status','timezone','locale']),
                    'available'=>$available->map(fn($o)=>['id'=>$o->id,'name'=>$o->name,'slug'=>$o->slug])->values()->all(),
                    'memberRole'=>$memberRole,
                    'impersonation'=>$actor?['active'=>true,'actor_id'=>$actor->id,'actor_name'=>$actor->name,'target_name'=>$user->name]:null,
                ];
            },
            'localization' => $this->localization(),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
            ],
        ];
    }
    /** @return array<string,mixed> */
    private function localization(): array
    {
        $supported = (array) config('localization.supported', ['en' => ['name' => 'English', 'native' => 'English', 'dir' => 'ltr']]);
        $locale = app()->getLocale();

        return [
            'locale' => $locale,
            'direction' => (string) ($supported[$locale]['dir'] ?? 'ltr'),
            'supported' => array_map(static fn (array $item, string $code): array => [
                'code' => $code,
                'name' => (string) ($item['name'] ?? $code),
                'native' => (string) ($item['native'] ?? $item['name'] ?? $code),
                'country' => (string) ($item['country'] ?? ''),
                'flag' => (string) ($item['flag'] ?? '🌐'),
                'flagUrl' => (string) ($item['flag_asset'] ?? ''),
                'direction' => (string) ($item['dir'] ?? 'ltr'),
            ], $supported, array_keys($supported)),
            'messages' => [
                'language' => __('nexora.language'),
                'controlCenter' => __('nexora.control_center'),
                'search' => __('nexora.search'),
                'signOut' => __('nexora.sign_out'),
            ],
        ];
    }

}
