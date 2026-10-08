#!/usr/bin/env python3
"""Check deployable static paths, including CSS, lazy images, manifests and JSON.

No network requests or third-party packages are required. WordPress is hosted
separately and is not part of the static release.
"""
import argparse
import json
import re
from html.parser import HTMLParser
from pathlib import Path
from urllib.parse import unquote, urljoin, urlsplit

EXCLUDED = {"blog", "build", "livesite", "comingsoon", "views", "_notes",
            ".git", ".github", ".well-known", "node_modules", "originals", "backups"}
OWN_HOSTS = {"vanshea.com", "www.vanshea.com", "build.vanshea.com", "audit.invalid"}
URL_PATTERN = re.compile(r"url\(\s*(?:\"([^\"]*)\"|'([^']*)'|([^)]*))\s*\)", re.I)


def css_refs(css):
    css = re.sub(r"/\*.*?\*/", "", css, flags=re.S)
    return [next(v for v in m.groups() if v is not None).strip()
            for m in URL_PATTERN.finditer(css)]


def json_refs(value):
    refs = []
    if isinstance(value, list):
        for item in value:
            refs.extend(json_refs(item))
    elif isinstance(value, dict):
        for key, item in value.items():
            if key in {"src", "thumb", "large", "fullscreen", "image"} and isinstance(item, str):
                refs.append(item)
            else:
                refs.extend(json_refs(item))
    return refs


class Page(HTMLParser):
    def __init__(self):
        super().__init__()
        self.refs = []
        self.base = None
        self.context = None
        self.buffer = []

    def handle_starttag(self, tag, attrs):
        attrs = dict(attrs)
        if tag == "base":
            self.base = attrs.get("href")
            return
        if tag == "style" or (tag == "script" and attrs.get("type") in
                              {"application/json", "application/ld+json"}):
            self.context = tag
            self.buffer = []
        for key, value in attrs.items():
            if not value:
                continue
            if key in {"href", "src", "poster", "data-src", "data-fullscreen-src", "data-lightbox-src"}:
                self.refs.append((key, value))
            elif key == "srcset" and not value.startswith("data:"):
                self.refs.extend((key, item.strip().split()[0])
                                 for item in value.split(",") if item.strip())
            elif key == "style":
                self.refs.extend(("css", ref) for ref in css_refs(value))
            elif tag == "meta" and key == "content" and attrs.get("property", attrs.get("name")) in {
                    "og:url", "og:image", "twitter:image"}:
                self.refs.append(("metadata", value))

    def handle_data(self, data):
        if self.context:
            self.buffer.append(data)

    def handle_endtag(self, tag):
        if tag != self.context:
            return
        raw = "".join(self.buffer)
        if tag == "style":
            self.refs.extend(("css", ref) for ref in css_refs(raw))
        else:
            self.refs.extend(("json", ref) for ref in json_refs(json.loads(raw)))
        self.context = None


def public_files(root):
    for path in sorted(root.iterdir()):
        if path.name in EXCLUDED or path.name.startswith("._"):
            continue
        if path.is_symlink():
            raise ValueError(f"Symlink is not deployable: {path}")
        if path.is_dir():
            yield from public_files(path)
        else:
            yield path


def exact_file(root, url_path):
    """Check exact spelling even on a case-insensitive development filesystem."""
    current = root
    for part in unquote(url_path).strip("/").split("/"):
        if not part:
            continue
        if part in {".", ".."} or not current.is_dir():
            return False
        children = {p.name: p for p in current.iterdir()}
        if part not in children:
            return False
        current = children[part]
    if current.is_dir():
        current = current / "index.html"
    return current.is_file() and current.name in {p.name for p in current.parent.iterdir()}


def check(root, origin=None):
    errors = []
    count = 0
    pages = 0
    for path in public_files(root):
        suffix = path.suffix.lower()
        if suffix not in {".html", ".css", ".webmanifest", ".json", ".xml", ".txt"}:
            continue
        rel = path.relative_to(root).as_posix()
        raw = path.read_text(encoding="utf-8")
        base = "https://audit.invalid/" + rel
        refs = []
        if suffix == ".html":
            page = Page()
            page.feed(raw)
            refs = page.refs
            pages += 1
            if page.base:
                base = urljoin(base, page.base)
            if origin:
                metadata = re.findall(r'<(?:link\b[^>]*rel=["\']canonical["\'][^>]*|meta\b[^>]*(?:property|name)=["\'](?:og:url|og:image|twitter:image)["\'][^>]*)>', raw, re.I)
                for tag in metadata:
                    value = re.search(r'(?:href|content)=["\']([^"\']+)', tag, re.I)
                    if value and urlsplit(value[1]).netloc != urlsplit(origin).netloc:
                        errors.append(f"{rel}: metadata points to another host: {value[1]}")
        elif suffix == ".css":
            refs = [("css", value) for value in css_refs(raw)]
        elif suffix in {".webmanifest", ".json"}:
            refs = [("json", value) for value in json_refs(json.loads(raw))]
            if rel == "assets/vscimage/config.json":
                base = "https://audit.invalid/"
                config = json.loads(raw)
                refs.extend(("logo", value) for value in config.get("logos", {}).values())
        elif path.name == "sitemap.xml":
            refs = [("sitemap", value) for value in re.findall(r"<loc>(.*?)</loc>", raw)]
        elif path.name == "robots.txt":
            refs = [("sitemap", value) for value in re.findall(r"^Sitemap:\s*(.+)$", raw, re.M)]
        for kind, value in refs:
            value = value.strip()
            if not value or value.startswith(("#", "data:", "mailto:", "tel:", "javascript:", "blob:")):
                continue
            url = urlsplit(urljoin(base, value))
            if url.hostname not in OWN_HOSTS or url.path.startswith("/blog"):
                continue
            count += 1
            if url.path.startswith(("/build/", "/livesite/")):
                errors.append(f"{rel}: deployment-folder prefix: {value}")
            if not exact_file(root, url.path):
                errors.append(f"{rel}: missing {kind}: {value}")
            if kind in {"src", "srcset", "poster", "data-src", "css"} and url.hostname != "audit.invalid":
                errors.append(f"{rel}: public asset must use this host: {value}")
    return pages, count, sorted(set(errors))


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("roots", nargs="+", type=Path)
    parser.add_argument("--origin")
    args = parser.parse_args()
    failed = False
    for root in args.roots:
        origin = args.origin or {"build": "https://build.vanshea.com", "livesite": "https://www.vanshea.com"}.get(root.name)
        pages, count, errors = check(root, origin)
        print(f"{root}: {pages} HTML pages, {count} local references, {len(errors)} errors")
        for error in errors:
            print("  " + error)
        failed = failed or bool(errors)
    raise SystemExit(1 if failed else 0)


if __name__ == "__main__":
    main()
