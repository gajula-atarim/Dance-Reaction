#!/usr/bin/env python3
"""Build the Elementor JSON for the Dance Reaction home page, header and footer.

Output goes to elementor/*.json. Images are referenced with placeholders
({"id": "__IMG__<file>", "url": "__IMG__<file>"}) that tools/setup.php swaps for
real media-library attachments when importing.

Every block is a native Elementor container or widget (Heading, Text Editor,
Button, Image, Shortcode, HTML) so it can be edited in the Elementor panel.
Look-and-feel comes from the CSS classes defined in the child theme (dr.css).

Usage: python3 tools/build_elementor.py
"""
import hashlib
import json
import os

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
OUT = os.path.join(ROOT, "elementor")

_seq = [0]


def eid(seed=""):
    _seq[0] += 1
    return hashlib.md5(f"dr-{_seq[0]}-{seed}".encode()).hexdigest()[:7]


def img(name):
    return {"id": f"__IMG__{name}", "url": f"__IMG__{name}", "size": "", "alt": "", "source": "library"}


def px(v):
    return {"unit": "px", "size": v, "sizes": []}


def box(t, r=None, b=None, l=None, unit="px"):
    r = t if r is None else r
    b = t if b is None else b
    l = r if l is None else l
    return {"unit": unit, "top": str(t), "right": str(r), "bottom": str(b), "left": str(l),
            "isLinked": t == r == b == l}


def gap(v, row=None):
    row = v if row is None else row
    return {"column": str(v), "row": str(row), "isLinked": v == row, "unit": "px", "size": v}


# ---------------------------------------------------------------- elements

def con(children, cls="", eid_=None, *, direction="column", boxed=False, width=None, gap_=None,
        justify=None, align=None, wrap=None, pad=None, pad_t=None, pad_m=None, dir_t=None, dir_m=None,
        tag=None, link=None, bg=None, radius=None, minh=None, anim=None, width_t=None, width_m=None,
        extra=None, inner=True, wrap_t=None):
    s = {"content_width": "boxed" if boxed else "full", "flex_direction": direction}
    if boxed:
        s["boxed_width"] = px(1280)
    if width is not None:
        s["width"] = {"unit": "%", "size": width, "sizes": []}
    if width_t is not None:
        s["width_tablet"] = {"unit": "%", "size": width_t, "sizes": []}
    if width_m is not None:
        s["width_mobile"] = {"unit": "%", "size": width_m, "sizes": []}
    if gap_ is not None:
        s["flex_gap"] = gap_ if isinstance(gap_, dict) else gap(gap_)
    if justify:
        s["flex_justify_content"] = justify
    if align:
        s["flex_align_items"] = align
    if wrap:
        s["flex_wrap"] = wrap
    if wrap_t:
        s["flex_wrap_tablet"] = wrap_t
    s["padding"] = pad or box(0)
    if pad_t:
        s["padding_tablet"] = pad_t
    if pad_m:
        s["padding_mobile"] = pad_m
    if dir_t:
        s["flex_direction_tablet"] = dir_t
    if dir_m:
        s["flex_direction_mobile"] = dir_m
    if tag:
        s["html_tag"] = tag
    if link:
        s["link"] = {"url": link, "is_external": "", "nofollow": "", "custom_attributes": ""}
    if bg:
        s.update(bg)
    if radius is not None:
        s["border_radius"] = box(radius)
    if minh:
        s["min_height"] = minh
    if anim:
        s["_animation"] = anim
        s["animation_duration"] = "slow"
    if cls:
        s["css_classes"] = cls
    if eid_:
        s["_element_id"] = eid_
    if extra:
        s.update(extra)
    return {"id": eid(cls + str(eid_)), "elType": "container", "isInner": inner, "settings": s, "elements": children}


def section(children, cls, sid, **kw):
    kw.setdefault("boxed", True)
    kw.setdefault("pad", box(120, 48, 120, 48))
    kw.setdefault("pad_t", box(96, 32, 96, 32))
    kw.setdefault("pad_m", box(72, 16, 72, 16))
    el = con(children, "dr-sec " + cls, sid, inner=False, **kw)
    return el


def widget(wtype, settings, cls=""):
    if cls:
        settings["_css_classes"] = cls
    return {"id": eid(wtype + cls), "elType": "widget", "widgetType": wtype, "settings": settings, "elements": []}


