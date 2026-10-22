"""Create a public-content-only WordPress import and a stable route manifest.

Input is an explicit read-only snapshot of published REST content and rendered
public card collections. No users, credentials, comments, submissions or private
post meta are read or exported. Run only against the isolated staging instance.
"""
import argparse
import html
from html.parser import HTMLParser
import json
from pathlib import Path
import re
from urllib.parse import urlsplit, unquote
from xml.sax.saxutils import escape

GROUPS = {
    'home': [97, 100, 1031, 1037, 1995, 2080],
    'about': [1763, 1785, 1794, 1797, 1988, 2076],
    'banking': [308, 519, 1040, 1042, 1974, 2074],
    'insurance': [46, 523], 'fintech': [407, 533],
    'partners': [88, 561, 1265, 1268, 1986, 2086],
    'advisory': [90, 330, 1147, 1148, 1989, 2088],
    'news_index': [265, 292, 1158, 1159, 1987, 2082],
    'privacy': [3, 536, 1613, 1616, 2084],
    'cookies': [1621, 1626, 1643, 1644, 2078],
    'summit': [255, 768], 'news': [1558, 1561, 1574, 1577],
    'cooperation': [1396, 1397],
}
LOCALES = {'mk', 'sr', 'hr', 'sl', 'me'}


def public_path(url):
    parsed = urlsplit(url)
    if parsed.hostname not in {'finelevate360.com', 'www.finelevate360.com'}:
        raise ValueError('Only the verified public source host is accepted')
    return '/' + unquote(parsed.path).strip('/') + '/' if parsed.path.strip('/') else '/'


def locale(path):
    first = path.strip('/').split('/')[0]
    return first if first in LOCALES else 'en'


class PublicMarkup(HTMLParser):
    """Keep editable semantic copy, never production scripts or submission paths."""
    DROP = {'script', 'style', 'form', 'iframe', 'canvas', 'svg', 'button', 'nav'}
    ALLOWED = {'a', 'p', 'div', 'span', 'section', 'article', 'h1', 'h2', 'h3', 'h4',
               'h5', 'h6', 'ul', 'ol', 'li', 'strong', 'b', 'em', 'i', 'small',
               'br', 'hr', 'img', 'figure', 'figcaption', 'blockquote', 'table',
               'thead', 'tbody', 'tr', 'th', 'td', 'time', 'address', 'details', 'summary'}
    VOID = {'br', 'hr', 'img'}

    def __init__(self, remove_heading=False):
        super().__init__(convert_charrefs=True)
        self.result, self.drops, self.stack = [], [], []
        self.remove_heading = remove_heading

    def handle_starttag(self, tag, attrs):
        if self.drops:
            if tag in self.DROP or (tag == 'h1' and self.remove_heading):
                self.drops.append(tag)
            return
        values = dict(attrs)
        if tag == 'button' and 'finelevate360-read-more' in values.get('class', ''):
            # The published modal's biography is public data, not executable code.
            # Keep it accessible without importing production event handlers.
            self.result.append('<details class="public-profile-details"><summary>Read more</summary>')
            for key in ['data-job-title', 'data-company', 'data-text']:
                if values.get(key):
                    self.result.append('<p>' + html.escape(values[key]) + '</p>')
            self.result.append('</details>')
        if tag in self.DROP or (tag == 'h1' and self.remove_heading):
            self.drops.append(tag)
            return
        tag = 'div' if tag in {'main', 'header', 'footer'} else tag
        if tag not in self.ALLOWED:
            return
        clean = []
        for key, value in attrs:
            if key not in {'class', 'src', 'alt', 'href', 'title', 'width', 'height',
                           'colspan', 'rowspan', 'datetime', 'open'} or value is None:
                continue
            if key in {'href', 'src'} and not re.match(r'^(https?://|mailto:|tel:|#|/)', value):
                continue
            if key == 'href' and value in {'#', '#more'}:
                continue
            clean.append(' ' + key + '="' + html.escape(value, quote=True) + '"')
        self.result.append('<' + tag + ''.join(clean) + '>')
        if tag not in self.VOID:
            self.stack.append(tag)

    def handle_endtag(self, tag):
        if self.drops:
            if tag == self.drops[-1]:
                self.drops.pop()
            return
        tag = 'div' if tag in {'main', 'header', 'footer'} else tag
        if tag in self.stack:
            while self.stack:
                current = self.stack.pop()
                self.result.append('</' + current + '>')
                if current == tag:
                    break

    def handle_data(self, data):
        if not self.drops:
            self.result.append(html.escape(data))

    def value(self):
        return ''.join(self.result) + ''.join('</' + tag + '>' for tag in reversed(self.stack))


def sanitize(value, remove_heading=False):
    parser = PublicMarkup(remove_heading)
    parser.feed(value)
    return parser.value()


