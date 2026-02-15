#!/usr/bin/env python3
import re
from pathlib import Path

ROOT = Path('/workspaces/ahmed007')
PAGES = [
    'index.html', 'about.html', 'articles.html', 'courses.html', 'timeline.html',
    'dictionary.html', 'influencers.html', 'influencers.html'
]

css_file = ROOT / 'static' / 'css' / 'site-layout.css'

def analyze_html(path: Path):
    txt = path.read_text(encoding='utf-8')
    viewport = bool(re.search(r'<meta\s+name=["\']viewport', txt, re.I))
    media_q = len(re.findall(r'@media\s*\(', txt))
    links_css = re.findall(r'<link[^>]+href=["\']([^"\']+)["\']', txt)
    uses_site_css = any('site-layout.css' in href for href in links_css)
    inline_max_width = bool(re.search(r'max-width\s*:\s*\d', txt))
    has_container = 'class="container"' in txt or "class='container'" in txt
    return {
        'file': str(path.relative_to(ROOT)),
        'viewport_meta': viewport,
        'media_queries': media_q,
        'uses_site_layout_css': uses_site_css,
        'has_inline_max_width': inline_max_width,
        'has_container_class': has_container,
    }


def main():
    pages = []
    for p in PAGES:
        f = ROOT / p
        if not f.exists():
            continue
        pages.append(analyze_html(f))

    css_info = None
    if css_file.exists():
        css_txt = css_file.read_text(encoding='utf-8')
        css_media = len(re.findall(r'@media\s*\(', css_txt))
        has_helpers = 'container { max-width' in css_txt or '.row' in css_txt
        css_info = {'path': str(css_file.relative_to(ROOT)), 'media_queries': css_media, 'has_helpers': has_helpers}

    # Print report
    print('Responsive Audit Report')
    print('=======================')
    for p in pages:
        print(f"\n- {p['file']}")
        print(f"  - viewport_meta: {p['viewport_meta']}")
        print(f"  - media_queries: {p['media_queries']}")
        print(f"  - uses_site_layout_css: {p['uses_site_layout_css']}")
        print(f"  - has_container_class: {p['has_container_class']}")
        print(f"  - has_inline_max_width: {p['has_inline_max_width']}")

    if css_info:
        print('\nShared CSS')
        print(f" - {css_info['path']}")
        print(f"   - media_queries: {css_info['media_queries']}")
        print(f"   - responsive_helpers_present: {css_info['has_helpers']}")

    # Quick summary
    missing_viewport = [p['file'] for p in pages if not p['viewport_meta']]
    few_media = [p['file'] for p in pages if p['media_queries'] == 0]
    print('\nSummary')
    print('-------')
    print(f"Pages scanned: {len(pages)}")
    print(f"Missing viewport meta: {len(missing_viewport)} -> {missing_viewport}")
    print(f"Pages with no media queries found: {len(few_media)} -> {few_media}")

if __name__ == '__main__':
    main()
