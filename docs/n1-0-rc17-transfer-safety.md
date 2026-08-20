# N1.0 RC17 — Large-File / Transfer Safety

RC17 is an operational-hardening pass. It does not add a product domain. Its purpose is to make Nexora's large/untrusted byte-transfer paths bounded, integrity-checked and fail-closed across Windows Laragon and Linux deployments.

## Transfer boundary

`config/nexora-transfers.php` centralizes transfer limits. `App\Nexora\Foundation\Transfers\TransferSafety` provides protected temporary paths, local-capacity preflight, fixed-size chunked reads/writes, partial-write loops, SHA-256/byte verification and destination-local atomic publication. `php artisan nexora:transfer:doctor` probes the protected transfer root and bounded atomic copy path on the actual target environment.

Disk free-space checks are preventive only. Filesystems/quotas can change while a transfer is running, so the write path still treats any short/failed write as fatal and removes unpublished/partial state.

## Media

Media uploads continue to stream through Laravel storage. RC17 additionally deletes a partially written object on failure and reopens the published object to confirm byte count and SHA-256 against the validated source. GD image transformation is not falsely described as fully streaming: RC17 caps the stored source bytes allowed into the in-memory decoder and cleans variants produced by a failed run.

## Marketplace and package archives

Marketplace downloads use protected Nexora temporary storage rather than the process/system temp directory. Download progress and `Content-Length` are bounded when available, and the signed-catalog checksum remains mandatory.

Theme and Extension ZIPs are checked before publication for source size, number of entries, total expanded bytes, per-entry bytes and compression ratio in addition to existing traversal, symbolic-link and case/path portability rules. Extraction uses `ZipArchive::getStream()` plus bounded atomic publication rather than loading arbitrary entries into PHP strings or falling back to partial directory copies.

## Backup surfaces

Cloud/runtime database backup publication and verification use streams and checksum/byte comparison instead of `file_get_contents()`/full `Storage::get()` loads. Installer MySQL/SQLite backups stage beside the final destination, handle partial writes, flush/fsync where available, persist SHA-256 metadata and atomically publish only after success.

Backup download remains Laravel/Symfony streamed delivery. RC17 verifies the runtime backup before serving it and sends no-store/nosniff headers. Byte-range/resumable HTTP downloads are not claimed by this milestone.

## Release boundary

`scripts/transfer-contract-verify.php` is part of source certification. Dependency-backed certification runs `php artisan nexora:transfer:doctor` before and after optimized boot. Protected transfer staging is forbidden from production artifacts, while `config/nexora-transfers.php` is required and its policy hash/transfer capabilities are recorded in release provenance.

N1.0 remains CERTIFYING until reviewed lockfiles, dependency-backed Laravel/Vite runs and browser/restore/real multi-node HA evidence are all green.