def cdata(value):
    return '<![CDATA[' + value.replace(']]>', ']]]]><![CDATA[>') + ']]>'


def build(snapshot, output, manifest_path):
    pages = json.loads((snapshot / 'published-pages-for-staging.json').read_text())
    posts = json.loads((snapshot / 'published-posts-for-staging.json').read_text())
    rendered = json.loads((snapshot / 'rendered-public-pages-v2.json').read_text())
    sets = [(pages, 'page'), (posts, 'post')]
    for filename, kind in [('public-events.json', 'event'),
                           ('public-finelev360_partner.json', 'finelev360_partner'),
                           ('public-partners-page2.json', 'finelev360_partner'),
                           ('public-finelev360_advisory.json', 'finelev360_advisory')]:
        sets.append((json.loads((snapshot / filename).read_text()), kind))
    manifest = {'schema': 1, 'source': 'Published public content snapshot, 2026-10-22',
                'routes': {}, 'groups': {}, 'incomplete': ['directory integration', 'submission forms']}
    items, seen = [], set()
    for records, kind in sets:
        for record in records:
            if record['id'] in seen:
                raise ValueError('Duplicate source ID')
            seen.add(record['id'])
            path = public_path(record['link'])
            language = locale(path)
            group = next((g for g, ids in GROUPS.items() if record['id'] in ids), '')
            title = html.unescape(record['title']['rendered'])
            manifest['routes'][path] = {'source_id': record['id'], 'type': kind,
                'locale': language, 'group': group, 'title': title}
            if group:
                manifest['groups'].setdefault(group, {})[language] = path
            content = record['content']['rendered']
            if str(record['id']) in rendered:
                content = rendered[str(record['id'])]
            elif path.strip('/') + '/' in rendered and group in {'partners', 'advisory'}:
                content = rendered[path.strip('/') + '/']
            # A dynamic archive is rendered from the imported editable posts.
            if group == 'news_index':
                content = ''
            content = sanitize(content, remove_heading=group in {'partners', 'advisory'})
            # The directory is not represented by fabricated static filters or ratings.
            # Import only its existing published explanatory text until its plugin is available.
            item = '<item><title>' + cdata(title) + '</title>'
            item += '<link>' + escape(record['link']) + '</link>'
            item += '<dc:creator>' + cdata('staging-content') + '</dc:creator>'
            item += '<content:encoded>' + cdata(content) + '</content:encoded><excerpt:encoded></excerpt:encoded>'
            for key, value in {'post_id': record['id'], 'post_date': record['date'].replace('T', ' '),
                               'post_date_gmt': record['date'].replace('T', ' '),
                               'comment_status': 'closed', 'ping_status': 'closed',
                               'post_name': record['slug'], 'status': 'publish', 'post_parent': 0,
                               'menu_order': 0, 'post_type': kind, 'is_sticky': 0}.items():
                item += '<wp:' + key + '>' + cdata(str(value)) + '</wp:' + key + '>'
            for key, value in {'_fe_public_source_id': record['id'], '_fe_public_path': path,
                               '_fe_public_locale': language}.items():
                item += '<wp:postmeta><wp:meta_key>' + cdata(key) + '</wp:meta_key><wp:meta_value>' + cdata(str(value)) + '</wp:meta_value></wp:postmeta>'
            items.append(item + '</item>')
    header = '<?xml version="1.0" encoding="UTF-8"?><rss version="2.0" xmlns:excerpt="http://wordpress.org/export/1.2/excerpt/" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:wp="http://wordpress.org/export/1.2/"><channel><title>Finelevate public content for isolated staging</title><link>https://finelevate360.com</link><description>Published copy only; no users, private meta, submissions or credentials.</description><language>en</language><wp:wxr_version>1.2</wp:wxr_version><wp:base_site_url>https://finelevate360.com</wp:base_site_url><wp:base_blog_url>https://finelevate360.com</wp:base_blog_url>'
    output.parent.mkdir(parents=True, exist_ok=True)
    output.write_text(header + '\n'.join(items) + '</channel></rss>', encoding='utf-8')
    manifest_path.write_text(json.dumps(manifest, ensure_ascii=True, indent=2) + '\n')
    print('Prepared', len(items), 'published records; directory and submission integrations remain explicitly incomplete.')


if __name__ == '__main__':
    parser = argparse.ArgumentParser()
    parser.add_argument('--snapshot', type=Path, required=True)
    parser.add_argument('--output', type=Path, default=Path('wordpress/content/published-site.xml'))
    parser.add_argument('--manifest', type=Path, default=Path('wordpress/themes/finelevate-advisory/inc/routes.json'))
    args = parser.parse_args()
    build(args.snapshot, args.output, args.manifest)