def heading(text, cls="", tag="h2", align=None, link=None, align_m=None):
    s = {"title": text, "header_size": tag}
    if align:
        s["align"] = align
    if align_m:
        s["align_mobile"] = align_m
    if link:
        s["link"] = {"url": link, "is_external": "", "nofollow": ""}
    return widget("heading", s, cls)


def text(html, cls="", align=None):
    s = {"editor": html}
    if align:
        s["align"] = align
    return widget("text-editor", s, cls)


def button(label, url, cls="dr-btn", align=None, align_m=None):
    s = {"text": label, "link": {"url": url, "is_external": "", "nofollow": ""}, "size": "sm"}
    if align:
        s["align"] = align
    if align_m:
        s["align_mobile"] = align_m
    return widget("button", s, cls)


def image(name, alt, cls=""):
    i = img(name)
    i["alt"] = alt
    return widget("image", {"image": i, "image_size": "full", "caption_source": "none", "link_to": "none"}, cls)


def html(code, cls=""):
    return widget("html", {"html": code}, cls)


def shortcode(code, cls=""):
    return widget("shortcode", {"shortcode": code}, cls)


def bg_image(name, pos="center center"):
    return {"background_background": "classic", "background_image": img(name),
            "background_position": pos, "background_size": "cover", "background_repeat": "no-repeat"}


def bg_grad():
    return {"background_background": "gradient", "background_color": "#E052D8", "background_color_b": "#3D4FE8",
            "background_gradient_type": "linear", "background_gradient_angle": {"unit": "deg", "size": 100, "sizes": []}}


def bg_color(c):
    return {"background_background": "classic", "background_color": c}


def btn_row(buttons, justify=None, mt=0):
    return con(buttons, "dr-btn-row", direction="row", wrap="wrap", gap_=12, justify=justify,
               align="center", pad=box(mt, 0, 0, 0))


PHONE = "0419 399 374"
TEL = "tel:0419399374"

# ---------------------------------------------------------------- sections


def hero():
    content = con([
        heading("Mobile DJ · Melbourne", "dr-badge", "div"),
        heading("Dance Reaction", "dr-hero-title", "h1"),
        heading("Mobile Disco's", "dr-hero-sub", "p"),
        heading("Feel the rhythm of the night", "dr-hero-tag", "p"),
        text("<p>Weddings, corporate dinners, birthdays and any other type of event.</p>", "dr-lead"),
        btn_row([button("Booking enquiry", "#enquiry"),
                 button(f"Call {PHONE}", TEL, "dr-btn dr-btn--outline")], mt=8),
    ], "dr-hero-content", boxed=True, align="flex-start", justify="center", gap_=24,
        pad=box(120, 48, 210, 48), pad_t=box(96, 32, 190, 32), pad_m=box(64, 16, 72, 16))

    scroll = html(
        '<a href="#welcome" aria-label="Scroll down">'
        '<span class="dr-scroll__line"></span>'
        '<span class="dr-scroll__dot"><svg width="14" height="12" viewBox="0 0 14 12" aria-hidden="true"><path d="M0 0 H14 L7 12 Z" fill="#fff"/></svg></span>'
        '<span class="dr-scroll__chev"><svg width="52" height="46" viewBox="0 0 52 46" fill="none" stroke="#fff" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 3 L26 22 L49 3"/><path d="M3 22 L26 41 L49 22"/></svg></span>'
        '</a>', "dr-scroll")

    return con([content, scroll], "dr-sec dr-hero dr-smoke", "top", inner=False, justify="center",
               minh={"unit": "vh", "size": 92, "sizes": []}, bg={**bg_image("dr-home-hero.jpg"), "background_color": "#050507"})


def stack(a, a_alt, b, b_alt):
    return con([
        image(a, a_alt, "dr-stack__a dr-fill"),
        image(b, b_alt, "dr-stack__b dr-fill dr-tint"),
    ], "dr-stack", width=50, width_t=100, width_m=100)


def welcome():
    copy = con([
        heading("Welcome", "dr-eyebrow", "div"),
        heading("Hi, thanks for dropping by", "dr-h2", "h2"),
        text("<p>Hi, thanks for dropping by to check out Dance Reaction. I know that planning a party or a celebration, "
             "be it a wedding, corporate dinner, birthday or any other type of event, can take some time, and trying to "
             "find the right DJ for your event may be frustrating.</p>"
             "<p>So what you will get here, is an uncomplicated honest look at what Dance Reaction is about, what I do, "
             "and how I make it work for your event.</p>", "dr-body-lg"),
        btn_row([button("Get in touch", "#enquiry"),
                 button("About Dance Reaction →", "#", "dr-btn dr-btn--link")]),
    ], "dr-split__copy", width=50, width_t=100, width_m=100, gap_=24, justify="center")

    row = con([stack("dr-home-dj.jpg", "DJ playing a set under neon lights",
                     "dr-home-dj-b.jpg", "DJ at the decks in a neon-lit club"), copy],
              "dr-split", direction="row", dir_t="column", gap_=gap(88, 40), align="center")
    return section([row], "dr-welcome dr-smoke", "welcome")


