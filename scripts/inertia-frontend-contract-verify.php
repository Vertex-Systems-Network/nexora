<?php

declare(strict_types=1);
$root=dirname(__DIR__);
require_once $root.'/scripts/lib/inertia-frontend-contracts.php';
$result=nexoraAnalyzeInertiaFrontendContracts($root);
if(!$result['ok']){
    fwrite(STDERR,"[Nexora Inertia Frontend Contracts] FAIL\n - ".implode("\n - ",$result['errors'])."\n");
    exit(1);
}
$m=$result['metrics'];
fwrite(STDOUT,"[Nexora Inertia Frontend Contracts] PASS — {$m['admin_ts_files']} Admin TS/TSX files; {$m['laragon_error_files']} Laragon error targets guarded; transform chains {$m['transform_chains']}; unsafe router payloads {$m['unsafe_router_payloads']}; NavLink children {$m['navlink_children']}; unsafe useForm unknown {$m['unsafe_useform_unknown']}.\n");
foreach($result['warnings'] as $warning) fwrite(STDOUT,"[WARN] {$warning}\n");
