<?php

declare(strict_types=1);

namespace App\Nexora\Modules\Core;

use App\Nexora\Distribution\Services\DistributionAdapterRegistry;
use App\Nexora\Distribution\Services\NewsletterDistributionAdapter;
use App\Nexora\Distribution\Services\RssDistributionAdapter;
use App\Nexora\Foundation\Contracts\AdminNavigationContract;
use App\Nexora\Foundation\Contracts\ModuleContract;
use App\Nexora\Foundation\Runtime\ModuleDependency;
use App\Nexora\Foundation\Runtime\ModuleManifest;

final readonly class MediaDistributionModule implements ModuleContract
{
    public function __construct(private AdminNavigationContract $navigation, private DistributionAdapterRegistry $adapters) {}

    public function manifest(): ModuleManifest
    {
        return new ModuleManifest(
            identifier: 'nexora.media-distribution',
            name: 'Nexora Media & Distribution',
            version: '0.25.0',
            description: 'Media library, responsive image variants, newsletter audiences/campaigns, RSS and distribution adapters.',
            core: true,
            loadOrder: 50,
            capabilities: [
                'media.assets.read','media.assets.write','media.assets.delete','media.usage.read','media.collections.manage',
                'distribution.newsletter.read','distribution.newsletter.write','distribution.newsletter.send','distribution.adapters.read',
                'admin.navigation.register','filesystem.public',
            ],
            dependencies: [
                new ModuleDependency('nexora.documents', '^0.18'),
                new ModuleDependency('nexora.publishing', '^0.22'),
                new ModuleDependency('nexora.themes', '^0.20'),
            ],
            metadata: [
                'media_types'=>['image','video','audio','document'],
                'distribution_adapters'=>['rss','newsletter'],
                'newsletter_queue'=>true,
            ],
        );
    }

    public function register(): void
    {
        $this->navigation->register([
            'id'=>'media','label'=>'Media Library','href'=>'/admin/media','icon'=>'image','order'=>54,'permission'=>'media.view',
        ]);
        $this->navigation->register([
            'id'=>'distribution','label'=>'Newsletter & Distribution','href'=>'/admin/distribution','icon'=>'send','order'=>58,'permission'=>'distribution.view',
        ]);
    }

    public function boot(): void
    {
        if (! $this->adapters->get('rss')) $this->adapters->register(new RssDistributionAdapter());
        if (! $this->adapters->get('newsletter')) $this->adapters->register(new NewsletterDistributionAdapter());
    }
}