def why():
    head = con([
        heading("Booking a DJ", "dr-h2 dr-h2--md", "h2", align="center"),
        text("<p>When you are booking a dj for your function, the first thing you should be thinking about is what is "
             "important to the success of your function...</p>", align="center"),
        html('<a href="#enquiry" aria-label="Booking enquiry">'
             '<span class="dr-play__ring dr-play__ring--outer"></span>'
             '<span class="dr-play__ring dr-play__ring--inner"></span>'
             '<span class="dr-play__core"></span><span class="dr-play__icon">▶</span></a>', "dr-play"),
    ], "dr-why-head", align="center", gap_=18)

    tiles = []
    for n, (num, title, pic) in enumerate([("01", "Quality", "dr-home-why-1.jpg"),
                                           ("02", "Experience", "dr-home-why-2.jpg"),
                                           ("03", "Music range", "dr-home-why-3.jpg"),
                                           ("04", "Equipment", "dr-home-why-4.jpg")]):
        tiles.append(con([heading(num, "dr-tile__num", "div"), heading(title, "dr-tile__title", "h3")],
                         "dr-tile", width=25, width_t=48, width_m=47, justify="flex-end", gap_=4,
                         pad=box(0, 12, 14, 12), bg=bg_image(pic)))

    grid = con(tiles, "dr-tiles", direction="row", wrap="nowrap", wrap_t="wrap", gap_=gap(18, 18), align="flex-start",
               pad=box(72, 0, 0, 0), pad_m=box(48, 0, 0, 0))
    return section([head, grid], "dr-why", "why", bg=bg_grad(), pad=box(120, 48, 110, 48))


def price():
    copy = con([
        heading("Price should be the last thing.", "dr-h2 dr-h2--md", "h2"),
        text("<p>In reality, the cost of your entertainment is a small portion of your overall event budget - however, "
             "it WILL be the critical element of a successful event.</p>", "dr-body-lg"),
        btn_row([button("Booking enquiry", "#enquiry")], mt=6),
    ], "dr-split__copy", width=50, width_t=100, width_m=100, gap_=22, justify="center")
    row = con([stack("dr-home-price-a.jpg", "DJ under lasers", "dr-home-price-b.jpg", "Crowd dancing under confetti"), copy],
              "dr-split", direction="row", dir_t="column", gap_=gap(96, 40), align="center")
    return section([row], "dr-price dr-smoke", "price", bg=bg_color("#050507"), pad=box(130, 48, 130, 48))


def how():
    def card(pic, alt, eyebrow, body, note=False):
        return con([
            image(pic, alt, "dr-card__media dr-fill dr-tint"),
            con([heading(eyebrow, "dr-eyebrow", "h3"), text(f"<p>{body}</p>")],
                "dr-card__body", gap_=14, pad=box(48, 36, 36, 36), pad_m=box(44, 22, 26, 22)),
        ], "dr-card" + (" dr-card--note" if note else ""), width=50, width_t=100, width_m=100, gap_=0)

    cards = con([
        card("dr-home-how-a.jpg", "Couple's first dance at a wedding reception", "Planning & communication",
             "Through planning and communication, I will provide you with quality music and a great atmosphere designed "
             "specifically for your event. I have the experience and attention to detail to maximise the event's success!"),
        card("dr-home-how-b.jpg", "Guests at a corporate dinner", "Presented & professional",
             "You will find me well presented and professional at all times, whether DJing a private party or larger "
             "corporate events, all are treated with the same level of attention to detail, ensuring your event's "
             "complete success!"),
    ], "dr-cards", direction="row", dir_t="column", gap_=28, align="stretch")

    cta = con([
        heading("Be sure to contact me to discuss your events requirements in detail!", "dr-cta__text", "p"),
        btn_row([button("Booking enquiry", "#enquiry", "dr-btn dr-btn--dark"),
                 button(f"Call {PHONE}", TEL, "dr-btn dr-btn--light-outline")]),
    ], "dr-cta", direction="row", wrap="wrap", justify="space-between", align="center", gap_=20,
        pad=box(40, 48, 40, 48), pad_m=box(24, 22, 24, 22), bg=bg_grad(), radius=28)

    return section([cards, cta], "dr-how dr-smoke", "how", bg=bg_color("#050507"), gap_=48)


