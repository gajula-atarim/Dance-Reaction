# Dance Reaction — WordPress site

The site is built with Elementor on **Hello Elementor**, using the **Dance Reaction** child theme:
https://dance-reaction.wsdfy.com/

## What's here

| Path | Purpose |
| --- | --- |
| `theme/dance-reaction-child/` | Child theme: brand CSS (`assets/css/dr.css`), effects JS (`assets/js/dr.js`), header/footer loader, booking form shortcode. |
| `elementor/*.json` | Elementor data for the Home, About, Gallery, The Music, Equipment and Contact pages and the UAE Site Header / Site Footer templates (generated). |
| `assets/images/` | Optimised design images, imported into the Media Library. |
| `tools/build_elementor.py` | Generates `elementor/*.json` from the design. |
| `tools/setup.php` | Idempotent installer: copies the theme, imports images, creates the page, templates and menus, and sets Elementor global colours and fonts. |
| `tools/about-setup.php` | Imports the About photos (`DR_STEP=media`), then creates/updates the About Me page and links it in the menus (`DR_STEP=page`). |
| `tools/gallery-setup.php` | Creates/updates the Gallery page (photo wall from the 20 event photos) and links it in the menus. |
| `tools/pages-setup.php` | Imports photos (`DR_STEP=media`), then creates/updates The Music, Equipment and Contact pages, refreshes About/Gallery and the header, and links the pages in the menus (`DR_STEP=pages`). |
| `tools/images-setup.php` | Imports the replacement photos for the repeated images and re-saves The Music, Equipment and About. |
| `tools/perf-setup.php` | Speed settings: hero preload and right-sized backgrounds on every page, page-cache minify + gzip. Reports in `reports/performance/`. |
| `tools/sitemap-setup.php` | Creates/updates the Site Map page and links "Site Map" in the footer menu. |

## How the pages are built

Every section is a native Elementor **container**. Content is native widgets (Heading, Text Editor, Button, Image, Shortcode), so text, images and links are all edited in Elementor. Each block's look comes from a CSS class in *Advanced → CSS Classes*:

- Buttons: `dr-btn` + optional `dr-btn--outline`, `dr-btn--dark`, `dr-btn--light-outline`, `dr-btn--link`, `dr-btn--plain`
- Text: `dr-eyebrow`, `dr-h2`, `dr-h2--md`, `dr-h2--xl`, `dr-body-lg`, `dr-lead`
- Images: `dr-fill` (cover its box), `dr-tint` (brand colour wash)
- Blocks: `dr-tile`, `dr-card`, `dr-event`, `dr-quote`, `dr-info`, `dr-cta`, `dr-gallery`, `dr-stack`
- Inner pages: `dr-panel` (`--grad`), `dr-tip`, `dr-checklist` (`--gear`), `dr-bignum`, `dr-ticker`, `dr-bigquote`, `dr-statement`, `dr-collage--flip`, `dr-neon__title--md/--sm`
- Gallery page: `dr-masonry` (photo wall) with `dr-mtile` tiles (`dr-mtile--11`, `--32`, `--34` ratios, `--alt` corners, `--glow`)
- About page: `dr-collage`, `dr-toys` / `dr-toy`, `dr-vinyl` / `dr-sleeve`, `dr-neon`, `dr-timeline` / `dr-step`, `dr-h2--lg`

Floating notes, flares, dot circles and smoke are added by `dr.js`, keyed to the section CSS IDs (`top`, `welcome`, `why`, `price`, `how`, `events`, `testimonials`, `gallery`, `enquiry`; About: `about-hero`, `family`, `aim`, `style`; Gallery: `gallery-hero`, `photos`). Add `dr-smoke` to any section to give it the smoke layer.

**Header / footer.** Built with Ultimate Addons for Elementor: *Appearance → UAE → Header/Footer Builder → Site Header (UAE) / Site Footer (UAE)*, shown on the entire site. Menu links are managed in *Appearance → Menus* (Header Menu, Footer Menu, Footer Legal). `tools/hfe-setup.php` recreates them.

**Booking form.** The form is the `[dr_booking_form]` shortcode (`layout="contact"` gives the Contact page's Full name / Email / Phone / Comments form), because the free version of Elementor has no Form widget. Enquiries are emailed to the site admin email. Optional attributes are `to="name@example.com"`, `button="Send"` and `types="Wedding, Birthday"`.

## Rebuild / redeploy

```bash
python3 tools/build_elementor.py          # regenerate elementor/*.json
git push                                  # setup.php pulls files from GitHub
# first install: run tools/setup.php on the site (DR_REF = pushed commit)
# later updates: run tools/redeploy.php (theme files + Elementor data only)
```
