<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DataConnection;
use App\Models\CrmOrganization;
use App\Models\CrmContact;
use App\Models\CrmOpportunity;
use App\Models\Role;
use App\Models\StudioCanvas;
use App\Models\User;
use App\Nexora\Data\ConnectionCatalog;
use App\Nexora\Discovery\Search\SearchIndexer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SearchController extends Controller
{
    public function __construct(private ConnectionCatalog $connectionCatalog, private SearchIndexer $contentSearch)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $request->validate(['q' => ['required', 'string', 'min:2', 'max:100']]);
        $q = trim($request->string('q')->toString());
        $needle = strtolower($q);
        $results = [];

        $destinations = [
            [
                'permission' => 'users.view',
                'title' => 'Users',
                'subtitle' => 'Identity management',
                'href' => route('admin.users.index', absolute: false),
            ],
            [
                'permission' => 'roles.view',
                'title' => 'Roles & Access',
                'subtitle' => 'Permissions and access control',
                'href' => route('admin.roles.index', absolute: false),
            ],
            [
                'permission' => 'audit.view',
                'title' => 'Audit Trail',
                'subtitle' => 'Security and operational history',
                'href' => route('admin.audit.index', absolute: false),
            ],
            [
                'permission' => 'studio.view',
                'title' => 'Nexora Studio',
                'subtitle' => 'Visual builder, responsive layouts and dynamic bindings',
                'href' => route('admin.studio.index', absolute: false),
            ],
            [
                'permission' => 'discovery.view',
                'title' => 'Search & Analytics',
                'subtitle' => 'Content search, analytics and SEO crawler intelligence',
                'href' => route('admin.discovery.index', absolute: false),
            ],
            [
                'permission' => 'crm.view',
                'title' => 'CRM',
                'subtitle' => 'Organizations, contacts, leads and opportunities',
                'href' => route('admin.crm.index', absolute: false),
            ],
            [
                'permission' => 'data.connections.view',
                'title' => 'Data Connections',
                'subtitle' => 'MongoDB, Redis and cloud data services',
                'href' => route('admin.data.connections.index', absolute: false),
            ],
            [
                'permission' => 'settings.manage',
                'title' => 'Settings',
                'subtitle' => 'Platform customization',
                'href' => route('admin.settings.edit', absolute: false),
            ],
            [
                'permission' => 'system.health.view',
                'title' => 'System Health',
                'subtitle' => 'Runtime diagnostics',
                'href' => route('admin.system.health', absolute: false),
            ],
            [
                'permission' => 'system.modules.view',
                'title' => 'Runtime Modules',
                'subtitle' => 'Kernel module registry and dependency order',
                'href' => route('admin.system.modules', absolute: false),
            ],
            [
                'permission' => 'system.capabilities.view',
                'title' => 'Runtime Capabilities',
                'subtitle' => 'Zero-trust platform capability catalog',
                'href' => route('admin.system.capabilities', absolute: false),
            ],
            [
                'permission' => 'security.sentinel.view',
                'title' => 'Nexora Sentinel',
                'subtitle' => 'Quarantine, package scans and security findings',
                'href' => route('admin.security.sentinel.index', absolute: false),
            ],
        ];

        foreach ($destinations as $destination) {
            $matches = str_contains(strtolower($destination['title']), $needle)
                || str_contains(strtolower($destination['subtitle']), $needle);

            if ($request->user()->hasPermission($destination['permission']) && $matches) {
                $results[] = [
                    'type' => 'command',
                    'title' => $destination['title'],
                    'subtitle' => $destination['subtitle'],
                    'href' => $destination['href'],
                ];
            }
        }


        if ($request->user()->hasPermission('documents.view')) {
            foreach ($this->contentSearch->search($q, false, 8) as $result) {
                $isMedia = $result['resource_type'] === 'media';
                if ($isMedia && ! $request->user()->hasPermission('media.view')) continue;
                $results[] = [
                    'type' => $isMedia ? 'media' : 'document',
                    'title' => $result['title'],
                    'subtitle' => $isMedia ? 'Media Library · Indexed metadata' : ucfirst(str_replace('_', ' ', (string) $result['status'])).' · Indexed content',
                    'href' => $isMedia ? route('admin.media.index', ['search'=>$result['title']], false) : route('admin.documents.edit', $result['resource_id'], false),
                ];
            }
        }

        if ($request->user()->hasPermission('users.view')) {
            $users = User::query()
                ->where(function ($query) use ($q): void {
                    $query->where('name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%");
                })
                ->limit(6)
                ->get(['id', 'name', 'email']);

            foreach ($users as $user) {
                $results[] = [
                    'type' => 'user',
                    'title' => $user->name,
                    'subtitle' => $user->email,
                    'href' => route('admin.users.edit', $user, false),
                ];
            }
        }

        if ($request->user()->hasPermission('crm.view')) {
            $organizations = CrmOrganization::query()->where(function ($query) use ($q): void {
                $query->where('name', 'like', "%{$q}%")->orWhere('domain', 'like', "%{$q}%");
            })->limit(4)->get(['id','name','domain']);
            foreach ($organizations as $organization) $results[] = ['type'=>'crm-organization','title'=>$organization->name,'subtitle'=>'CRM organization'.($organization->domain ? ' · '.$organization->domain : ''),'href'=>route('admin.crm.organizations.show',$organization,false)];

            $contacts = CrmContact::query()->where(function ($query) use ($q): void {
                $query->where('display_name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%");
            })->limit(4)->get(['id','display_name','email']);
            foreach ($contacts as $contact) $results[] = ['type'=>'crm-contact','title'=>$contact->display_name,'subtitle'=>'CRM contact'.($contact->email ? ' · '.$contact->email : ''),'href'=>route('admin.crm.contacts.show',$contact,false)];

            $opportunities = CrmOpportunity::query()->where('name','like',"%{$q}%")->limit(4)->get(['id','name','status']);
            foreach ($opportunities as $opportunity) $results[] = ['type'=>'crm-opportunity','title'=>$opportunity->name,'subtitle'=>'CRM opportunity · '.ucfirst($opportunity->status),'href'=>route('admin.crm.opportunities.show',$opportunity,false)];
        }

        if ($request->user()->hasPermission('data.connections.view')) {
            $connections = DataConnection::query()
                ->where(function ($query) use ($q): void {
                    $query->where('name', 'like', "%{$q}%")
                        ->orWhere('driver', 'like', "%{$q}%")
                        ->orWhere('provider', 'like', "%{$q}%");
                })
                ->limit(6)
                ->get(['id', 'name', 'driver', 'provider']);

            foreach ($connections as $connection) {
                $definition = $this->connectionCatalog->all()[$connection->driver] ?? null;
                $rawName = trim((string) $connection->name);
                $title = $rawName === '' || $rawName === $connection->driver
                    ? (string) ($definition['label'] ?? $connection->driver)
                    : $rawName;
                $results[] = [
                    'type' => 'data-connection',
                    'title' => $title,
                    'subtitle' => (string) ($definition['kind'] ?? 'Data service'),
                    'href' => route('admin.data.connections.index', absolute: false),
                ];
            }
        }

        if ($request->user()->hasPermission('studio.view')) {
            $canvases = StudioCanvas::query()->where('name', 'like', "%{$q}%")->limit(6)->get(['id', 'name', 'scope', 'status']);
            foreach ($canvases as $canvas) {
                $results[] = [
                    'type' => 'studio-canvas',
                    'title' => $canvas->name,
                    'subtitle' => ucfirst(str_replace('-', ' ', (string) $canvas->scope)).' · '.ucfirst((string) $canvas->status),
                    'href' => route('admin.studio.edit', $canvas, false),
                ];
            }
        }

        if ($request->user()->hasPermission('roles.view')) {
            $roles = Role::query()
                ->where(function ($query) use ($q): void {
                    $query->where('name', 'like', "%{$q}%")
                        ->orWhere('slug', 'like', "%{$q}%");
                })
                ->limit(6)
                ->get(['id', 'name', 'slug']);

            foreach ($roles as $role) {
                $results[] = [
                    'type' => 'role',
                    'title' => $role->name,
                    'subtitle' => $role->slug,
                    'href' => route('admin.roles.edit', $role, false),
                ];
            }
        }

        return response()->json(['results' => array_slice($results, 0, 10)]);
    }
}