def events():
    head = con([
        heading("Events we cover", "dr-h2 dr-h2--xl", "h2"),
        button("Check your date", "#enquiry", "dr-btn dr-btn--dark"),
    ], "dr-events-head", direction="row", wrap="wrap", justify="space-between", align="flex-end", gap_=24)

    cards = []
    for title, pic in [("Weddings", "dr-home-ev-wedding.jpg"), ("Corporate dinners", "dr-home-ev-corporate.jpg"),
                       ("Birthdays", "dr-home-ev-birthday.jpg"), ("Any other event", "dr-home-hero.jpg")]:
        cards.append(con([heading(title, "dr-event__title", "h3")], "dr-event", width=25, width_t=48, width_m=100,
                         justify="flex-end", pad=box(18, 20, 18, 20), tag="a", link="#enquiry",
                         bg=bg_image(pic)))
    grid = con(cards, "dr-events-grid", direction="row", wrap="nowrap", wrap_t="wrap", gap_=16, align="stretch")
    return section([head, grid], "dr-events", "events", bg=bg_grad(), gap_=56)


TESTIMONIALS = [
    ("Thank you for all your help at our wedding at the zoo. With your experience and professionalism we enjoyed our "
     "reception knowing you were in control giving us one less thing to worry about.", "Thanks again,",
     "Robert and Sharon", "Wedding"),
    ("A big thank you for the sensational job you did at our wedding at the Brighton Savoy. Both Stewart and I, as well "
     "as our guests, loved the variety of music you played which was evident by the number of people on the dance "
     "floor. We definitely recommend you to our friends.", "Regards,", "Melinda and Stewart", "Wedding"),
    ("Thank you for doing a fantastic job as DJ/MC at our wedding. The music was great and your professionalism "
     "appreciated. All our Guests tell us they had a fantastic time. We know we certainly did. We could have easily "
     "partied on for a few hours more.", "Thanks once again,", "Leonie and Romeo", "Wedding"),
    ("I just wanted to say a big thank you for all your musical entertainment at my 21st birthday. The music couldn't "
     "have been better. I've passed your name on to many of my friends.", "Thanks again,", "Samantha", "21st Birthday"),
    ("Just a note to say thank you for all your efforts at our staff ball at the Regent Hotel. The evening was a great "
     "success and the dance floor was at capacity level throughout the night. Your selection of music suited all the "
     "different age groups within our company, and I have had many of our staff comment on the DJ and the music.",
     "Thanks,", "Peta", "Staff ball"),
    ("On behalf of my wife and myself, I sincerely thank you for the wonderful job by you on the occasion of our "
     "wedding at the Royal Melbourne Zoo. Your professionalism and ability to put both of us at ease on such an "
     "important day in our lives, was truly outstanding.", "Thank you, thank you, thank you.", "Ian and Debbie", "Wedding"),
    ("Please accept our sincere thanks for your entertainment at our wedding. Everyone at the function had a fantastic "
     "time.", "", "Anthony and Naomi", "Wedding"),
    ("It was really helpful having you take care of the music for our wedding reception. The night was a total success. "
     "Your MC/DJ skills and timing made the night.", "Thanks heaps for a really professional service,",
     "Yran & George", "Wedding"),
]


def testimonials():
    head = con([heading("Testimonials", "dr-eyebrow", "div", align="center"),
                heading("What they say", "dr-h2", "h2", align="center")],
               "dr-quotes-head", align="center", gap_=14)
    cards = []
    for body, sign, name, tag in TESTIMONIALS:
        cards.append(con([
            heading("Dear Bruce,", "dr-quote__hi", "div"),
            text(f"<p>{body}</p>" + (f"<p>{sign}</p>" if sign else ""), "dr-quote__text"),
            con([heading(name, "dr-quote__name", "div"), heading(tag, "dr-quote__tag", "div")], "dr-quote__by"),
        ], "dr-quote", gap_=18, pad=box(36, 34, 38, 34), pad_m=box(28, 26, 30, 26)))
    strip = con(cards, "dr-marquee", direction="row", wrap="nowrap", gap_=24, align="stretch")
    return section([head, strip], "dr-testimonials dr-smoke", "testimonials", bg=bg_color("#050507"), gap_=56)


