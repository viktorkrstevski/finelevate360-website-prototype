"""FTPS theme deployment to a dedicated staging-only chroot; no database writes."""
import argparse
from datetime import datetime, timezone
from ftplib import FTP_TLS, error_perm
import hashlib
import io
import json
import os
from pathlib import Path, PurePosixPath
import re
import ssl
import time
from urllib.request import Request, urlopen
import zipfile

from staging_package import HOST, SLUG, ALLOWED, REQUIRED, FORBIDDEN

SENTINEL = ".finelevate-staging-target"
# The staging FTP hostname is exposed by hPanel's additional-account listing
# and resolves directly to the provider-confirmed FTP IP. Never substitute the
# separate file-manager hostname to get around a certificate-name mismatch.
FTPS_HOST = "ftp.stg.finelevate360.com"
FTPS_USER = "u466894776.finelevatestgci"
# Pin the provider-confirmed address while requiring the actual staging FTP
# hostname in the certificate; no plaintext or certificate-exception fallback.
FTPS_ADDRESS = "82.198.228.17"
BACKUP = re.compile(r"\.finelevate-rollback-[0-9]{20}-[a-f0-9]{12}")


def archive_files(path, expected_commit):
    files = {}
    with zipfile.ZipFile(path) as archive:
        if sum(info.file_size for info in archive.infolist()) > 50 * 1024 * 1024:
            raise ValueError("Artifact exceeds 50 MiB")
        for info in archive.infolist():
            parts = PurePosixPath(info.filename).parts
            if (len(parts) < 2 or parts[0] != SLUG or ".." in parts or
                    "\\" in info.filename or info.is_dir() or
                    ((info.external_attr >> 16) & 0o170000) == 0o120000):
                raise ValueError("Unsafe artifact path")
            relative = "/".join(parts[1:])
            if relative in files or FORBIDDEN.search(relative) or PurePosixPath(relative).suffix.lower() not in ALLOWED:
                raise ValueError("Unapproved artifact path")
            files[relative] = archive.read(info)
    manifest = json.loads(files.pop("release.json"))
    if (manifest.get("host") != HOST or manifest.get("theme") != SLUG or
            manifest.get("commit") != expected_commit or
            not re.fullmatch(r"[a-f0-9]{40}", expected_commit)):
        raise ValueError("Artifact target or commit mismatch")
    actual = {name: hashlib.sha256(data).hexdigest() for name, data in files.items()}
    if manifest.get("files") != actual or not REQUIRED.issubset(files):
        raise ValueError("Artifact file integrity mismatch")
    files["release.json"] = (json.dumps(manifest, sort_keys=True, indent=2) + "\n").encode()
    return files


def verify_target(ftp):
    # The FTP account must be jailed at this staging instance's wp-content/themes.
    ftp.cwd("/")
    marker = io.BytesIO()
    try:
        ftp.retrbinary(f"RETR {SENTINEL}", marker.write)
    except error_perm as error:
        if not str(error).startswith("550"):
            raise
        raise ValueError("Staging target marker is not readable at the FTP account root; "
                         "verify the dedicated account directory. No files changed.") from error
    if marker.getvalue().decode().strip() != f"https://{HOST}":
        raise ValueError("Staging chroot sentinel mismatch; no files changed")


def verify_confinement(ftp):
    """Read-only checks at the approved jail boundary; never read other sites."""
    ftp.cwd("/")
    root = ftp.pwd()
    if root != "/":
        raise ValueError("FTPS account does not report the expected jailed root")
    names = {PurePosixPath(name.rstrip("/")).name for name in ftp.nlst()}
    if SENTINEL not in names or names.intersection({"wp-config.php", "wp-admin", "public_html", "domains", "home"}):
        raise ValueError("FTPS root is not the approved staging theme directory")
    try:
        ftp.cwd("..")
    except error_perm as error:
        if not str(error).startswith("550"):
            raise
    if ftp.pwd() != root:
        raise ValueError("FTPS account can leave its approved root")
    ftp.cwd("/")


def exists(ftp, name):
    try:
        ftp.cwd(name)
        ftp.cwd("/")
        return True
    except error_perm as error:
        if not str(error).startswith("550"):
            raise
        return False


def publish(ftp, incoming, release_id):
    backup = f".finelevate-rollback-{release_id}"
    previous = exists(ftp, SLUG)
    if previous:
        ftp.rename(SLUG, backup)
    try:
        ftp.rename(incoming, SLUG)
    except Exception:
        if previous:
            ftp.rename(backup, SLUG)
        raise
    return backup if previous else None


