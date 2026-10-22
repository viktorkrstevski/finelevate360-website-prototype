"""Read the public event's literal translation table without executing JavaScript."""
import base64
import ast
import json
from pathlib import Path
import re
import sys
from html.parser import HTMLParser


class Targets(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.frames, self.targets = [], []

    def handle_starttag(self, tag, attributes):
        void = tag in {'area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'param', 'source', 'track', 'wbr'}
        if not void:
            for frame in self.frames: frame['depth'] += 1
        attributes = dict(attributes)
        key = attributes.get('data-i') or attributes.get('data-ih')
        if key and not void:
            self.frames.append({'tag': tag, 'key': key, 'html': 'data-ih' in attributes, 'depth': 0, 'text': []})

    def handle_data(self, value):
        for frame in self.frames: frame['text'].append(value)

    def handle_endtag(self, tag):
        remaining = []
        for frame in self.frames:
            if frame['depth'] == 0 and frame['tag'] == tag:
                target = {k: frame[k] for k in ['tag', 'key', 'html']}
                target['source_text'] = ' '.join(''.join(frame['text']).split())
                if target not in self.targets: self.targets.append(target)
            else:
                if frame['depth'] > 0: frame['depth'] -= 1
                remaining.append(frame)
        self.frames = remaining


class Literal:
    def __init__(self, source, position):
        self.source, self.position = source, position

    def space(self):
        while self.source[self.position].isspace(): self.position += 1

    def string(self):
        start = self.position
        quote = self.source[start]
        if quote not in ('"', "'"): raise ValueError('Only quoted string literals are accepted')
        self.position += 1
        while True:
            character = self.source[self.position]
            self.position += 1
            if character == '\\': self.position += 1
            elif character == quote: break
        if quote == "'": return ast.literal_eval(self.source[start:self.position].replace('\\/', '/'))
        value = re.sub(r'\\x([a-fA-F0-9]{2})', r'\\u00\1', self.source[start:self.position])
        return json.loads(value)

    def object(self):
        self.space()
        if self.source[self.position] != '{': raise ValueError('Expected object literal')
        self.position += 1
        result = {}
        while True:
            self.space()
            if self.source[self.position] == '}': self.position += 1; return result
            if self.source[self.position] == '"': key = self.string()
            else:
                match = re.match(r'[A-Za-z_$][A-Za-z0-9_$]*', self.source[self.position:])
                if not match: raise ValueError('Unexpected translation key')
                key = match.group(); self.position += len(key)
            self.space()
            if self.source[self.position] != ':': raise ValueError('Expected colon')
            self.position += 1; self.space()
            value = self.object() if self.source[self.position] == '{' else self.string()
            if key in result: raise ValueError('Duplicate translation key')
            result[key] = value
            self.space()
            if self.source[self.position] == ',': self.position += 1
            elif self.source[self.position] != '}': raise ValueError('Unexpected executable expression')


def extract(snapshot, destination):
    events = json.loads(Path(snapshot).read_text())
    html = next(event['content']['rendered'] for event in events if '/mk/' not in event['link'])
    tables = []
    for encoded in re.findall(r'atob\(["\']([A-Za-z0-9+/=]+)["\']\)', html):
        script = base64.b64decode(encoded).decode('utf-8')
        match = re.search(r'\bI18N\s*=\s*\{', script)
        if match: tables.append(Literal(script, script.index('{', match.start())).object())
    if len(tables) != 1: raise ValueError('Expected one public translation table')
    strings = tables[0]
    parser = Targets(); parser.feed(html)
    targets = [target for target in parser.targets if target['key'] in strings['en']]
    output = {'source': 'Published Finelevate Summit frontend translation literals; read-only snapshot 2026-10-22. No script execution or private configuration.', 'strings': strings, 'targets': targets}
    Path(destination).write_text(json.dumps(output, ensure_ascii=True, indent=2) + '\n')
    print('Extracted', len(targets), 'translation targets for', sorted(strings))


if __name__ == '__main__': extract(sys.argv[1], sys.argv[2])