def gallery():
    head = con([
        heading('Feel the <span class="dr-hp"><span>rhythm</span></span> of the night', "dr-rhythm", "h2", align="center"),
        text("<p>Weddings, corporate dinners, birthdays and any other type of event.</p>", "dr-muted", align="center"),
    ], "dr-gallery-head", align="center", gap_=18)
    grid = con([
        image("dr-home-g1.jpg", "DJ booth under lasers", "dr-g1 dr-fill dr-tint"),
        image("dr-home-g2.jpg", "DJ performing in a neon club", "dr-g2 dr-fill"),
        image("dr-home-g3.jpg", "Birthday celebration with sparklers", "dr-g3 dr-fill dr-tint"),
        image("dr-home-ev-wedding.jpg", "Wedding first dance", "dr-g4 dr-fill dr-tint"),
        image("dr-home-g5.jpg", "Party guests dancing", "dr-g5 dr-fill dr-tint"),
    ], "dr-gallery", direction="row", wrap="wrap")
    return section([head, grid, button("View gallery", "#", "dr-btn dr-btn--outline", align="center")],
                   "dr-gallery-sec dr-smoke", "gallery", gap_=56, pad=box(140, 48, 140, 48))


def enquiry():
    left = con([
        heading("Booking enquiry", "dr-eyebrow dr-eyebrow--line", "div"),
        heading('Book your <span class="dr-grad-text">DJ</span>', "dr-h2 dr-h2--xl", "h2"),
        text("<p>Send an enquiry with your event date, or call directly.</p>", "dr-body-lg"),
        con([
            con([heading("Call", "dr-info__label", "div"), heading(PHONE, "dr-info__value", "div")],
                "dr-info dr-info--call", tag="a", link=TEL),
            con([heading("Based in", "dr-info__label", "div"), heading("Sunshine North, VIC 3020", "dr-info__value", "div")],
                "dr-info dr-info--place"),
        ], "dr-info-list", gap_=12),
    ], "dr-split__copy", width=50, width_t=100, width_m=100, gap_=26, justify="center")
    right = con([shortcode("[dr_booking_form]", "dr-form-wrap")], "dr-split__form", width=50, width_t=100, width_m=100)
    row = con([left, right], "dr-split", direction="row", dir_t="column", gap_=gap(80, 40), align="center")
    return section([row], "dr-enquiry dr-smoke", "enquiry", bg={**bg_image("dr-home-hero.jpg"), "background_color": "#050507"})


def header_tpl():
    return [con([
        heading("Dance Reaction", "dr-logo", "div", link="/"),
        shortcode('[dr_menu location="primary"]', "dr-nav"),
        con([
            button(PHONE, TEL, "dr-btn dr-btn--plain"),
            button("Book now", "/#enquiry"),
            html('<button type="button" aria-label="Menu" aria-expanded="false"><span></span></button>', "dr-burger"),
        ], "dr-header__cta", direction="row", align="center", gap_=12, wrap="nowrap"),
    ], "dr-header", boxed=True, direction="row", justify="space-between", align="center", gap_=20, wrap="nowrap",
        pad=box(14, 48, 14, 48), pad_t=box(12, 32, 12, 32), pad_m=box(10, 16, 10, 16), inner=False)]


def footer_tpl():
    return [con([
        con([heading("Dance Reaction", "dr-footer__logo", "div", link="/"),
             shortcode('[dr_menu location="footer"]')],
            "dr-footer__top", direction="row", wrap="wrap", justify="space-between", align="center", gap_=20),
        con([text("<p>© Dance Reaction Mobile Discos [dr_year]. all rights reserved</p>", "dr-footer__copy"),
             shortcode('[dr_menu location="legal"]')],
            "dr-footer__bottom", direction="row", wrap="wrap", justify="space-between", align="center",
            gap_=gap(24, 10), pad=box(20, 0, 0, 0)),
    ], "dr-footer", boxed=True, gap_=20, pad=box(32, 48, 24, 48), pad_m=box(28, 16, 22, 16), bg=bg_grad(), inner=False)]


# ---------------------------------------------------------------- UAE (Header Footer Elementor) templates
# Widget setting keys match Ultimate Addons for Elementor 2.9.x
# (navigation-menu, hfe-site-title, copyright).

def hfe_site_title(cls):
    return widget("hfe-site-title", {"custom_link": "default", "heading_tag": "h2", "size": "default"}, cls)


