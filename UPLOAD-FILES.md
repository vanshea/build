# Build upload files

Upload these paths **inside the Build document root**, preserving folders. No files have been uploaded. This manifest does not authorize or perform a Livesite deployment.

Upload assets first, then HTML and `.htaccess`. Verify Build before removing obsolete files or promoting it. Metadata currently targets the Build host; use the established promotion process for production.

## Public files (94)

- `.htaccess`
- `400.html`
- `401.html`
- `403.html`
- `404.html`
- `500.html`
- `502.html`
- `503.html`
- `aidesign/Contact Prototype (standalone).html`
- `aidesign/experiments/index.html`
- `aidesign/experiments/stock-performance-test/index.html`
- `aidesign/index.html`
- `aidesign/meeting_coach.html`
- `aidesign/meeting_coach_demo.html`
- `aidesign/partner.html`
- `aidesign/partner_fullscreen.html`
- `aidesign/self_care.html`
- `aidesign/share/contact.html`
- `aidesign/share/meeting-coach.html`
- `aidesign/share/partner.html`
- `aidesign/share/self-care.html`
- `assets/aidesign.js`
- `assets/aidesign/prototypes/contact/index.html`
- `assets/aidesign/prototypes/meeting-coach/index.html`
- `assets/aidesign/prototypes/partner/index.html`
- `assets/aidesign/prototypes/self-care/index.html`
- `assets/design-system/components.css`
- `assets/design-system/tokens.css`
- `assets/embeds/us20220261083a1.html`
- `assets/experience.html`
- `assets/home/agency-home.css`
- `assets/home/agency-home.js`
- `assets/home/agency-site.css`
- `assets/home/demos/aidesign--Contact Prototype (standalone).html`
- `assets/home/demos/aidesign--meeting_coach.html`
- `assets/home/demos/aidesign--meeting_coach_demo.html`
- `assets/home/demos/aidesign--partner.html`
- `assets/home/demos/aidesign--partner_fullscreen.html`
- `assets/home/demos/aidesign--self_care.html`
- `assets/home/demos/aidesign--share--contact.html`
- `assets/home/demos/aidesign--share--meeting-coach.html`
- `assets/home/demos/aidesign--share--partner.html`
- `assets/home/demos/aidesign--share--self-care.html`
- `assets/home/journey-map-animation.css`
- `assets/home/journey-map-animation.js`
- `assets/home/large-logo-horizontal.svg`
- `assets/home/logomark-lite.svg`
- `assets/home/logomark.svg`
- `assets/home/van-shea-headshot-2027-small.jpg`
- `assets/home/van-shea-headshot-2027.png`
- `assets/home/van-shea-portrait.webp`
- `assets/home/wave-05-morph.svg`
- `assets/home/wave-05-static.svg`
- `assets/index.html`
- `assets/responsive/capital-one-gesture-patent-detail.webp`
- `assets/responsive/capital-one-patent-figure-pdf-fullscreen-3200x1800-1600.webp`
- `assets/responsive/capital-one-patent-figure-pdf-fullscreen-3200x1800-480.webp`
- `assets/responsive/capital-one-patent-figure-pdf-fullscreen-3200x1800-960.webp`
- `assets/responsive/exchange-1440-v01-default-thumb-760x570-480.webp`
- `assets/responsive/large-web-portfolio-airdolly23200x1800-thumb-760x570-480.webp`
- `assets/responsive/large-web-portfolio-fluidx3200x1800-thumb-760x570-480.webp`
- `assets/responsive/large-web-portfolio-gore3200x1800-thumb-760x570-480.webp`
- `assets/responsive/large-web-portfolio-greif-thumb-760x570-480.webp`
- `assets/responsive/large-web-portfolio-greif3200x1800-thumb-760x570-480.webp`
- `assets/responsive/mt-bank-commercial-banking-transformation-detail.webp`
- `assets/responsive/mt-bank-future-b2b-lending-board-fullscreen-3200x1800-1600.webp`
- `assets/responsive/mt-bank-future-b2b-lending-board-fullscreen-3200x1800-480.webp`
- `assets/responsive/mt-bank-future-b2b-lending-board-fullscreen-3200x1800-960.webp`
- `assets/responsive/nami-988-queen-bus-fullscreen-1693x929-1600.webp`
- `assets/responsive/nami-988-queen-bus-fullscreen-1693x929-480.webp`
- `assets/responsive/nami-988-queen-bus-fullscreen-1693x929-960.webp`
- `assets/responsive/nyu-2000-1-2000x1000-thumb-760x570-480.webp`
- `assets/responsive/politico-thumb-760x570-480.webp`
- `assets/responsive/sketches-and-nfts-thumb-760x570-480.webp`
- `assets/responsive/vanguard3200x1800-fullscreen-3200x1800-1600.webp`
- `assets/responsive/vanguard3200x1800-fullscreen-3200x1800-480.webp`
- `assets/responsive/vanguard3200x1800-fullscreen-3200x1800-960.webp`
- `assets/script.js`
- `case-studies/capital-one-gesture-patent/index.html`
- `case-studies/capital-one-gesture-patent/social-preview.jpg`
- `case-studies/index.html`
- `case-studies/mt-bank-commercial-banking-transformation/index.html`
- `case-studies/mt-bank-commercial-banking-transformation/social-preview.jpg`
- `case-studies/nami-delaware-988-campaign/index.html`
- `case-studies/nami-delaware-988-campaign/social-preview.jpg`
- `case-studies/nyu-curriculum-alignment/index.html`
- `case-studies/nyu-curriculum-alignment/social-preview.jpg`
- `case-studies/vanguard-innovation-lab-integration/index.html`
- `case-studies/vanguard-innovation-lab-integration/social-preview.jpg`
- `experience.html`
- `index.html`
- `script.js`
- `studio.html`
- `work.html`

## Obsolete asset paths

After the new paths work on Build, the old `assets/home-2027/` directory can be removed. Do not remove it before uploading `assets/home/` and the updated HTML. No other hosted deletion is needed for this pass.

## Keep with the source, not the static upload

- `data/` — shared testimonials, case-study cards and role/scope data.
- `scripts/render-testimonials.py`, `scripts/render-case-studies.py`, `scripts/render-role-scope.py` — static renderers.
- `views/` — matching server templates.
- `qa/`, `FIX-REPORT.md`, `UPLOAD-FILES.md` — review evidence and manifests.

The separate WordPress blog package is not included in this upload list. Its shared theme-cookie work is preserved in the listed Build JavaScript.
