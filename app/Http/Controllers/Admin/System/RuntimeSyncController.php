<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Nexora\Foundation\Runtime\RuntimeSynchronizer;
use App\Nexora\Security\Audit\AuditManager;
use Illuminate\Http\RedirectResponse;

final class RuntimeSyncController extends Controller
{
    public function __construct(
        private RuntimeSynchronizer $synchronizer,
        private AuditManager $audit,
    ) {
    }

    public function __invoke(): RedirectResponse
    {
        $result = $this->synchronizer->sync();
        $this->audit->record('runtime.synchronized', metadata: $result);

        return back()->with('success', "Runtime synchronized: {$result['modules']} modules and {$result['capabilities']} capabilities.");
    }
}