def upload(ftp, files, release_id):
    incoming = f".finelevate-incoming-{release_id}"
    ftp.mkd(incoming)  # Fail rather than reuse an existing/incomplete release.
    directories = {incoming}
    for name, data in sorted(files.items()):
        relative = PurePosixPath(incoming) / name
        for parent in reversed(relative.parents):
            if str(parent) != "." and str(parent) not in directories:
                ftp.mkd(str(parent))
                directories.add(str(parent))
        ftp.storbinary(f"STOR {relative}", io.BytesIO(data))
        received = io.BytesIO()
        ftp.retrbinary(f"RETR {relative}", received.write)
        if received.getvalue() != data:
            raise ValueError(f"FTPS upload integrity check failed: {name}")
    return publish(ftp, incoming, release_id)


def verify_http(expected_commit):
    req = Request(f"https://{HOST}/?finelevate_release={expected_commit}", headers={"Cache-Control": "no-cache"})
    with urlopen(req, timeout=30) as response:
        if response.url.split("/")[2] != HOST:
            raise ValueError("Staging redirected to another host")
        html = response.read(4 * 1024 * 1024).decode("utf-8", errors="replace")
        robots = response.headers.get("X-Robots-Tag", "").lower()
        if "noindex" not in robots and not re.search(r'<meta\b(?=[^>]*\bname=["\x27]robots["\x27])(?=[^>]*\bcontent=["\x27][^"\x27]*noindex)', html, re.I):
            raise ValueError("Staging noindex is missing")
    with urlopen(f"https://{HOST}/wp-content/themes/{SLUG}/release.json?v={expected_commit}", timeout=30) as response:
        if response.url.split("/")[2] != HOST:
            raise ValueError("Release redirected to another host")
        manifest = json.load(response)
    if manifest.get("commit") != expected_commit or manifest.get("host") != HOST:
        raise ValueError("Public release verification mismatch")


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--artifact", type=Path)
    parser.add_argument("--commit")
    parser.add_argument("--rollback-directory")
    parser.add_argument("--check-access", action="store_true", help="Verify TLS, login, marker and confinement without writes")
    args = parser.parse_args()
    if not args.check_access and os.environ.get("STAGING_DEPLOY_ENABLED") != "true":
        raise ValueError("Staging deployment is not enabled")
    if os.environ.get("STAGING_URL") != f"https://{HOST}":
        raise ValueError("Only the approved staging host may be deployed")
    if os.environ.get("STAGING_FTPS_HOST") != FTPS_HOST:
        raise ValueError("Only the verified staging FTPS TLS endpoint is permitted")
    if os.environ.get("STAGING_FTPS_USER") != FTPS_USER:
        raise ValueError("Only the dedicated staging theme-directory FTP account is permitted")
    if args.check_access and (args.artifact or args.commit or args.rollback_directory):
        raise ValueError("Access checks cannot be combined with deployment inputs")
    if not args.check_access and not re.fullmatch(r"[a-f0-9]{40}", args.commit or ""):
        raise ValueError("A full commit SHA is required")
    if args.rollback_directory and not BACKUP.fullmatch(args.rollback_directory):
        raise ValueError("Invalid rollback directory")
    files = None if args.check_access or args.rollback_directory else archive_files(args.artifact, args.commit)
    context = ssl.create_default_context()
    with FTP_TLS(context=context, timeout=60) as ftp:
        ftp.connect(FTPS_ADDRESS, 21)
        ftp.host = FTPS_HOST  # FTP_TLS.login verifies this name during AUTH TLS.
        ftp.login(os.environ["STAGING_FTPS_USER"], os.environ["STAGING_FTPS_PASSWORD"])
        ftp.prot_p()  # Encrypt data as well as control; never fall back to plain FTP.
        verify_target(ftp)
        verify_confinement(ftp)
        if args.check_access:
            print("Staging FTPS TLS, login, marker and jailed-root checks passed. No files changed.")
            return
        release_id = datetime.now(timezone.utc).strftime("%Y%m%d%H%M%S%f") + "-" + args.commit[:12]
        if args.rollback_directory:
            stored = io.BytesIO()
            ftp.retrbinary(f"RETR {args.rollback_directory}/release.json", stored.write)
            old = json.loads(stored.getvalue())
            if old.get("commit") != args.commit or old.get("host") != HOST:
                raise ValueError("Rollback release does not match requested commit and staging host")
            backup = publish(ftp, args.rollback_directory, release_id)
        else:
            backup = upload(ftp, files, release_id)
        try:
            for attempt in range(3):
                try:
                    verify_http(args.commit)
                    break
                except Exception:
                    if attempt == 2:
                        raise
                    time.sleep(5)
        except Exception:
            if backup:
                ftp.rename(SLUG, f".finelevate-failed-{release_id}")
                ftp.rename(backup, SLUG)
                print("Verification failed; the previous theme directory was restored.")
            raise
        print(f"Verified https://{HOST} at {args.commit}; previous release: {backup or 'none'}")


if __name__ == "__main__":
    main()
