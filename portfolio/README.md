# Portfolio previews

On the project Mac, open [the Build portfolio](http://localhost/build/portfolio/) in your browser. The same chooser opens from **Preview the new portfolio** below the case studies on the [Build homepage](http://localhost/build/).

To preview on a machine without the local web server, serve the Build directory in a Terminal window and keep that window open while browsing.

```sh
python3 -m http.server 8847 --bind 127.0.0.1 --directory /Library/WebServer/Documents/build
```

For that Terminal preview, open [the chooser on port 8847](http://127.0.0.1:8847/portfolio/). Use its three links to compare the gallery, narrative, and process presentations of the same six illustrative studies. The footer theme selection is shared with the rest of Build. Each view ends with the site's existing scheduling link.

## Files to upload

Copy the entire `portfolio/` directory to `/portfolio/` at the document root for **build.vanshea.com**. Keep the directory structure below. The existing Build site already provides `assets/home/`, `assets/design-system/`, `assets/themes/`, and `fav-icon/`.

```text
portfolio/
├── index.html
├── README.md
├── data/
│   └── projects.json
├── shared/
│   ├── data.js
│   ├── portfolio.css
│   ├── portfolio.js
│   └── images/
│       └── {common-ground,relay,daybreak,field-notes,signal-room,waypoint}/
│           └── {hero,detail-1,detail-2,detail-3}-{480,800,1280}.{webp,jpg}
├── v1-gallery/
│   └── index.html
├── v2-narrative/
│   └── index.html
└── v3-process/
    ├── index.html
    └── media/
        ├── README.md
        ├── {project}-{720,1280}.{mp4,webm}
        └── {project}-poster.jpg
```

Braces denote all combinations. Each of the six project IDs has 24 still images, four video encodes, and one poster. The two README files are handoff documentation.

## Replace a project

Edit `data/projects.json` to change its title, sector, client, role, summary, chapters, metric, and media descriptions. Keep illustrative metrics marked with `"placeholder": true` until you supply a real outcome. Each image record specifies its original dimensions, alt text, and the paths for each responsive width. All media paths in the JSON are relative to `portfolio/`. Replacing files at the same paths preserves the links.

The gallery opens the cover and supporting images in a fullscreen dialog. On touchscreens, swipe across the image. On a keyboard, use the arrow keys to move and Escape to return to the project. The narrative uses a sticky lead image at wider widths and inline chapters on small screens. The process view offers a manual play/pause button, pauses videos that leave the viewport, and shows each poster until Play is selected when reduced motion is on. Expand the project notes to see the written study and supporting images.

See the [media guide](v3-process/media/README.md) for FFmpeg commands, recording dimensions, and per-file sizes.

## Local verification

Lighthouse 13.5.0 mobile emulation in Chrome, served from localhost with the standard simulated network and CPU profile.

| Page | Performance | Accessibility | Largest Contentful Paint | Cumulative Layout Shift |
| --- | ---: | ---: | ---: | ---: |
| Chooser | 99 | 100 | 2.03 s | 0 |
| Gallery | 98 | 100 | 2.10 s | 0 |
| Narrative | 98 | 100 | 2.18 s | 0 |
| Process | 99 | 100 | 1.88 s | 0 |

The Process figures are the median of three cold runs. Chrome layout checks covered 320, 375, 768, and 1440 CSS pixels with Clear, Contrast, OS7, and Wild. All checked interactive targets measured at least 44 × 44 CSS pixels, and each layout fit the viewport. Automated axe checks at 375 and 1440 px reported zero WCAG A/AA violations in the tested rules. Manual-interaction simulations covered the gallery keyboard controls, touch swipe, focus return, the scroll-animation fallback, theme persistence, and reduced-motion video behavior. These measurements cover local Chrome emulation. Physical mobile devices, assistive-technology sessions, Safari, and production hosting were outside the test coverage.
