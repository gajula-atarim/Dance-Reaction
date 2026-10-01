/**
 * Dance Reaction — front-end effects.
 *
 * Purely decorative layers (floating notes, flares, dot circles, smoke) are added
 * here so they never clutter the Elementor editing panel. They are keyed to the
 * section CSS IDs (Advanced → CSS ID) and to the `dr-smoke` CSS class.
 */
( function () {
	'use strict';

	var editor = document.body.classList.contains( 'elementor-editor-active' ) ||
		/elementor-preview=/.test( window.location.search );

	var W = 'rgba(255,255,255,', P = 'rgba(240,139,234,';

	// Decorations per section ID. Positions are percentages of the section box.
	var DECO = {
		top: [
			{ t: 'dots', right: '8%', top: '14%', size: 160, a: .35 },
			{ t: 'flare', right: '30%', top: '22%', size: 90 },
			{ t: 'note', ch: '♫', right: '14%', bottom: '22%', size: 35, color: W + '.5)', glow: 1, delay: 1 }
		],
		welcome: [
			{ t: 'note', ch: '♪', left: '4%', top: '10%', size: 40, color: '#F08BEA', delay: 1.5, dur: 8 },
			{ t: 'dots', right: '4%', top: '8%', size: 130, a: .18, dur: 9 }
		],
		why: [
			{ t: 'note', ch: '♫', left: '6%', top: '14%', size: 35, color: W + '.7)', delay: 2.5, dur: 6 },
			{ t: 'note', ch: '♪', left: '24%', top: '34%', size: 23, color: W + '.55)', dur: 7 },
			{ t: 'note', ch: '𝄞', right: '8%', top: '10%', size: 42, color: W + '.75)', delay: .5, dur: 8 },
			{ t: 'note', ch: '♬', right: '22%', top: '30%', size: 26, color: W + '.6)', delay: 1, dur: 5 },
			{ t: 'flare', left: '36%', top: '9%', size: 80 },
			{ t: 'flare', right: '34%', top: '40%', size: 64, delay: 1.2 }
		],
		price: [
			{ t: 'dots', right: '-70px', bottom: '-70px', size: 260, a: .2, dur: 11 },
			{ t: 'flare', left: '46%', top: '12%', size: 64 }
		],
		how: [
			{ t: 'note', ch: '♫', left: '3%', top: '8%', size: 38, color: P + '.6)', delay: .5, dur: 6 },
			{ t: 'flare', right: '3%', top: '8%', size: 70, delay: .6 },
			{ t: 'dots', left: '-80px', bottom: '10%', size: 240, a: .18, dur: 7 }
		],
		events: [
			{ t: 'note', ch: '𝄞', left: '3%', top: '6%', size: 84, color: W + '.55)', glow: 1, delay: 2, dur: 5 },
			{ t: 'note', ch: '♫', left: '44%', top: '14%', size: 30, color: W + '.6)', delay: 2.5, dur: 6 },
			{ t: 'note', ch: '♪', left: '22%', bottom: '8%', size: 25, color: W + '.5)', dur: 7 },
			{ t: 'flare', right: '6%', top: '8%', size: 110 },
			{ t: 'flare', left: '60%', bottom: '40%', size: 60, delay: 1.4 },
			{ t: 'dots', right: '-40px', bottom: '-40px', size: 220, a: .45, dur: 7 },
			{ t: 'dots', left: '30%', top: '-50px', size: 120, a: .3, dur: 9 }
		],
		testimonials: [
			{ t: 'note', ch: '𝄞', right: '6%', top: '10%', size: 42, color: P + '.55)', delay: 2.5, dur: 8 },
			{ t: 'flare', left: '8%', top: '18%', size: 66, delay: .9 }
		],
		enquiry: [
			{ t: 'dots', left: '-60px', bottom: '-60px', size: 240, a: .22, dur: 11 },
			{ t: 'flare', left: '40%', top: '12%', size: 64 },
			{ t: 'note', ch: '♫', left: '6%', top: '16%', size: 35, color: P + '.6)', delay: .5, dur: 8 }
		]
	};

	function el( tag, cls ) {
		var n = document.createElement( tag );
		if ( cls ) { n.className = cls; }
		return n;
	}

	function place( node, d ) {
		[ 'left', 'right', 'top', 'bottom' ].forEach( function ( k ) {
			if ( d[ k ] !== undefined ) { node.style[ k ] = d[ k ]; }
		} );
	}

	function addDeco( section, items ) {
		if ( section.querySelector( ':scope > .dr-deco' ) ) { return; }
		var layer = el( 'div', 'dr-deco' );
		layer.setAttribute( 'aria-hidden', 'true' );

		items.forEach( function ( d ) {
			var n;
			if ( d.t === 'note' ) {
				n = el( 'span', 'dr-note' );
				n.textContent = d.ch;
				n.style.fontSize = d.size + 'px';
				n.style.color = d.color;
				if ( d.glow ) { n.style.textShadow = '0 0 16px rgba(255,255,255,.5)'; }
			} else if ( d.t === 'dots' ) {
				n = el( 'div', 'dr-dots' );
				n.style.width = n.style.height = d.size + 'px';
				n.style.setProperty( '--a', d.a );
			} else {
				n = el( 'span', 'dr-flare' );
				n.style.width = n.style.height = d.size + 'px';
				n.style.margin = ( -d.size * .35 ) + 'px';
			}
			if ( d.dur ) { n.style.animationDuration = d.dur + 's'; }
			if ( d.delay ) { n.style.animationDelay = d.delay + 's'; }
			place( n, d );
			layer.appendChild( n );
		} );

		section.insertBefore( layer, section.firstChild );
	}

	function addSmoke( section ) {
		if ( section.querySelector( ':scope > .dr-smoke-layer' ) ) { return; }
		var layer = el( 'div', 'dr-smoke-layer' );
		layer.setAttribute( 'aria-hidden', 'true' );
		[
			[ '-10%', '10%', '70%', '50%', 34, 0 ],
			[ '40%', '40%', '80%', '60%', 42, -8 ],
			[ '10%', '60%', '60%', '45%', 38, -16 ],
			[ '60%', '-10%', '55%', '45%', 30, -4 ]
		].forEach( function ( s ) {
			var i = el( 'i' );
			i.style.left = s[ 0 ]; i.style.top = s[ 1 ]; i.style.width = s[ 2 ]; i.style.height = s[ 3 ];
			i.style.animationDuration = s[ 4 ] + 's'; i.style.animationDelay = s[ 5 ] + 's';
			layer.appendChild( i );
		} );
		section.insertBefore( layer, section.firstChild );
	}

	function addEq() {
		document.querySelectorAll( '.dr-badge .elementor-heading-title' ).forEach( function ( t ) {
			if ( t.querySelector( '.dr-eq' ) ) { return; }
			var eq = el( 'span', 'dr-eq' );
			eq.setAttribute( 'aria-hidden', 'true' );
			for ( var i = 0; i < 4; i++ ) { eq.appendChild( el( 'i' ) ); }
			t.insertBefore( eq, t.firstChild );
		} );
	}

	// Testimonials: duplicate the cards so the strip loops seamlessly.
	function marquee() {
		if ( editor ) { return; }
		document.querySelectorAll( '.dr-marquee' ).forEach( function ( box ) {
			var host = box.querySelector( ':scope > .e-con-inner' ) || box;
			if ( host.querySelector( ':scope > .dr-marquee__track' ) ) { return; }
			var cards = Array.prototype.slice.call( host.children ).filter( function ( c ) {
				return c.classList.contains( 'elementor-element' );
			} );
			if ( ! cards.length ) { return; }

			var gap = parseFloat( getComputedStyle( host ).columnGap ) || 24;
			var track = el( 'div', 'dr-marquee__track' );
			track.style.gap = gap + 'px';
			track.style.setProperty( '--dr-marquee-gap', gap + 'px' );
			track.style.setProperty( '--dr-marquee-speed', Math.max( 30, cards.length * 9 ) + 's' );

			cards.forEach( function ( c ) { track.appendChild( c ); } );
			cards.forEach( function ( c ) {
				var clone = c.cloneNode( true );
				clone.setAttribute( 'aria-hidden', 'true' );
				clone.removeAttribute( 'id' );
				track.appendChild( clone );
			} );
			host.appendChild( track );
		} );
	}

	// Underline the menu item for the section in view (home page anchors).
	function scrollspy() {
		document.querySelectorAll( '.dr-menu--primary, .dr-nav--hfe .hfe-nav-menu' ).forEach( function ( nav ) {
			if ( nav.dataset.drSpy ) { return; }
			var here = window.location.origin + window.location.pathname;
			var links = [], home = null;
			nav.querySelectorAll( 'a[href]' ).forEach( function ( a ) {
				var u = new URL( a.href, window.location.href );
				if ( u.origin + u.pathname !== here ) { return; }
				if ( u.hash && document.getElementById( u.hash.slice( 1 ) ) ) {
					links.push( { a: a, el: document.getElementById( u.hash.slice( 1 ) ) } );
				} else if ( ! u.hash ) {
					home = a;
				}
			} );
			if ( ! links.length ) { return; }
			nav.dataset.drSpy = '1';
			nav.classList.add( 'dr-spy' );

			var ticking = false;
			function update() {
				ticking = false;
				var line = window.innerHeight * .35, current = null;
				links.forEach( function ( l ) {
					if ( l.el.getBoundingClientRect().top <= line ) { current = l; }
				} );
				links.forEach( function ( l ) { l.a.classList.toggle( 'is-active', l === current ); } );
				if ( home ) { home.classList.toggle( 'is-active', ! current ); }
			}
			window.addEventListener( 'scroll', function () {
				if ( ! ticking ) { ticking = true; window.requestAnimationFrame( update ); }
			}, { passive: true } );
			window.addEventListener( 'resize', update );
			update();
		} );
	}

	// Mobile menu toggle in the header template.
	function burger() {
		document.querySelectorAll( '.dr-burger button' ).forEach( function ( btn ) {
			if ( btn.dataset.drBound ) { return; }
			btn.dataset.drBound = '1';
			var header = btn.closest( '.dr-site-header' ) || document.body;
			btn.addEventListener( 'click', function () {
				var open = header.classList.toggle( 'dr-nav-open' );
				btn.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
			} );
			header.addEventListener( 'click', function ( e ) {
				if ( e.target.closest( '.dr-menu a' ) ) {
					header.classList.remove( 'dr-nav-open' );
					btn.setAttribute( 'aria-expanded', 'false' );
				}
			} );
		} );
	}

	// "Feel the rhythm" gallery: an Elementor Basic Gallery (class dr-gallery-show) becomes the
	// 5-tile grid, cycling through the images 5 at a time with music-note indicators.
	var NOTES = [ '♪', '♫', '♬', '♩' ];

	function bestSrc( img ) {
		var set = img.getAttribute( 'srcset' ), best = img.getAttribute( 'src' ), w = 0;
		if ( set ) {
			set.split( ',' ).forEach( function ( part ) {
				var bits = part.trim().split( /\s+/ ), n = parseInt( bits[ 1 ], 10 ) || 0;
				if ( n > w ) { w = n; best = bits[ 0 ]; }
			} );
		}
		return best;
	}

	function gallerySlides() {
		document.querySelectorAll( '.dr-gallery-show' ).forEach( function ( widget ) {
			if ( widget.querySelector( '.dr-gallery--live' ) ) { return; }
			var imgs = Array.prototype.slice.call( widget.querySelectorAll( '.elementor-image-gallery img, .gallery img' ) );
			if ( ! imgs.length ) { return; }

			var pics = imgs.map( function ( i ) { return { src: bestSrc( i ), alt: i.getAttribute( 'alt' ) || '' }; } );
			var per = 5, sets = Math.max( 1, Math.ceil( pics.length / per ) );
			var reduced = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

			var grid = el( 'div', 'dr-gallery dr-gallery--live' );
			var tiles = [];
			for ( var t = 0; t < per; t++ ) {
				var tile = el( 'div', 'dr-gtile dr-g' + ( t + 1 ) + ( t === 1 ? '' : ' dr-tint' ) );
				for ( var s = 0; s < sets; s++ ) {
					var p = pics[ ( s * per + t ) % pics.length ];
					var im = el( 'img', 'dr-gtile__img' + ( s === 0 ? ' is-on' : '' ) );
					im.alt = s === 0 ? p.alt : ''; im.decoding = 'async';
					if ( s === 0 ) { im.src = p.src; im.loading = 'lazy'; } else { im.dataset.src = p.src; im.setAttribute( 'aria-hidden', 'true' ); }
					tile.appendChild( im );
				}
				grid.appendChild( tile );
				tiles.push( tile );
			}

			var dots = el( 'div', 'dr-gnotes' );
			dots.setAttribute( 'role', 'group' );
			dots.setAttribute( 'aria-label', 'Gallery sets' );
			var buttons = [];
			for ( var d = 0; d < sets; d++ ) {
				var b = el( 'button', 'dr-gnote' + ( d === 0 ? ' is-on' : '' ) );
				b.type = 'button';
				b.textContent = NOTES[ d % NOTES.length ];
				b.setAttribute( 'aria-label', 'Show photo set ' + ( d + 1 ) + ' of ' + sets );
				b.setAttribute( 'aria-pressed', d === 0 ? 'true' : 'false' );
				b.dataset.set = d;
				dots.appendChild( b );
				buttons.push( b );
			}
			if ( sets < 2 ) { dots.hidden = true; }

			widget.appendChild( grid );
			widget.appendChild( dots );
			widget.classList.add( 'is-built' );

			var current = 0, timer = null, paused = false, visible = false;

			// Lazy-load a photo set only when it is about to be shown.
			function loadSet( n ) {
				tiles.forEach( function ( tile ) {
					var img = tile.querySelectorAll( '.dr-gtile__img' )[ n ];
					if ( img && img.dataset.src ) { img.src = img.dataset.src; delete img.dataset.src; }
				} );
			}

			function show( n ) {
				if ( n === current ) { return; }
				loadSet( n );
				if ( sets > 1 ) { window.setTimeout( function () { loadSet( ( n + 1 ) % sets ); }, 2500 ); }
				var prev = current;
				current = n;
				tiles.forEach( function ( tile, i ) {
					var layers = tile.querySelectorAll( '.dr-gtile__img' );
					window.setTimeout( function () {
						if ( current !== n ) { return; }
						layers.forEach( function ( l, k ) {
							if ( k !== n ) { l.classList.remove( 'is-on' ); l.setAttribute( 'aria-hidden', 'true' ); }
						} );
						layers[ n ].classList.add( 'is-on' );
						layers[ n ].removeAttribute( 'aria-hidden' );
						layers[ n ].alt = pics[ ( n * per + i ) % pics.length ].alt;
						tile.classList.remove( 'is-beat' );
						void tile.offsetWidth;
						tile.classList.add( 'is-beat' );
					}, reduced ? 0 : i * 120 );
				} );
				buttons.forEach( function ( b, i ) {
					b.classList.toggle( 'is-on', i === n );
					b.setAttribute( 'aria-pressed', i === n ? 'true' : 'false' );
				} );
				return prev;
			}

			function schedule() {
				window.clearInterval( timer );
				if ( reduced || editor || sets < 2 ) { return; }
				timer = window.setInterval( function () {
					if ( ! paused && visible && ! document.hidden ) { show( ( current + 1 ) % sets ); }
				}, 5000 );
			}

			dots.addEventListener( 'click', function ( e ) {
				var b = e.target.closest( '.dr-gnote' );
				if ( ! b ) { return; }
				show( parseInt( b.dataset.set, 10 ) );
				schedule();
			} );
			[ grid, dots ].forEach( function ( zone ) {
				zone.addEventListener( 'mouseenter', function () { paused = true; } );
				zone.addEventListener( 'mouseleave', function () { paused = false; } );
				zone.addEventListener( 'focusin', function () { paused = true; } );
				zone.addEventListener( 'focusout', function () { paused = false; } );
			} );
			if ( 'IntersectionObserver' in window ) {
				new IntersectionObserver( function ( entries ) {
					visible = entries[ 0 ].isIntersecting;
					if ( visible && sets > 1 ) { loadSet( ( current + 1 ) % sets ); }
				}, { threshold: .2 } ).observe( grid );
			} else {
				visible = true;
			}
			schedule();
		} );
	}

	// Pause decorative animations for sections outside the viewport.
	function pauseOffscreen() {
		if ( ! ( 'IntersectionObserver' in window ) ) { return; }
		var io = pauseOffscreen.io || ( pauseOffscreen.io = new IntersectionObserver( function ( entries ) {
			entries.forEach( function ( e ) { e.target.classList.toggle( 'dr-offscreen', ! e.isIntersecting ); } );
		}, { rootMargin: '150px 0px' } ) );
		document.querySelectorAll( '.dr-sec' ).forEach( function ( s ) {
			if ( s.dataset.drObserved ) { return; }
			s.dataset.drObserved = '1';
			io.observe( s );
		} );
	}

	function init() {
		Object.keys( DECO ).forEach( function ( id ) {
			var s = document.getElementById( id );
			if ( s && s.classList.contains( 'e-con' ) ) { addDeco( s, DECO[ id ] ); }
		} );
		document.querySelectorAll( '.dr-smoke' ).forEach( addSmoke );
		addEq();
		marquee();
		burger();
		scrollspy();
		gallerySlides();
		pauseOffscreen();
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}

	// Re-run when Elementor (re)renders elements in the editor preview.
	window.addEventListener( 'elementor/frontend/init', function () {
		if ( window.elementorFrontend && window.elementorFrontend.hooks ) {
			window.elementorFrontend.hooks.addAction( 'frontend/element_ready/global', function () {
				window.clearTimeout( init._t );
				init._t = window.setTimeout( init, 60 );
			} );
		}
	} );
}() );
