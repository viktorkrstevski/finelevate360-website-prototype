import io
import json
from pathlib import Path
import tempfile
import unittest
import zipfile

from staging_package import build, SLUG, check_image_metadata
from staging_deploy import archive_files, verify_target, verify_confinement, publish, upload

COMMIT = 'a' * 40


class MemoryFTP:
    """Small filesystem fake exercises failure recovery without any live writes."""
    def __init__(self):
        self.files = {'.finelevate-staging-target': b'https://stg.finelevate360.com\n'}
        self.dirs = {'/', SLUG}
        self.renames = []
        self.fail_publish = False

    def pwd(self):
        return '/'

    def nlst(self):
        return list(self.files) + [name for name in self.dirs if name != '/']

    def cwd(self, path):
        from ftplib import error_perm
        if path not in self.dirs:
            raise error_perm('550 not found')

    def retrbinary(self, command, callback):
        callback(self.files[command[5:]])

    def storbinary(self, command, data):
        self.files[command[5:]] = data.read()

    def mkd(self, path):
        if path in self.dirs:
            raise ValueError('already exists')
        self.dirs.add(path)

    def rename(self, source, dest):
        if self.fail_publish and source.startswith('.finelevate-incoming-'):
            raise OSError('simulated connection failure')
        self.dirs.remove(source)
        self.dirs.add(dest)
        self.renames.append((source, dest))


class StagingReleaseTests(unittest.TestCase):
    def setUp(self):
        self.temp = tempfile.TemporaryDirectory()
        self.addCleanup(self.temp.cleanup)
        self.root = Path(self.temp.name)
        self.theme = self.root / 'theme'
        self.theme.mkdir()
        for file in ['style.css', 'functions.php', 'index.php']:
            (self.theme / file).write_text('test fixture')
        self.artifact = self.root / 'theme.zip'

    def test_reproducible_artifact_and_integrity(self):
        build(self.theme, self.artifact, COMMIT)
        original = self.artifact.read_bytes()
        self.assertIn('index.php', archive_files(self.artifact, COMMIT))
        build(self.theme, self.artifact, COMMIT)
        self.assertEqual(original, self.artifact.read_bytes())

    def test_wrong_commit_is_rejected(self):
        build(self.theme, self.artifact, COMMIT)
        with self.assertRaises(ValueError):
            archive_files(self.artifact, 'b' * 40)

    def test_embedded_image_metadata_is_rejected(self):
        fixtures = [b'\xff\xd8\xff\xe1\x00\x08Exif\x00\x00',
                    b'\x89PNG\r\n\x1a\n\x00\x00\x00\x00eXIf0000',
                    b'RIFF0000WEBPEXIF\x00\x00\x00\x00']
        for fixture in fixtures:
            with self.assertRaises(ValueError):
                check_image_metadata('photo', fixture)

    def test_private_data_and_symlinks_are_rejected(self):
        for name in ['.env', 'backup.sql', 'wp-config.php', 'secret.pem']:
            file = self.theme / name
            file.write_text('fixture')
            with self.assertRaises(ValueError):
                build(self.theme, self.artifact, COMMIT)
            file.unlink()
        (self.theme / 'external.php').symlink_to(self.root / 'private')
        with self.assertRaises(ValueError):
            build(self.theme, self.artifact, COMMIT)

    def test_tampering_and_path_traversal_are_rejected(self):
        build(self.theme, self.artifact, COMMIT)
        with zipfile.ZipFile(self.artifact, 'a') as archive:
            archive.writestr(SLUG + '/../wp-config.php', 'not allowed')
        with self.assertRaises(ValueError):
            archive_files(self.artifact, COMMIT)

    def test_content_hash_mismatch_is_rejected(self):
        build(self.theme, self.artifact, COMMIT)
        with zipfile.ZipFile(self.artifact) as original:
            entries = {name: original.read(name) for name in original.namelist()}
        entries[SLUG + '/style.css'] = b'tampered'
        with zipfile.ZipFile(self.artifact, 'w') as changed:
            for name, data in entries.items():
                changed.writestr(name, data)
        with self.assertRaises(ValueError):
            archive_files(self.artifact, COMMIT)

    def test_misdirected_ftp_root_stops_before_changes(self):
        ftp = MemoryFTP()
        ftp.files['.finelevate-staging-target'] = b'https://finelevate360.com'
        with self.assertRaises(ValueError):
            verify_target(ftp)
        self.assertEqual(ftp.renames, [])

    def test_jailed_root_preflight_does_not_write(self):
        ftp = MemoryFTP()
        before = (dict(ftp.files), set(ftp.dirs))
        verify_target(ftp)
        verify_confinement(ftp)
        self.assertEqual((ftp.files, ftp.dirs), before)
        self.assertEqual(ftp.renames, [])

    def test_broad_root_is_rejected_without_reading_private_files(self):
        ftp = MemoryFTP()
        ftp.files['wp-config.php'] = b'private fixture must not be read'
        with self.assertRaises(ValueError):
            verify_confinement(ftp)
        self.assertEqual(ftp.renames, [])

    def test_parent_escape_is_rejected(self):
        ftp = MemoryFTP()
        position = ['/']
        ftp.pwd = lambda: position[0]
        def cwd(path):
            position[0] = '/outside' if path == '..' else '/'
        ftp.cwd = cwd
        with self.assertRaises(ValueError):
            verify_confinement(ftp)
        self.assertEqual(ftp.renames, [])

    def test_interrupted_swap_restores_previous_theme(self):
        ftp = MemoryFTP()
        ftp.dirs.add('.finelevate-incoming-test')
        ftp.fail_publish = True
        with self.assertRaises(OSError):
            publish(ftp, '.finelevate-incoming-test', 'test')
        self.assertIn(SLUG, ftp.dirs)
        self.assertNotIn('.finelevate-rollback-test', ftp.dirs)

    def test_upload_verifies_bytes_before_swap(self):
        ftp = MemoryFTP()
        verify_target(ftp)
        backup = upload(ftp, {'style.css': b'body{}', 'assets/main.js': b'code'}, 'test')
        self.assertEqual(backup, '.finelevate-rollback-test')
        self.assertEqual(ftp.files['.finelevate-incoming-test/assets/main.js'], b'code')
        self.assertIn(SLUG, ftp.dirs)


if __name__ == '__main__':
    unittest.main()
