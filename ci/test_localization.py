import json
from pathlib import Path
import re
import unittest

from extract_event_locales import Literal

THEME = Path(__file__).resolve().parents[1] / 'wordpress/themes/finelevate-advisory'


class LocalizationTests(unittest.TestCase):
    def test_all_theme_copy_and_interface_labels_have_localized_values(self):
        defaults = json.loads((THEME / 'inc/content.json').read_text())
        fields = {key for key, value in defaults.items() if value['type'] == 'text'}
        content = json.loads((THEME / 'inc/translations.json').read_text())
        interface = json.loads((THEME / 'inc/ui-translations.json').read_text())
        labels = set()
        for source in THEME.rglob('*.php'):
            labels.update(re.findall(r"(?:esc_html_e|esc_html__|esc_attr_e|esc_attr__|__)\('([^']+)', 'finelevate-advisory'\)", source.read_text()))
        labels.update({'Privacy Policy', 'Cookie Policy', 'Banking Digital Map', 'Insurance Digital Map', 'Fintech Digital Map'})
        for language in ['mk', 'sr', 'hr', 'sl', 'me']:
            self.assertFalse(fields - set(content[language]), language)
            self.assertFalse(labels - set(interface[language]), language)
            self.assertTrue(all(content[language][key].strip() for key in fields))
            self.assertTrue(all(interface[language][key].strip() for key in labels))
            for key in fields:
                self.assertEqual(re.findall(r'\d+', content[language][key]), re.findall(r'\d+', defaults[key]['value']), (language, key))

    def test_public_translation_literal_is_data_not_executable_javascript(self):
        self.assertEqual(Literal('{en:{title:"Public\\x20copy",body:\'<strong>Copy</strong>\'}}', 0).object()['en']['title'], 'Public copy')
        with self.assertRaises(ValueError):
            Literal('{en:{title:runCode()}}', 0).object()
        with self.assertRaises(ValueError):
            Literal('{en:{title:"copy"+runCode()}}', 0).object()


if __name__ == '__main__': unittest.main()
