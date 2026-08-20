# N1.0 RC16 — Filesystem / Path Portability Certification

Platform: `1.0.0-rc.16`

RC16 is an operational hardening pass, not a new product domain. It adds:

- centralized `AtomicFileWriter` for critical state publication;
- `PortablePath` traversal/Windows-name/case portability policy;
- `FilesystemDoctor` and `nexora:filesystem:doctor`;
- repository case-fold, PSR-4 and App import casing certification;
- ZIP package collision and symbolic-link rejection for Theme/Extension installers;
- dependency-free atomic state publication in the browser deployment bootstrap;
- fsync-backed mutable installation journal writes where the runtime provides `fsync()`.

Full certification still requires reviewed dependency lockfiles and real target-environment Laravel/Vite/operator evidence.
