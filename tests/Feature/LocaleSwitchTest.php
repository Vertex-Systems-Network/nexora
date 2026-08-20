<?php

declare(strict_types=1);

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class LocaleSwitchTest extends TestCase
{
    #[Test]
    public function locale_can_be_changed_while_the_application_is_not_installed(): void
    {
        config()->set('installer.bypass', false);
        config()->set('installer.lock_path', storage_path('framework/non-existent-install.lock'));
        @unlink((string) config('installer.lock_path'));

        $response = $this->from('/install')->post('/locale', ['locale' => 'tr']);

        $response->assertRedirect('/install');
        $this->assertSame('tr', session('locale'));
    }

    #[Test]
    public function unsupported_locale_is_rejected(): void
    {
        $this->from('/install')->post('/locale', ['locale' => 'xx'])->assertSessionHasErrors('locale');
    }
}
