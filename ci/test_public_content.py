import json
import gzip
from pathlib import Path
import unittest
import xml.etree.ElementTree as ET

from public_content_import import public_path, sanitize

ROOT = Path(__file__).resolve().parents[1]


class PublicContentTests(unittest.TestCase):
    def test_production_submission_paths_and_scripts_are_not_imported(self):
        value = sanitize('<p>Public copy</p><form action="https://finelevate360.com/submit"><input name="email"></form><script>send()</script><p>More copy</p>')
        self.assertEqual(value, '<p>Public copy</p><p>More copy</p>')

    def test_official_external_links_and_copy_are_preserved(self):
        self.assertIn('https://bank.example/app', sanitize('<a href="https://bank.example/app">Official app</a>'))
        self.assertNotIn('javascript:', sanitize('<a href="javascript:alert(1)">Text</a>'))

    def test_full_public_modal_biography_survives_without_its_script(self):
        result = sanitize('<button class="finelevate360-read-more" onclick="open()" data-text="Complete public biography">Read more</button>')
        self.assertIn('Complete public biography', result)
        self.assertIn('<details', result)
        self.assertNotIn('onclick', result)

    def test_source_host_is_strict_and_unicode_paths_survive(self):
        self.assertEqual(public_path('https://finelevate360.com/mk/%d0%b7%d0%b0/'), '/mk/\u0437\u0430/')
        with self.assertRaises(ValueError):
            public_path('https://finelevate360.com.evil.example/about-us/')

    def test_import_is_published_content_only_and_manifest_covers_each_record(self):
        document = ET.ElementTree(ET.fromstring(gzip.decompress((ROOT / 'wordpress/content/published-site.xml.gz').read_bytes())))
        items = document.findall('./channel/item')
        manifest = json.loads((ROOT / 'wordpress/themes/finelevate-advisory/inc/routes.json').read_text())
        self.assertEqual(len(items), len(manifest['routes']))
        self.assertGreater(len(items), 250)
        wp = '{http://wordpress.org/export/1.2/}'
        self.assertEqual({x.find(wp + 'status').text for x in items}, {'publish'})
        allowed_meta = {'_fe_public_source_id', '_fe_public_path', '_fe_public_locale'}
        for item in items:
            self.assertEqual({m.find(wp + 'meta_key').text for m in item.findall(wp + 'postmeta')}, allowed_meta)
            self.assertFalse(item.findall(wp + 'comment'))


if __name__ == '__main__':
    unittest.main()
