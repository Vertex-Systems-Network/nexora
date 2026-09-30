<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;

final class N100Rc3RuntimeFrontendArchitectureTest extends TestCase
{
    public function test_runtime_middleware_and_inertia_v3_frontend_contracts_are_guarded(): void
    {
        $root=dirname(__DIR__,2);
        $middleware=(string)file_get_contents($root.'/app/Http/Middleware/RuntimeNodeHeartbeat.php');
        $automation=(string)file_get_contents($root.'/resources/js/admin/pages/Admin/Automation/Form.tsx');
        $enterprise=(string)file_get_contents($root.'/resources/js/admin/pages/Admin/Enterprise/OrganizationShow.tsx');
        $writer=(string)file_get_contents($root.'/resources/js/admin/components/writer/BlockEditor.tsx');
        $helpdeskNav=(string)file_get_contents($root.'/resources/js/admin/pages/Admin/Helpdesk/_HelpdeskNav.tsx');
        $membershipNav=(string)file_get_contents($root.'/resources/js/admin/pages/Admin/Membership/_MembershipNav.tsx');
        $certifier=(string)file_get_contents($root.'/scripts/certify-release.php');
        self::assertMatchesRegularExpression('/function\s+handle\s*\(\s*Request\s+\$request\s*,\s*Closure\s+\$next\s*\)\s*:\s*Response/',$middleware);
        self::assertStringContainsString('private readonly NodeIdentity $identity',$middleware);
        self::assertStringContainsString('private readonly NodeManager $nodes',$middleware);
        self::assertStringContainsString('private readonly InstallationState $installation',$middleware);
        self::assertStringContainsString('if (! $this->installation->isInstalled())',$middleware);
        self::assertStringContainsString('useForm<WorkflowFormData>',$automation);
        self::assertStringNotContainsString('Record<string,unknown>',$automation);
        self::assertStringNotContainsString('Record<string, unknown>',$automation);
        self::assertStringContainsString('Deliberate shallow boundary: SSO configuration and secret payload default server-side.',$enterprise);
        self::assertStringContainsString('const ssoForm = useForm({',$enterprise);
        self::assertStringContainsString('export type WriterValue =',$writer);
        self::assertStringContainsString('ButtonLink',$helpdeskNav);
        self::assertStringContainsString('ButtonLink',$membershipNav);
        self::assertStringNotContainsString('NavLink',$helpdeskNav);
        self::assertStringNotContainsString('NavLink',$membershipNav);
        self::assertStringContainsString('frontend-contract-verify.php',$certifier);

        $iterator=new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root.'/resources/js/admin',\FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if (! $file->isFile() || ! in_array(strtolower($file->getExtension()),['ts','tsx'],true)) continue;
            $source=(string)file_get_contents($file->getPathname());
            self::assertDoesNotMatchRegularExpression('/\.transform\s*\([\s\S]*?\)\s*\.\s*(?:get|post|put|patch|delete)\s*\(/m',$source,$file->getPathname());
        }
    }
}
