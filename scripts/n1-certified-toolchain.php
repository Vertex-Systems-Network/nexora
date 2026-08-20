<?php

declare(strict_types=1);$root=dirname(__DIR__);require_once $root.'/scripts/lib/n1-certified-toolchain.php';$write=in_array('--write',$argv,true);try{$data=$write?nexoraWriteCertifiedToolchain($root):null;$errors=nexoraValidateCertifiedToolchain($root,$data);if($errors!==[]){fwrite(STDERR,"[N1.0 Toolchain] FAIL\n - ".implode("\n - ",$errors)."\n");exit(1);}fwrite(STDOUT,"[N1.0 Toolchain] PASS — PHP/Composer/Node/npm executable fingerprints and reviewed locks are frozen.\n");exit(0);}catch(Throwable $e){fwrite(STDERR,"[N1.0 Toolchain] FAIL — {$e->getMessage()}\n");exit(1);}