def hfe_nav(menu, cls, *, size=15, weight="600", space=32, align="center", dropdown="none", color="#F4F1EA",
            hover="#FFFFFF", vpad=8):
    s = {
        "menu": menu,
        "layout": "horizontal",
        "navmenu_align": align,
        "pointer": "none",
        "dropdown": dropdown,
        "padding_horizontal_menu_item": px(0),
        "padding_vertical_menu_item": px(vpad),
        "menu_space_between": px(space),
        "menu_typography_typography": "custom",
        "menu_typography_font_family": "Archivo",
        "menu_typography_font_size": px(size),
        "menu_typography_font_weight": weight,
        "color_menu_item": color,
        "color_menu_item_hover": hover,
        "color_menu_item_active": hover,
    }
    if dropdown != "none":
        s.update({
            "full_width_dropdown": "yes",
            "resp_align": "left",
            "hamburger_align": "right",
            "hamburger_align_tablet": "right",
            "hamburger_align_mobile": "right",
            "toggle_color": "#FFFFFF",
            "toggle_hover_color": "#F08BEA",
            "toggle_size": px(22),
            "color_dropdown_item": "#F4F1EA",
            "background_color_dropdown_item": "#0B0A12",
            "color_dropdown_item_hover": "#FFFFFF",
            "background_color_dropdown_item_hover": "#17142A",
            "color_dropdown_item_active": "#FFFFFF",
            "background_color_dropdown_item_active": "#17142A",
            "dropdown_typography_typography": "custom",
            "dropdown_typography_font_family": "Archivo",
            "dropdown_typography_font_size": px(16),
            "dropdown_typography_font_weight": "600",
            "padding_horizontal_dropdown_item": px(24),
            "padding_vertical_dropdown_item": px(14),
            "divider_border_color": "rgba(244,241,234,0.1)",
            "distance_from_menu": px(12),
        })
    return widget("navigation-menu", s, cls)


def hfe_header_tpl():
    return [con([
        hfe_site_title("dr-logo"),
        hfe_nav("header-menu", "dr-nav dr-nav--hfe", dropdown="tablet"),
        con([
            button(PHONE, TEL, "dr-btn dr-btn--plain"),
            button("Book now", "/#enquiry"),
        ], "dr-header__cta", direction="row", align="center", gap_=12, wrap="nowrap"),
    ], "dr-header", boxed=True, direction="row", justify="space-between", align="center", gap_=20, wrap="nowrap",
        pad=box(14, 48, 14, 48), pad_t=box(12, 32, 12, 32), pad_m=box(10, 16, 10, 16), inner=False)]


def hfe_footer_tpl():
    return [con([
        con([hfe_site_title("dr-footer__logo"),
             hfe_nav("footer-menu", "dr-footer__nav", size=13, space=22, align="right", color="#FFFFFF",
                     hover="#050507", vpad=4)],
            "dr-footer__top", direction="row", wrap="wrap", justify="space-between", align="center", gap_=20),
        con([widget("copyright", {"shortcode": "© Dance Reaction Mobile Discos [hfe_current_year]. all rights reserved",
                                  "title_color": "#FFFFFF", "caption_typography_typography": "custom",
                                  "caption_typography_font_family": "Archivo", "caption_typography_font_size": px(12)},
                    "dr-footer__copy"),
             hfe_nav("footer-legal", "dr-footer__legal", size=12, weight="400", space=20, align="right",
                     color="#FFFFFF", hover="#050507", vpad=4)],
            "dr-footer__bottom", direction="row", wrap="wrap", justify="space-between", align="center",
            gap_=gap(24, 10), pad=box(20, 0, 0, 0)),
    ], "dr-footer", boxed=True, gap_=20, pad=box(32, 48, 24, 48), pad_m=box(28, 16, 22, 16), bg=bg_grad(), inner=False)]


def main():
    os.makedirs(OUT, exist_ok=True)
    pages = {
        "home.json": [hero(), welcome(), why(), price(), how(), events(), testimonials(), gallery(), enquiry()],
        "header.json": header_tpl(),
        "footer.json": footer_tpl(),
        "hfe-header.json": hfe_header_tpl(),
        "hfe-footer.json": hfe_footer_tpl(),
    }
    for name, data in pages.items():
        with open(os.path.join(OUT, name), "w") as f:
            json.dump(data, f, ensure_ascii=False, indent=1)
        print(name, len(json.dumps(data)))


if __name__ == "__main__":
    main()
