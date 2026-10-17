#!/usr/bin/env python3
"""Read-only Nexora Phase 1 evidence integrity manifest helper.

Explicit evidence files only. Hashes establish local *integrity*, not author
identity, independent approval, authenticity or Phase 1 acceptance. Retain the
manifest SHA-256 via a separate trusted channel to detect manifest replacement.
"""
from __future__ import annotations

import argparse
import hashlib
import json
import os
from pathlib import Path
import stat
import sys
import tempfile

FORMAT = "nexora-phase1-evidence-integrity-v1"
CHUNK = 1 << 20
MAX_EVIDENCE_BYTES = 100 * 1024 * 1024


class EvidenceError(ValueError):
    pass


def _no_symlink_components(path: Path) -> Path:
    absolute = path.absolute()
    if ".." in absolute.parts:
        raise EvidenceError("Parent traversal is not allowed")
    node = Path(absolute.anchor)
    for component in absolute.parts[1:]:
        node = node / component
        if node.is_symlink():
            raise EvidenceError("Symlink path components are not allowed")
    return absolute


def _root(root: Path) -> Path:
    root = _no_symlink_components(root)
    if not root.is_dir():
        raise EvidenceError("Evidence root must be an existing directory")
    return root


def _relative(name: str) -> Path:
    if not isinstance(name, str) or not name or "\\" in name or "\x00" in name:
        raise EvidenceError("Unsafe evidence filename")
    if any(ord(c) < 32 for c in name) or len(name) > 512:
        raise EvidenceError("Unsafe evidence filename")
    p = Path(name)
    if p.is_absolute() or any(part in (".", "..", "") for part in p.parts):
        raise EvidenceError("Evidence filename must be relative and contained")
    if name != p.as_posix():
        raise EvidenceError("Evidence filename must be canonical POSIX path")
    # Do not archive or hash runtime secrets, keys or environment files.
    parts = [part.lower() for part in p.parts]
    if any(part.startswith(".env") or part.endswith((".key", ".pem", ".p12"))
           or "secret" in part or "credential" in part for part in parts):
        raise EvidenceError("Runtime secrets and credentials must not be inventoried")
    return p


def _hash_file(root: Path, name: str) -> dict:
    p = _relative(name)
    target = _no_symlink_components(root / p)
    if not target.is_relative_to(root):
        raise EvidenceError("Evidence path escapes root")
    try:
        fd = os.open(target, os.O_RDONLY | getattr(os, "O_NOFOLLOW", 0) | getattr(os, "O_NONBLOCK", 0))
    except OSError as ex:
        raise EvidenceError("Evidence file is missing or unreadable") from ex
    try:
        before = os.fstat(fd)
        if not stat.S_ISREG(before.st_mode):
            raise EvidenceError("Evidence is not a regular file")
        if before.st_size > MAX_EVIDENCE_BYTES:
            raise EvidenceError("Evidence file exceeds safe manifest limit")
        digest = hashlib.sha256()
        size = 0
        while True:
            chunk = os.read(fd, CHUNK)
            if not chunk:
                break
            size += len(chunk)
            digest.update(chunk)
        after = os.fstat(fd)
        if (before.st_dev, before.st_ino, before.st_size, before.st_mtime_ns) != (
            after.st_dev, after.st_ino, after.st_size, after.st_mtime_ns
        ) or before.st_size != size:
            raise EvidenceError("Evidence changed while being hashed")
        # Detect path swap after opening (without displaying any content).
        current = os.stat(target, follow_symlinks=False)
        if (current.st_dev, current.st_ino) != (before.st_dev, before.st_ino):
            raise EvidenceError("Evidence path changed while being hashed")
        return {"path": name, "size_bytes": size, "sha256": digest.hexdigest()}
    finally:
        os.close(fd)


def _validated_manifest_path(root: Path, path: Path) -> Path:
    path = _no_symlink_components(path)
    if path.is_relative_to(root):
        raise EvidenceError("Manifest must live outside the evidence root")
    if not path.parent.is_dir():
        raise EvidenceError("Manifest parent directory must exist")
    return path


