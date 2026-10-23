<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Http\Middleware\ApplyContentSecurityPolicy;
use Database\Seeders\Core\NexoraCoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

final class ContentSecurityPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_html_pages_receive_a_nonce_based_enforced_policy(): void
    {
        $this->seed(NexoraCoreSeeder::class);

        $response = $this->get('/login');

        $response->assertOk();
        $policy = (string) $response->headers->get('Content-Security-Policy');
        $this->assertMatchesRegularExpression("/script-src 'self' 'nonce-[A-Za-z0-9]{40}'/", $policy);
        $this->assertStringContainsString("object-src 'none'", $policy);
        $this->assertStringContainsString("base-uri 'self'", $policy);
        $this->assertStringContainsString("frame-ancestors 'self'", $policy);
        $this->assertStringNotContainsString("'unsafe-eval'", $policy);
        $this->assertDoesNotMatchRegularExpression("/script-src[^;]*'unsafe-inline'/", $policy);
    }

    public function test_each_request_gets_a_fresh_nonce(): void
    {
        $this->seed(NexoraCoreSeeder::class);

        $first = (string) $this->get('/login')->headers->get('Content-Security-Policy');
        $second = (string) $this->get('/login')->headers->get('Content-Security-Policy');

        preg_match("/'nonce-([^']+)'/", $first, $a);
        preg_match("/'nonce-([^']+)'/", $second, $b);
        $this->assertNotEmpty($a[1] ?? null);
        $this->assertNotSame($a[1], $b[1] ?? null);
    }

    public function test_report_only_and_off_modes(): void
    {
        $this->seed(NexoraCoreSeeder::class);

        config(['nexora-performance.headers.csp' => 'report-only']);
        $response = $this->get('/login');
        $this->assertFalse($response->headers->has('Content-Security-Policy'));
        $this->assertTrue($response->headers->has('Content-Security-Policy-Report-Only'));

        config(['nexora-performance.headers.csp' => 'off']);
        $response = $this->get('/login');
        $this->assertFalse($response->headers->has('Content-Security-Policy'));
        $this->assertFalse($response->headers->has('Content-Security-Policy-Report-Only'));
    }

    public function test_unknown_mode_fails_closed_to_enforce(): void
    {
        $this->seed(NexoraCoreSeeder::class);
        config(['nexora-performance.headers.csp' => 'disabled-by-typo']);

        $this->assertTrue($this->get('/login')->headers->has('Content-Security-Policy'));
    }

    public function test_existing_policy_and_non_html_responses_are_not_overwritten(): void
    {
        $middleware = new ApplyContentSecurityPolicy;

        $own = $middleware->handle(Request::create('/media/x'), fn () => new Response('x', 200, [
            'Content-Type' => 'image/png',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
        ]));
        $this->assertSame("default-src 'none'; sandbox", $own->headers->get('Content-Security-Policy'));

        $json = $middleware->handle(Request::create('/x'), fn () => new Response('{}', 200, ['Content-Type' => 'application/json']));
        $this->assertFalse($json->headers->has('Content-Security-Policy'));
    }

    public function test_vite_tags_carry_the_same_nonce_as_the_header(): void
    {
        $this->seed(NexoraCoreSeeder::class);

        $response = $this->get('/login');
        preg_match("/'nonce-([^']+)'/", (string) $response->headers->get('Content-Security-Policy'), $m);

        $this->assertSame($m[1] ?? null, Vite::cspNonce());
    }
}
