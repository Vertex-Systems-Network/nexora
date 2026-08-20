<?php

declare(strict_types=1);

namespace Tests\Unit\Certification;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class FrontendBuildDiagnosticsTest extends TestCase
{
    #[Test]
    public function it_parses_windows_and_standard_typescript_diagnostics_into_one_stable_shape(): void
    {
        require_once base_path('scripts/lib/n1-frontend-build-diagnostics.php');

        $output = <<<'LOG'
D:\laragon\www\nexora\resources\js\admin\pages\Admin\Automation\Form.tsx:17:196 - error TS2322: Example Windows diagnostic.
resources/js/admin/pages/Admin/Cloud/Index.tsx(27,102): error TS2345: Example standard diagnostic.
LOG;

        $diagnostics = \nexoraParseTypeScriptDiagnostics(
            $output,
            'D:/laragon/www/nexora',
        );

        self::assertCount(2, $diagnostics);
        self::assertSame('resources/js/admin/pages/Admin/Automation/Form.tsx', $diagnostics[0]['file']);
        self::assertSame('TS2322', $diagnostics[0]['code']);
        self::assertSame('resources/js/admin/pages/Admin/Cloud/Index.tsx', $diagnostics[1]['file']);
        self::assertSame('TS2345', $diagnostics[1]['code']);
    }

    #[Test]
    public function the_historical_compiler_baseline_remains_exactly_seventy_six_errors_across_eleven_files(): void
    {
        require_once base_path('scripts/lib/n1-frontend-build-diagnostics.php');

        $baseline = \nexoraFrontendBuildHistoricalBaseline();
        $errors = array_sum(array_map(
            static fn (array $entry): int => (int) $entry['historical_errors'],
            $baseline,
        ));

        self::assertCount(11, $baseline);
        self::assertSame(76, $errors);
    }
}
