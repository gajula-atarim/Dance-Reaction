#!/usr/bin/env python3
"""Build the Elementor JSON for the Dance Reaction pages (Home, About, Gallery, The Music, Equipment, Contact, Site Map), header and footer.

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
        # Company name with "Mobile Disco" centred under it (client feedback).
        con([heading("Dance Reaction", "dr-hero-title", "h1"),
             heading("Mobile Disco", "dr-hero-sub", "p", align="center")], "dr-hero-name", align="center", gap_=8),
        heading('Feel the <span class="dr-hp"><span>rhythm</span></span> of the night', "dr-hero-tag dr-hero-tag--rhythm", "p"),
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
        text("<p>Hi, thanks for dropping by. I know that planning and organising a party or special celebration, be it a "
             "wedding, corporate event, birthday or a social club presentation evening, can be time consuming and "
             "stressful when trying to find the right DJ / music for your event.</p>"
             "<p>So what you will get here, is an uncomplicated honest look at what Dance Reaction is about, what I do, "
             "and how I make it work for your event.</p>", "dr-body-lg"),
        btn_row([button("Get in touch", "#enquiry"),
                 button("About Dance Reaction →", "/about/", "dr-btn dr-btn--link")]),
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


def gallery(view=False):
    head = con([
        heading('Feel the <span class="dr-hp"><span>rhythm</span></span> of the night', "dr-rhythm", "h2", align="center"),
        text("<p>Weddings, corporate dinners, birthdays and any other type of event.</p>", "dr-muted", align="center"),
    ], "dr-gallery-head", align="center", gap_=18)
    # Slideshow: the child theme shows these 5 at a time in the 5-tile grid (dr.js).
    pics = [(80, "gal_31"), (81, "gal_30"), (82, "gal_29"), (83, "gal_28"), (84, "gal_21"), (85, "gal_17"),
            (86, "gal_16"), (87, "gal_14"), (88, "gal_11"), (89, "gal_10"), (90, "gal_36"), (91, "gal_9"),
            (92, "IMG_68"), (93, "gal_01"), (94, "IMG_89"), (95, "gal_04"), (96, "IMG_96"), (97, "IMG_a72"),
            (98, "gal_06"), (99, "IMG_a74")]
    uploads = "https://dance-reaction.wsdfy.com/wp-content/uploads/2026/10/"
    grid = widget("image-gallery", {
        "wp_gallery": [{"id": i, "url": f"{uploads}{n}.jpg"} for i, n in pics],
        "thumbnail_size": "full",
        "gallery_columns": "4",
        "gallery_link": "none",
        "open_lightbox": "no",
        "gallery_rand": "",
    }, "dr-gallery-show")
    parts = [head, grid]
    if view:  # "View gallery" under the slideshow (all pages except Gallery itself).
        parts.append(btn_row([button("View gallery", "/gallery/", "dr-btn dr-btn--outline")], justify="center"))
    return section(parts, "dr-gallery-sec dr-smoke", "gallery", gap_=56, pad=box(140, 48, 140, 48))


def enquiry():
    left = con([
        heading("Booking enquiry", "dr-eyebrow dr-eyebrow--line", "div"),
        heading('Book your <span class="dr-grad-text">DJ</span>', "dr-h2 dr-h2--xl", "h2"),
        text("<p>Send an enquiry with your event date, or call directly.</p>", "dr-body-lg"),
        con([
            con([heading("Call", "dr-info__label", "div"), heading(PHONE, "dr-info__value", "div")],
                "dr-info dr-info--call", tag="a", link=TEL),
            con([heading("Based in", "dr-info__label", "div"), heading("Preston, VIC 3072", "dr-info__value", "div")],
                "dr-info dr-info--place"),
        ], "dr-info-list", gap_=12),
    ], "dr-split__copy", width=50, width_t=100, width_m=100, gap_=26, justify="center")
    right = con([shortcode("[dr_booking_form]", "dr-form-wrap")], "dr-split__form", width=50, width_t=100, width_m=100)
    row = con([left, right], "dr-split", direction="row", dir_t="column", gap_=gap(80, 40), align="center")
    return section([row], "dr-enquiry dr-smoke", "enquiry", bg={**bg_image("dr-home-hero.jpg"), "background_color": "#050507"})


# ---------------------------------------------------------------- About page


def about_hero():
    content = con([
        heading("Mobile DJ / MC · Melbourne", "dr-badge", "div"),
        heading("About Me", "dr-hero-title dr-hero-title--about", "h1"),
        heading("All things to all events", "dr-hero-tag", "p"),
        btn_row([button("Booking enquiry", "/contact/"),
                 button(f"Call {PHONE}", TEL, "dr-btn dr-btn--outline")], mt=6),
    ], "dr-hero-content", boxed=True, align="flex-start", justify="center", gap_=22,
        pad=box(110, 48, 110, 48), pad_t=box(90, 32, 90, 32), pad_m=box(56, 16, 56, 16))
    return con([content], "dr-sec dr-hero dr-hero--about dr-smoke", "about-hero", inner=False, justify="center",
               bg={**bg_image("dr-about-hero.jpg"), "background_color": "#050507"})


TOYS = [("01", "Music", "A select area of my music collection"),
        ("02", "DJ gear", "The kit behind every event"),
        ("03", "Ski boat", "Out on the water"),
        ("04", "The shack", "A little holiday shack on the Murray river")]


def family():
    collage = con([
        # Client photo: Cheerful-DJ-Under-Blue-Stage-Lights.png from the Media Library.
        image("dr-family-main.png", "A family man with his boys toys", "dr-collage__main dr-fill"),
        image("dr-x-controller.jpg", "Hands on a neon-lit DJ controller", "dr-collage__disc dr-fill"),
        con([heading("2", "dr-collage__num", "div"), heading("Kids · all grown up", "dr-collage__cap", "div")],
            "dr-collage__badge", gap_=4),
    ], "dr-collage", width=50, width_t=100, width_m=100)

    toys = con([con([heading(n, "dr-toy__num", "div"), heading(t, "dr-toy__title", "h3"), heading(d, "dr-toy__text", "p")],
                    "dr-toy") for n, t, d in TOYS], "dr-toys", direction="row", wrap="wrap", gap_=12)

    copy = con([
        heading("Off the decks", "dr-eyebrow", "div"),
        heading('A family man with his <span class="dr-grad-text">boys toys</span>', "dr-h2 dr-h2--lg", "h2"),
        text("<p>I am a family man with two kids (all grown up living their own lives) I have my boys toys which include "
             "- a select area of my music collection, dj gear, my ski boat and a little holiday shack on the Murray river "
             "which is a great place to relax when I'm not entertaining....and I have other hobbies and interests as well "
             "just to keep me busy (when I find some spare time).....</p>", "dr-body-lg"),
        toys,
    ], "dr-split__copy", width=50, width_t=100, width_m=100, gap_=26, justify="center")

    row = con([collage, copy], "dr-split", direction="row", dir_t="column", gap_=gap(88, 56), align="center")
    return section([row], "dr-family dr-smoke", "family", pad=box(150, 48, 150, 48), pad_t=box(110, 32, 110, 32),
                   pad_m=box(80, 16, 80, 16))


STEPS = [("Early 1980s", "One night at the local hotel disco"),
         ("Learning the ropes", "Time with a few DJ's setting up and learning the ins and outs"),
         ("Until the mid 1990s", "Melbourne hotels, night clubs, and 4 &amp; 5 star reception venues")]


def story():
    record = con([
        con([image("dr-about-story.jpg", "Vinyl records in a crate", "dr-sleeve__img dr-fill"),
             heading("80's", "dr-sleeve__label", "div")], "dr-sleeve"),
    ], "dr-vinyl", width=50, width_t=100, width_m=100)

    copy = con([
        heading("Where it began", "dr-eyebrow", "div"),
        heading('It all started one night in the <span class="dr-grad-text">early eighty’s</span>', "dr-h2 dr-h2--lg", "h2"),
        text("<p>For me, It all started one night in the early eighty’s at the local hotel disco, walking into the venue "
             "and seeing everyone having a great time dancing away their work day blues socialising, meeting new people "
             "and catching up with old friends.</p>", "dr-body-lg"),
    ], "dr-split__copy", width=50, width_t=100, width_m=100, gap_=22, justify="center")
    row = con([record, copy], "dr-split", direction="row", dir_t="column", gap_=gap(100, 48), align="center")

    bars = "".join(f'<i style="--h:{h}%;--d:{d}s;--t:{t}s"></i>'
                   for h, d, t in [((30 + (i * 29) % 62), round(.6 + (i * .1) % 1, 2), round((i * .1) % 1.6, 2))
                                   for i in range(36)])
    neon = con([
        heading("All this was the work of one person", "dr-neon__kicker", "div", align="center"),
        heading('"The DJ"', "dr-neon__title", "p", align="center"),
        text("<p>The enthusiasm and skill the DJ used to create this atmosphere and keep the dance floor pumping inspired "
             "me. It was a great night and I decided there and then that I wanted to do the same.</p>",
             "dr-body-lg dr-neon__text", align="center"),
        html(f'<div class="dr-bars" aria-hidden="true">{bars}</div>', "dr-neon__bars"),
    ], "dr-neon", align="center", gap_=18, pad=box(110, 56, 0, 56), pad_t=box(80, 32, 0, 32), pad_m=box(56, 20, 0, 20))

    steps = con([con([heading(t, "dr-step__title", "h3"), heading(d, "dr-step__text", "p")], "dr-step", gap_=10)
                 for t, d in STEPS], "dr-timeline", direction="row", dir_m="column", gap_=gap(40, 32))

    after = text("<p>After doing some time with a few DJ's setting up and learning the ins and outs, I set about creating "
                 "my own company and from then and until the mid 1990's I worked at a good majority of Melbourne hotels, "
                 "night clubs, and 4 &amp; 5 star reception venues. This included a very healthy diet of weddings, "
                 "corporate events, and other various types of parties as a mobile DJ/MC.</p>", "dr-body-lg dr-story__after")

    return section([row, neon, steps, after], "dr-story dr-smoke", "story", gap_=gap(0, 100),
                   pad=box(140, 48, 140, 48), pad_t=box(100, 32, 100, 32), pad_m=box(80, 16, 80, 16),
                   bg=bg_color("#050507"))


def aim():
    return section([
        heading("My aim", "dr-eyebrow dr-eyebrow--light", "div", align="center"),
        heading("My aim is to allow everyone, including the organisers to relax and enjoy the event.", "dr-aim__text",
                "p", align="center"),
    ], "dr-aim", "aim", align="center", gap_=20, bg=bg_grad(),
        pad=box(110, 48, 110, 48), pad_t=box(80, 32, 80, 32), pad_m=box(64, 16, 64, 16))


def dj_style():
    copy = con([
        heading("My DJ style", "dr-eyebrow", "div"),
        heading("A Mobile DJ for every event", "dr-h2", "h2"),
        text("<p>When asked what my DJ style is, I am a Mobile DJ and as a Mobile DJ you need to be able to change your "
             "style to suit the event and demographic.</p>"
             "<p>I think it is fair to say, I believe with my years of experience and various types of functions I can "
             "be all things to all events.</p>", "dr-body-lg"),
        btn_row([button("Get in touch", "/contact/"),
                 button("Back to home →", "/", "dr-btn dr-btn--link")]),
    ], "dr-split__copy", width=50, width_t=100, width_m=100, gap_=24, justify="center")
    row = con([stack("dr-about-c.jpg", "DJ performing for a crowd", "dr-about-d.jpg", "Party lights over the dance floor"),
               copy], "dr-split", direction="row", dir_t="column", gap_=gap(88, 40), align="center")
    return section([row], "dr-style dr-smoke", "style", pad=box(130, 48, 130, 48))


# ---------------------------------------------------------------- Gallery page

GALLERY_PICS = [(80, "gal_31"), (81, "gal_30"), (82, "gal_29"), (83, "gal_28"), (84, "gal_21"), (85, "gal_17"),
                (86, "gal_16"), (87, "gal_14"), (88, "gal_11"), (89, "gal_10"), (90, "gal_36"), (91, "gal_9"),
                (92, "IMG_68"), (93, "gal_01"), (94, "IMG_89"), (95, "gal_04"), (96, "IMG_96"), (97, "IMG_a72"),
                (98, "gal_06"), (99, "IMG_a74")]
UPLOADS = "https://dance-reaction.wsdfy.com/wp-content/uploads/2026/10/"


def gallery_hero():
    content = con([
        heading("Mobile DJ / MC · Melbourne", "dr-badge", "div"),
        heading("Gallery", "dr-hero-title dr-hero-title--about", "h1"),
        heading("Feel the rhythm of the night", "dr-hero-tag", "p"),
        btn_row([button("Booking enquiry", "/contact/"),
                 button(f"Call {PHONE}", TEL, "dr-btn dr-btn--outline")], mt=6),
    ], "dr-hero-content", boxed=True, align="flex-start", justify="center", gap_=22,
        pad=box(110, 48, 110, 48), pad_t=box(90, 32, 90, 32), pad_m=box(56, 16, 56, 16))
    return con([content], "dr-sec dr-hero dr-hero--about dr-smoke", "gallery-hero", inner=False, justify="center",
               bg={**bg_image("dr-gallery-hero.jpg"), "background_color": "#050507"})


def photo_wall():
    # Tile shapes follow the design's 16-tile pattern (ratio, alternate corners, glow, colour wash).
    ratios = ["45", "11", "32", "45", "34", "11", "32", "45", "45", "11", "32", "45", "34", "11", "32", "45"]
    tiles = []
    for n, (pid, name) in enumerate(GALLERY_PICS):
        k = n % 16
        cls = f"dr-mtile dr-mtile--{ratios[k]} dr-fill"
        if k % 3 == 1:
            cls += " dr-mtile--alt"
        if k in (2, 7, 12):
            cls += " dr-mtile--glow"
        if k % 2 == 0:
            cls += " dr-tint"
        tiles.append(widget("image", {
            "image": {"id": pid, "url": f"{UPLOADS}{name}.jpg", "size": "", "alt": "Dance Reaction event photo",
                      "source": "library"},
            "image_size": "large", "caption_source": "none", "link_to": "file", "open_lightbox": "yes",
        }, cls))

    head = con([
        heading("Gallery", "dr-eyebrow", "div", align="center"),
        heading('Weddings, parties &amp; <span class="dr-grad-text">corporate events</span>', "dr-h2 dr-h2--lg", "h2",
                align="center"),
    ], "dr-photos-head", align="center", gap_=16)
    wall = con(tiles, "dr-masonry")
    ctas = btn_row([button("Book your DJ", "/contact/"),
                    button(f"Call {PHONE}", TEL, "dr-btn dr-btn--outline")], justify="center")
    return section([head, wall, ctas], "dr-photos dr-smoke", "photos", gap_=56, pad=box(130, 48, 130, 48),
                   pad_t=box(96, 32, 96, 32), pad_m=box(72, 16, 72, 16))


# ---------------------------------------------------------------- Site Map page

SITEMAP = [
    ("Home", "/", "The welcome, what to look for when booking a DJ, and the events Dance Reaction covers.",
     [("Welcome", "#welcome"), ("Booking a DJ", "#why"), ("Price should be the last thing", "#price"),
      ("Planning &amp; communication", "#how"), ("Events we cover", "#events"), ("Testimonials", "#testimonials"),
      ("Gallery", "#gallery"), ("Booking enquiry", "#enquiry")]),
    ("About Me", "/about/", "The DJ behind Dance Reaction — from a night at the local hotel disco to every kind of event.",
     [("Off the decks", "#family"), ("Where it began", "#story"), ("My aim", "#aim"), ("My DJ style", "#style"),
      ("Testimonials", "#testimonials"), ("Gallery", "#gallery")]),
    ("The Music", "/the-music/", "From the swing era to the dance hits of today, and how the music is chosen on the night.",
     [("All the music you require", "#library"), ("The music list", "#list"), ("Your music, your guests", "#requests"),
      ("Reading the crowd", "#crowd"), ("Testimonials", "#testimonials"), ("Gallery", "#gallery")]),
    ("Equipment", "/equipment/", "The equipment package, the standards it meets and the guest numbers it suits.",
     [("My gear", "#intro"), ("Quality and reliability", "#standards"), ("The gear", "#gear"),
      ("Guest lists", "#capacity"), ("Testimonials", "#testimonials"), ("Gallery", "#gallery")]),
    ("Gallery", "/gallery/", "Photos from weddings, parties and corporate events.",
     [("Photo wall", "#photos"), ("Testimonials", "#testimonials")]),
    ("Contact", "/contact/", "Send an enquiry or get in touch directly by phone or email.",
     [("Enquiry form &amp; contact details", "#contact")]),
]


def sitemap_section():
    cards = []
    for n, (title, url, desc, secs) in enumerate(SITEMAP, 1):
        links = "".join(f'<li><a href="{url}{a}">{t}</a></li>' for t, a in secs)
        cards.append(con([
            heading(f"{n:02d}", "dr-smap__num", "div"),
            heading(title, "dr-smap__title", "h2", link=url),
            text(f"<p>{desc}</p>", "dr-smap__desc"),
            text(f'<ul class="dr-smap__links">{links}</ul>', "dr-smap__list"),
            button(f"Go to {title} →", url, "dr-btn dr-btn--link"),
        ], "dr-smap", width=33, width_t=48, width_m=100, gap_=14, pad=box(36), pad_t=box(30, 28, 30, 28),
            pad_m=box(26, 22, 26, 22)))
    head = con([
        heading("Site map", "dr-eyebrow", "div", align="center"),
        heading('Every page, <span class="dr-grad-text">at a glance</span>', "dr-h2 dr-h2--lg", "h2", align="center"),
    ], "dr-photos-head", align="center", gap_=16)
    grid = con(cards, "dr-smap-grid", direction="row", wrap="wrap", gap_=gap(24, 24), align="stretch")
    return section([head, grid, cta_pair()], "dr-sitemap dr-smoke", "sitemap", gap_=56, pad=box(130, 48, 130, 48),
                   pad_t=box(96, 32, 96, 32), pad_m=box(72, 16, 72, 16))


# ---------------------------------------------------------------- Shared blocks for the inner pages

CONTACT = "/contact/"


def page_hero(sid, title, tag, pic):
    content = con([
        heading("Mobile DJ / MC · Melbourne", "dr-badge", "div"),
        heading(title, "dr-hero-title dr-hero-title--about", "h1"),
        heading(tag, "dr-hero-tag", "p"),
        btn_row([button("Booking enquiry", CONTACT),
                 button(f"Call {PHONE}", TEL, "dr-btn dr-btn--outline")], mt=6),
    ], "dr-hero-content", boxed=True, align="flex-start", justify="center", gap_=22,
        pad=box(110, 48, 110, 48), pad_t=box(90, 32, 90, 32), pad_m=box(56, 16, 56, 16))
    return con([content], "dr-sec dr-hero dr-hero--about dr-smoke", sid, inner=False, justify="center",
               bg={**bg_image(pic), "background_color": "#050507"})


def collage(a, a_alt, b, b_alt, flip=False):
    """Tilted gradient plate, a main photo and a round inset photo (About / Music / Equipment)."""
    return con([image(a, a_alt, "dr-collage__main dr-fill"), image(b, b_alt, "dr-collage__disc dr-fill")],
               "dr-collage" + (" dr-collage--flip" if flip else ""), width=50, width_t=100, width_m=100)


def eq_bars(n=36, cls="dr-bars"):
    bars = "".join(f'<i style="--h:{30 + (i * 29) % 62}%;--d:{round(.6 + (i * .1) % 1, 2)}s;--t:{round((i * .1) % 1.6, 2)}s"></i>'
                   for i in range(n))
    return html(f'<div class="{cls}" aria-hidden="true">{bars}</div>', "dr-neon__bars")


def neon(kicker, title, body=None, size=""):
    kids = [heading(kicker, "dr-neon__kicker", "div", align="center"),
            heading(title, "dr-neon__title" + (f" dr-neon__title--{size}" if size else ""), "p", align="center")]
    if body:
        kids.append(text(f"<p>{body}</p>", "dr-body-lg dr-neon__text", align="center"))
    kids.append(eq_bars())
    return con(kids, "dr-neon", align="center", gap_=18, pad=box(96, 56, 0, 56), pad_t=box(72, 32, 0, 32),
               pad_m=box(48, 20, 0, 20))


def panel(eyebrow, title, body, extra=None, grad=False, title_cls=""):
    kids = [heading(eyebrow, "dr-eyebrow" + (" dr-eyebrow--light" if grad else ""), "div"),
            heading(title, ("dr-panel__title " + title_cls).strip(), "h3"),
            text(f"<p>{body}</p>", "dr-body-lg")]
    if extra:
        kids.append(extra)
    return con(kids, "dr-panel" + (" dr-panel--grad" if grad else ""), width=50, width_t=100, width_m=100, gap_=20,
               justify="space-between" if grad else "flex-start", pad=box(48, 48, 48, 48), pad_t=box(36, 32, 36, 32),
               pad_m=box(28, 24, 28, 24))


def checklist(items, gear=False):
    rows = [con([heading(f"{n:02d}", "dr-check__num", "div"), heading(t, "dr-check__text", "p")], "dr-check")
            for n, t in enumerate(items, 1)]
    return con(rows, "dr-checklist" + (" dr-checklist--gear" if gear else ""), gap_=6, pad=box(8))


def cta_pair(label="Book your DJ"):
    return btn_row([button(label, CONTACT), button(f"Call {PHONE}", TEL, "dr-btn dr-btn--outline")], justify="center")


# ---------------------------------------------------------------- The Music page

GENRES = ["Swing", "Rock ’n’ Roll", "60s", "70s Disco", "80s", "90s", "2000s", "Today’s dance hits"]


def music_library():
    years = con([heading('25<span>+</span>', "dr-bignum", "div"),
                 heading("Years in the entertainment industry", "dr-bignum__label", "p")],
                "dr-years", width=50, width_t=100, width_m=100, gap_=6, align="flex-start", justify="center")
    copy = con([
        heading("The music library", "dr-eyebrow", "div"),
        heading('All the music <span class="dr-grad-text">you require</span>', "dr-h2 dr-h2--lg", "h2"),
        text("<p>The Dance Reaction music library, is the result of over twenty five years in the entertainment "
             "industry, and I'm confident, that you will find all the music you require from the swing era through to "
             "the current dance hits of today.</p>", "dr-body-lg"),
    ], "dr-split__copy", width=50, width_t=100, width_m=100, gap_=22, justify="center")
    row = con([years, copy], "dr-split", direction="row", dir_t="column", gap_=gap(88, 40), align="center")
    ticker = con([heading(g, "dr-ticker__item", "div") for g in GENRES], "dr-marquee dr-ticker", direction="row",
                 wrap="nowrap", gap_=44, align="center")
    return section([row, ticker], "dr-library dr-smoke", "library", gap_=gap(0, 90), pad=box(140, 48, 100, 48),
                   pad_t=box(100, 32, 80, 32), pad_m=box(80, 16, 64, 16))


def music_list():
    quote = heading('For the most part, inexperience and personal music tastes, '
                    '<span class="dr-grad-text">obscure the success of an event.</span>', "dr-bigquote", "p",
                    align="center")
    cards = con([
        panel("The music list", "Built on what fills the floor",
              "To simplify the whole process, I've compiled a music list for you, that is not made up of my personal "
              "choices, but by the averages of all the successful songs played and requested at every event I perform at.",
              eq_bars(28, "dr-bars dr-bars--sm")),
        panel("Online music library", "Fully searchable",
              "Use it in conjunction with Dance Reaction's fully searchable online music library, to further satisfy "
              "your individual requirements.",
              button("Search the music library →", "#", "dr-btn dr-btn--dark"), grad=True),
    ], "dr-panels", direction="row", dir_t="column", gap_=32, align="stretch")
    return section([quote, cards], "dr-list dr-smoke", "list", gap_=72, pad=box(130, 48, 130, 48),
                   pad_t=box(96, 32, 96, 32), pad_m=box(72, 16, 72, 16), bg=bg_color("#050507"))


def music_requests():
    copy = con([
        heading("Your music, your guests", "dr-eyebrow", "div"),
        heading('The music you <span class="dr-grad-text">want and need</span> to hear', "dr-h2 dr-h2--lg", "h2"),
        text("<p>My aim is to always play the music both you and your guests want, and need to hear, and as well as "
             "accommodating your favourite music Your guests are encouraged to request their choices throughout the "
             "event, and at the same time I will bear in mind your personal wishes, and apply professional discretion "
             "with regard to any requests made.</p>", "dr-body-lg"),
        btn_row([button("Booking enquiry", CONTACT), button(f"Call {PHONE}", TEL, "dr-btn dr-btn--outline")]),
    ], "dr-split__copy", width=50, width_t=100, width_m=100, gap_=24, justify="center")
    row = con([collage("dr-requests-main.webp", "Your music, your guests", "dr-x-turntable.jpg", "A record spinning on a turntable"), copy],
              "dr-split", direction="row", dir_t="column", gap_=gap(100, 56), align="center")
    return section([row], "dr-requests dr-smoke", "requests", pad=box(130, 48, 130, 48), pad_t=box(110, 32, 110, 32),
                   pad_m=box(80, 16, 80, 16))


def music_crowd():
    tips = [("01 · Working well", "For example, if a particular music style or era is working well, I will continue to "
             "play more of these songs.", False),
            ("02 · Not working", "If, on the other hand, a particular genre of music is not working so well, I will move "
             "on and try another.", False),
            ("03 · The art of it", "The art of this, is that I will spot small tell-tale signs, and adjust the music "
             "early, ensuring you of a packed dancefloor at all times.", True)]
    cards = con([con([heading(t, "dr-tip__label", "div"), text(f"<p>{b}</p>", "dr-body-lg")],
                     "dr-tip" + (" dr-tip--grad" if g else ""), gap_=14, pad=box(36), pad_t=box(28), pad_m=box(24))
                 for t, b, g in tips], "dr-tips", direction="row", dir_t="column", gap_=24, align="stretch")
    return section([
        neon("The right music at the right time", "Reading the crowd",
             "Ensuring the right music is played at the right time, is called reading the crowd. I constantly monitor "
             "the dance floor for the crowd reactions to the music being played.", size="md"),
        cards, cta_pair(),
    ], "dr-crowd", "crowd", gap_=64, pad=box(130, 48, 130, 48), pad_t=box(96, 32, 96, 32), pad_m=box(72, 16, 72, 16),
        bg=bg_color("#050507"))


# ---------------------------------------------------------------- Equipment page

STANDARDS = ["Clean and regularly maintained components and media",
             "Brands with a proven track record of performance and reliability",
             "A professional standard of presentation",
             "Clear, quality sound for both vocal, and full range music applications",
             "Back up contingencies - you never know!",
             "tested &amp; tagged equipment as required by law, and by qualified persons",
             "roadcased components", "industry standard rigging", "safe wiring practices",
             "restricted access to equipment"]
GEAR = ["Native Instruments Traktor Pro", "Wireless Mic Systems", "DBX compressior/limiter",
        "HK Linier3 1000 Active Speakers", "HK Linier3 1000 Active Sub Speakers",
        "DMX Controlled &amp; Sound Activated lighting"]


def equip_intro():
    left = con([
        heading("My gear", "dr-eyebrow", "div"),
        heading('Possibly one of the most important, yet overlooked decisions made in the planning of any event, is '
                '<span class="dr-grad-text">the equipment package supplied.</span>', "dr-statement", "p"),
    ], "dr-split__copy", width=50, width_t=100, width_m=100, gap_=22)
    right = con([image("dr-x-intro.jpg", "Hands on a DJ mixer in the smoke and lights", "dr-intro__img dr-fill dr-tint"),
                 text("<p>The equipment, will carry a good percentage of the success of your function, squarely on its "
                      "capacity to perform, and perform to a standard.</p>", "dr-body-lg")],
                "dr-split__copy", width=50, width_t=100, width_m=100, gap_=24)
    row = con([left, right], "dr-split", direction="row", dir_t="column", gap_=gap(80, 32), align="center")
    return section([row, neon("Demand quality, and reliability in the equipment you are supplied.",
                              "Your event depends on it.", size="sm")],
                   "dr-intro dr-smoke", "intro", gap_=gap(0, 96), pad=box(140, 48, 140, 48), pad_t=box(100, 32, 100, 32),
                   pad_m=box(80, 16, 80, 16))


def equip_standards():
    copy = con([heading("What you can expect", "dr-eyebrow", "div"),
                heading('Quality and <span class="dr-grad-text">reliability</span>', "dr-h2 dr-h2--lg", "h2"),
                checklist(STANDARDS)],
               "dr-split__copy", width=50, width_t=100, width_m=100, gap_=24, justify="center")
    row = con([collage("dr-equip-std.jpg", "DJ equipment set up for an event", "dr-equip-hero.jpg",
                       "Mixer and lighting controls"), copy],
              "dr-split", direction="row", dir_t="column", gap_=gap(100, 56), align="center")
    return section([row], "dr-standards dr-smoke", "standards", pad=box(130, 48, 130, 48), pad_t=box(110, 32, 110, 32),
                   pad_m=box(80, 16, 80, 16))


def equip_gear():
    copy = con([heading("The system", "dr-eyebrow", "div"),
                heading('The <span class="dr-grad-text">gear</span>', "dr-h2 dr-h2--lg", "h2"),
                checklist(GEAR, gear=True),
                text("<p>My system comes fully self contained. I don’t require any extra equipment from the venue "
                     "(except 240v power points)</p>", "dr-body-lg")],
               "dr-split__copy", width=50, width_t=100, width_m=100, gap_=24, justify="center")
    row = con([copy, collage("dr-equip-gear.webp", "DJ equipment set up for an event", "dr-music-hero.jpg",
                             "Dance floor lights", flip=True)],
              "dr-split", direction="row", dir_t="column-reverse", gap_=gap(100, 56), align="center")
    return section([row], "dr-gear dr-smoke", "gear", pad=box(130, 48, 130, 48), pad_t=box(110, 32, 110, 32),
                   pad_m=box(80, 16, 80, 16))


def equip_capacity():
    guests = con([
        heading("Guest lists", "dr-eyebrow", "div"),
        heading('50<span>–</span>400', "dr-bignum dr-bignum--md", "div"),
        text("<p>This system suits events catering guest lists from 50, up to 400 people, without over driving the audio "
             "system and keeping the sound quality at a premium, while providing enough lighting to keep the dance floor "
             "buzzing all night.</p>", "dr-body-lg"),
    ], "dr-panel", width=50, width_t=100, width_m=100, gap_=22, pad=box(48), pad_t=box(36, 32, 36, 32),
        pad_m=box(28, 24, 28, 24))
    more = panel("Custom package · POA", "Need more?",
                 "Should your event require more, then I am more than happy to arrange a 'custom' package to suit your "
                 "individual needs - POA.", button("Ask about a custom package →", CONTACT, "dr-btn dr-btn--dark"),
                 grad=True, title_cls="dr-panel__title--lg")
    cards = con([guests, more], "dr-panels", direction="row", dir_t="column", gap_=32, align="stretch")
    return section([cards, cta_pair()], "dr-capacity", "capacity", gap_=32, pad=box(130, 48, 130, 48),
                   pad_t=box(96, 32, 96, 32), pad_m=box(72, 16, 72, 16), bg=bg_color("#050507"))


# ---------------------------------------------------------------- Contact page

def contact_section():
    left = con([
        heading("Contact", "dr-eyebrow dr-eyebrow--line", "div"),
        heading('Dance Reaction <span class="dr-grad-text">Mobile Discos</span>', "dr-h2 dr-h2--lg", "h2"),
        heading("abn: 63 006 859 187", "dr-abn", "div"),
        con([
            con([heading("Mobile", "dr-info__label", "div"), heading(PHONE, "dr-info__value", "div")],
                "dr-info dr-info--call dr-info--row", tag="a", link=TEL),
            con([heading("Email", "dr-info__label", "div"), heading("info@dancereaction.com.au", "dr-info__value", "div")],
                "dr-info dr-info--mail dr-info--row", tag="a", link="mailto:info@dancereaction.com.au"),
            con([heading("Web", "dr-info__label", "div"), heading("www.dancereaction.com.au", "dr-info__value", "div")],
                "dr-info dr-info--web dr-info--row", tag="a", link="https://www.dancereaction.com.au"),
        ], "dr-info-list dr-info-list--stack", gap_=12),
    ], "dr-split__copy", width=50, width_t=100, width_m=100, gap_=26)
    right = con([shortcode('[dr_booking_form layout="contact" anchor="contact" button="Send to Dance reaction"]',
                           "dr-form-wrap")], "dr-split__form", width=50, width_t=100, width_m=100)
    row = con([left, right], "dr-split", direction="row", dir_t="column", gap_=gap(80, 40), align="flex-start")
    return section([row], "dr-contact dr-smoke", "contact", pad=box(130, 48, 130, 48), pad_t=box(96, 32, 96, 32),
                   pad_m=box(72, 16, 72, 16))


# ---------------------------------------------------------------- UAE (Header Footer Elementor) templates
# Widget setting keys match Ultimate Addons for Elementor 2.9.x
# (navigation-menu, hfe-site-title, copyright).

def hfe_site_title(cls):
    return widget("hfe-site-title", {"custom_link": "default", "heading_tag": "h2", "size": "default"}, cls)


def hfe_nav(menu, cls, *, size=15, weight="600", space=32, align="center", dropdown="none", color="#F4F1EA",
            hover="#FFFFFF", vpad=8, active=None):
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
        "color_menu_item_active": active or hover,
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
            button("Book now", "/contact/"),
        ], "dr-header__cta", direction="row", align="center", gap_=12, wrap="nowrap"),
    ], "dr-header", boxed=True, direction="row", justify="space-between", align="center", gap_=20, wrap="nowrap",
        pad=box(14, 48, 14, 48), pad_t=box(12, 32, 12, 32), pad_m=box(10, 16, 10, 16), inner=False)]


def hfe_footer_tpl():
    return [con([
        con([hfe_site_title("dr-footer__logo"),
             hfe_nav("footer-menu", "dr-footer__nav", size=13, space=22, align="right", color="#FFFFFF",
                     hover="#050507", vpad=4, active="#FFFFFF")],
            "dr-footer__top", direction="row", wrap="wrap", justify="space-between", align="center", gap_=20),
        con([widget("copyright", {"shortcode": "© Dance Reaction Mobile Discos [hfe_current_year]. all rights reserved",
                                  "title_color": "#FFFFFF", "caption_typography_typography": "custom",
                                  "caption_typography_font_family": "Archivo", "caption_typography_font_size": px(12)},
                    "dr-footer__copy"),
             hfe_nav("footer-legal", "dr-footer__legal", size=12, weight="400", space=20, align="right",
                     color="#FFFFFF", hover="#050507", vpad=4, active="#FFFFFF")],
            "dr-footer__bottom", direction="row", wrap="wrap", justify="space-between", align="center",
            gap_=gap(24, 10), pad=box(20, 0, 0, 0)),
    ], "dr-footer", boxed=True, gap_=20, pad=box(32, 48, 24, 48), pad_m=box(28, 16, 22, 16), bg=bg_grad(), inner=False)]


def main():
    os.makedirs(OUT, exist_ok=True)
    pages = {
        "home.json": [hero(), welcome(), why(), price(), how(), events(), testimonials(), gallery(view=True), enquiry()],
        "hfe-header.json": hfe_header_tpl(),
        "hfe-footer.json": hfe_footer_tpl(),
        "about.json": [about_hero(), family(), story(), aim(), dj_style(), testimonials(), gallery(view=True)],
        "gallery.json": [gallery_hero(), photo_wall(), testimonials()],
        "music.json": [page_hero("music-hero", "The Music", "From the swing era to the dance hits of today",
                                 "dr-music-hero.jpg"),
                       music_library(), music_list(), music_requests(), music_crowd(), testimonials(),
                       gallery(view=True)],
        "equipment.json": [page_hero("equip-hero", "Equipment", "My gear", "dr-equip-hero.jpg"),
                           equip_intro(), equip_standards(), equip_gear(), equip_capacity(), testimonials(),
                           gallery(view=True)],
        "contact.json": [page_hero("contact-hero", "Contact", "Get in touch", "dr-contact-hero.jpg"),
                         contact_section()],
        "sitemap.json": [page_hero("sitemap-hero", "Site Map", "Every page at a glance", "dr-sitemap-hero.jpg"),
                         sitemap_section()],
    }
    for name, data in pages.items():
        with open(os.path.join(OUT, name), "w") as f:
            json.dump(data, f, ensure_ascii=False, indent=1)
        print(name, len(json.dumps(data)))


if __name__ == "__main__":
    main()
