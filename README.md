# Dance Reaction — WordPress site

The site is built with Elementor on **Hello Elementor**, using the **Dance Reaction** child theme:
https://dance-reaction.wsdfy.com/

## What's here

| Path | Purpose |
| --- | --- |
| `theme/dance-reaction-child/` | Child theme: brand CSS (`assets/css/dr.css`), effects JS (`assets/js/dr.js`), header/footer loader, booking form shortcode. |
| `elementor/*.json` | Elementor data for the Home page and the UAE Site Header / Site Footer templates (generated). |
| `assets/images/` | Optimised design images, imported into the Media Library. |
| `tools/build_elementor.py` | Generates `elementor/*.json` from the design. |
| `tools/setup.php` | Idempotent installer: copies the theme, imports images, creates the page, templates and menus, and sets Elementor global colours and fonts. |

## How the pages are built

Every section is a native Elementor **container**. Content is native widgets (Heading, Text Editor, Button, Image, Shortcode), so text, images and links are all edited in Elementor. Each block's look comes from a CSS class in *Advanced → CSS Classes*:

- Buttons: `dr-btn` + optional `dr-btn--outline`, `dr-btn--dark`, `dr-btn--light-outline`, `dr-btn--link`, `dr-btn--plain`
- Text: `dr-eyebrow`, `dr-h2`, `dr-h2--md`, `dr-h2--xl`, `dr-body-lg`, `dr-lead`
- Images: `dr-fill` (cover its box), `dr-tint` (brand colour wash)
- Blocks: `dr-tile`, `dr-card`, `dr-event`, `dr-quote`, `dr-info`, `dr-cta`, `dr-gallery`, `dr-stack`

Floating notes, flares, dot circles and smoke are added by `dr.js`, keyed to the section CSS IDs (`top`, `welcome`, `why`, `price`, `how`, `events`, `testimonials`, `gallery`, `enquiry`). Add `dr-smoke` to any section to give it the smoke layer.

**Header / footer.** Built with Ultimate Addons for Elementor: *Appearance → UAE → Header/Footer Builder → Site Header (UAE) / Site Footer (UAE)*, shown on the entire site. Menu links are managed in *Appearance → Menus* (Header Menu, Footer Menu, Footer Legal). `tools/hfe-setup.php` recreates them.

**Booking form.** The form is the `[dr_booking_form]` shortcode, because the free version of Elementor has no Form widget. Enquiries are emailed to the site admin email. Optional attributes are `to="name@example.com"`, `button="Send"` and `types="Wedding, Birthday"`.

## Rebuild / redeploy

```bash
python3 tools/build_elementor.py          # regenerate elementor/*.json
git push                                  # setup.php pulls files from GitHub
# first install: run tools/setup.php on the site (DR_REF = pushed commit)
# later updates: run tools/redeploy.php (theme files + Elementor data only)
```
