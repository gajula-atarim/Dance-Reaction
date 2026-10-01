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

	function init() {
		Object.keys( DECO ).forEach( function ( id ) {
			var s = document.getElementById( id );
			if ( s && s.classList.contains( 'e-con' ) ) { addDeco( s, DECO[ id ] ); }
		} );
		document.querySelectorAll( '.dr-smoke' ).forEach( addSmoke );
		addEq();
		marquee();
		burger();
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