def seal(root: Path, files: list[str], manifest_path: Path) -> dict:
    root = _root(root)
    target = _validated_manifest_path(root, manifest_path)
    if not files or len(set(files)) != len(files):
        raise EvidenceError("Provide unique explicit evidence file names")
    entries = [_hash_file(root, name) for name in sorted(files)]
    document = {"format": FORMAT, "scope": "integrity_only_not_independent_acceptance", "files": entries}
    data = (json.dumps(document, sort_keys=True, separators=(",", ":"), ensure_ascii=True) + "\n").encode()
    fd, name = tempfile.mkstemp(prefix=".nexora-evidence-", dir=target.parent)
    try:
        with os.fdopen(fd, "wb") as stream:
            stream.write(data)
            stream.flush()
            os.fsync(stream.fileno())
        # An existing manifest can be replaced only by atomic rename, never followed.
        _validated_manifest_path(root, target)
        os.replace(name, target)
    finally:
        if os.path.lexists(name):
            os.unlink(name)
    return {"state": "SEALED", "file_count": len(entries), "manifest_sha256": hashlib.sha256(data).hexdigest()}


def verify(root: Path, manifest_path: Path, expected_sha256: str) -> dict:
    root = _root(root)
    path = _validated_manifest_path(root, manifest_path)
    if len(expected_sha256) != 64 or any(c not in "0123456789abcdef" for c in expected_sha256):
        raise EvidenceError("Expected manifest SHA-256 must be 64 lowercase hex characters")
    fd = os.open(path, os.O_RDONLY | getattr(os, "O_NOFOLLOW", 0))
    try:
        st = os.fstat(fd)
        if not stat.S_ISREG(st.st_mode) or st.st_size > 1024 * 1024:
            raise EvidenceError("Manifest must be a bounded regular file")
        data = os.read(fd, 1024 * 1024 + 1)
    finally:
        os.close(fd)
    if hashlib.sha256(data).hexdigest() != expected_sha256:
        raise EvidenceError("Manifest digest does not match the independently retained anchor")
    try:
        document = json.loads(data)
    except (ValueError, UnicodeDecodeError) as ex:
        raise EvidenceError("Invalid evidence manifest") from ex
    if not isinstance(document, dict) or document.get("format") != FORMAT or document.get("scope") != "integrity_only_not_independent_acceptance":
        raise EvidenceError("Wrong manifest format/scope")
    rows = document.get("files")
    if not isinstance(rows, list) or not rows or len(rows) > 1000:
        raise EvidenceError("Invalid manifest entries")
    names = []
    for row in rows:
        if not isinstance(row, dict) or set(row) != {"path", "size_bytes", "sha256"}:
            raise EvidenceError("Invalid manifest entry")
        if (type(row["size_bytes"]) is not int or row["size_bytes"] < 0 or
                not isinstance(row["sha256"], str)):
            raise EvidenceError("Invalid manifest entry fields")
        names.append(row["path"])
    if names != sorted(set(names)):
        raise EvidenceError("Manifest file list must be unique and sorted")
    mismatched = []
    for row in rows:
        try:
            actual = _hash_file(root, row["path"])
            if actual != row:
                mismatched.append(row["path"])
        except EvidenceError:
            mismatched.append(row["path"])
    if mismatched:
        raise EvidenceError("Evidence integrity FAILED for " + str(len(mismatched)) + " file(s)")
    return {"state": "PASS", "file_count": len(rows), "scope": "integrity_only_not_independent_acceptance"}


def main() -> int:
    ap = argparse.ArgumentParser(description=__doc__)
    ap.add_argument("action", choices=("seal", "verify"))
    ap.add_argument("--root", required=True)
    ap.add_argument("--manifest", required=True)
    ap.add_argument("--file", action="append", default=[])
    ap.add_argument("--expected-sha256", default="")
    args = ap.parse_args()
    try:
        if args.action == "seal":
            report = seal(Path(args.root), args.file, Path(args.manifest))
        else:
            report = verify(Path(args.root), Path(args.manifest), args.expected_sha256)
        print(json.dumps(report, sort_keys=True))
        return 0
    except (EvidenceError, OSError, TypeError) as ex:
        # Never reveal paths, contents, or secret values in failure output.
        print(json.dumps({"state": "FAIL", "reason": type(ex).__name__, "scope": "integrity_only"}))
        return 2


if __name__ == "__main__":
    sys.exit(main())
