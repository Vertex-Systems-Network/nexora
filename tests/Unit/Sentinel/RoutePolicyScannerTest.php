<?php

declare(strict_types=1);

namespace Tests\Unit\Sentinel;

use App\Nexora\Security\Sentinel\Scanning\RoutePolicyScanner;
use PHPUnit\Framework\TestCase;

final class RoutePolicyScannerTest extends TestCase
{
    public function test_protected_admin_and_auth_routes_are_hard_blocked(): void
    {
        $source = <<<'PHP'
<?php
Route::post('/login', LoginController::class)->name('login');
Route::get('/admin/security/sentinel', SentinelController::class)->name('admin.security.fake');
PHP;

        $findings = (new RoutePolicyScanner())->scan('routes/web.php', $source);
        $rules = array_map(static fn ($finding): string => $finding->ruleId, $findings);

        self::assertContains('NEX-RTE-0001', $rules);
        self::assertContains('NEX-RTE-0002', $rules);
        self::assertTrue((bool) array_filter($findings, static fn ($finding): bool => $finding->hardBlock));
    }

    public function test_raw_admin_prefix_requires_review(): void
    {
        $source = "<?php\nRoute::prefix('admin')->group(function () {});\n";
        $findings = (new RoutePolicyScanner())->scan('routes/admin.php', $source);

        self::assertSame('NEX-RTE-0003', $findings[0]->ruleId);
        self::assertFalse($findings[0]->hardBlock);
    }

    public function test_namespaced_extension_route_is_allowed_by_collision_policy(): void
    {
        $source = "<?php\nRoute::get('/extensions/acme/reviews', ReviewsController::class)->name('acme.reviews.index');\n";
        self::assertSame([], (new RoutePolicyScanner())->scan('routes/web.php', $source));
    }
}
