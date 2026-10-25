"""Exercise the real theme cascade in isolated headless Chrome fixtures."""
import html
import json
import os
from pathlib import Path
import re
import shutil
import subprocess
import tempfile
import unittest

ROOT = Path(__file__).resolve().parents[1]
CSS = (ROOT / "wordpress/themes/finelevate-advisory/style.css").read_text()
CONTAINER_RULE = "@container(max-width:1059px){.header-inner{grid-template-columns:minmax(0,1fr) auto;gap:20px}.desktop-nav,.header-contact{display:none}.menu-toggle{display:block}}"


class HeaderBreakpointTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.chrome = os.environ.get("CHROME_BIN") or shutil.which("google-chrome") or shutil.which("chromium")
        if not cls.chrome:
            mac = Path("/Applications/Google Chrome.app/Contents/MacOS/Google Chrome")
            cls.chrome = str(mac) if mac.exists() else None
        if not cls.chrome:
            raise RuntimeError("Chrome is required for the header breakpoint regression test")

    def render(self, width, scrollbar=True, css=CSS):
        scrollbar_css = "html{overflow-y:scroll;scrollbar-gutter:stable}html::-webkit-scrollbar{width:15px}" if scrollbar else "html{overflow-y:hidden}"
        fixture = """<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><style>""" + css + scrollbar_css + """</style></head><body>
<header class="site-header"><div class="header-inner">
<a class="brand">Finelevate360</a>
<nav class="desktop-nav"><ul class="nav-links"><li><a>Digital Summit 2026</a></li><li><a>Digital Map</a></li><li><a>Partners</a></li><li><a>News</a></li><li><a>About</a></li><li><a>EN</a></li></ul></nav>
<button class="button contact header-contact">Contact us</button><button class="menu-toggle">Menu</button>
</div></header><main style="min-height:1500px"></main><pre id="metrics"></pre>
<script>
const header=document.querySelector('.site-header'),grid=document.querySelector('.header-inner'),menu=document.querySelector('.menu-toggle'),nav=document.querySelector('.desktop-nav'),contact=document.querySelector('.header-contact');
const box=e=>({left:e.getBoundingClientRect().left,right:e.getBoundingClientRect().right,width:e.getBoundingClientRect().width});
parent.postMessage({viewport:innerWidth,client:document.documentElement.clientWidth,scroll:document.documentElement.scrollWidth,header:box(header),grid:box(grid),menu:box(menu),columns:getComputedStyle(grid).gridTemplateColumns.split(' ').length,menuDisplay:getComputedStyle(menu).display,navDisplay:getComputedStyle(nav).display,contactDisplay:getComputedStyle(contact).display}, '*');
</script></body></html>"""
        # A frame avoids Chrome's 500px minimum top-level window width while
        # retaining real viewport media queries and classic scrollbar geometry.
        fixture = ('<!doctype html><html><body style="margin:0;overflow:hidden">'
                   '<pre id="metrics"></pre><script>addEventListener("message",e=>{'
                   'document.querySelector("#metrics").textContent=JSON.stringify(e.data)'
                   '})</script><iframe style="display:block;width:' + str(width) +
                   'px;height:900px;border:0" srcdoc="' + html.escape(fixture, quote=True) +
                   '"></iframe></body></html>')
        with tempfile.TemporaryDirectory(prefix="finelevate-header-") as directory:
            page = Path(directory) / "header.html"
            page.write_text(fixture)
            result = subprocess.run([self.chrome, "--headless=new", "--no-sandbox", "--disable-gpu", "--no-first-run", "--disable-background-networking", "--user-data-dir=" + directory + "/profile", "--window-size=" + str(max(width, 500)) + ",900", "--virtual-time-budget=1000", "--dump-dom", page.as_uri()], capture_output=True, text=True, timeout=30)
            self.assertEqual(result.returncode, 0, result.stderr[-1500:])
            match = re.search(r'<pre id="metrics">(.*?)</pre>', result.stdout, re.S)
            self.assertIsNotNone(match, result.stdout[-1500:])
            metrics = json.loads(html.unescape(match.group(1)))
            print("HEADER_BREAKPOINT " + json.dumps(metrics, sort_keys=True))
            return metrics

    def assert_header(self, metrics, width, scrollbar):
        self.assertEqual(metrics["viewport"], width)
        self.assertEqual(metrics["client"], width - (15 if scrollbar else 0))
        self.assertEqual(metrics["scroll"], metrics["client"])
        narrow = metrics["header"]["width"] <= 1059
        self.assertEqual(metrics["columns"], 2 if narrow else 3)
        if narrow:
            self.assertEqual(metrics["navDisplay"], "none")
            self.assertEqual(metrics["contactDisplay"], "none")
            self.assertNotEqual(metrics["menuDisplay"], "none")
            self.assertLessEqual(metrics["menu"]["width"], 100)
            self.assertAlmostEqual(metrics["menu"]["right"], metrics["grid"]["right"], delta=1)
        else:
            self.assertEqual(metrics["menuDisplay"], "none")
            self.assertNotEqual(metrics["navDisplay"], "none")
            self.assertNotEqual(metrics["contactDisplay"], "none")

    def test_classic_scrollbar_at_each_width_1060_through_1075(self):
        for width in range(1060, 1076):
            with self.subTest(width=width):
                self.assert_header(self.render(width), width, True)

    def test_prior_mobile_and_desktop_widths(self):
        for width in (320, 390, 1280):
            for scrollbar in (False, True):
                with self.subTest(width=width, scrollbar=scrollbar):
                    self.assert_header(self.render(width, scrollbar), width, scrollbar)

    def test_fixture_detects_original_three_column_override(self):
        original = CSS.replace(CONTAINER_RULE + "\n", "")
        original = original.replace("/* Modest shared brand scale", CONTAINER_RULE + "\n/* Modest shared brand scale")
        metrics = self.render(1060, css=original)
        self.assertEqual(metrics["client"], 1045)
        self.assertEqual(metrics["navDisplay"], "none")
        self.assertNotEqual(metrics["menuDisplay"], "none")
        self.assertEqual(metrics["columns"], 3)
        self.assertGreater(metrics["menu"]["width"], 100)


if __name__ == "__main__":
    unittest.main()
