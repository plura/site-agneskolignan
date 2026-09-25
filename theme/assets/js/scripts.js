// Everything here is one module: an unguarded querySelector that returns null throws at
// evaluation and takes every later block down with it. Each block checks what it needs.

const
	header = document.querySelector('header'),
	postType = typeof plura_wp_data !== 'undefined' ? plura_wp_data?.type ?? '' : '';


//Scroll
const headerGroup = header?.querySelector(':scope > .wp-block-group');

if( headerGroup ) {

	const
		headerH = headerGroup.offsetHeight,
		scrollEventHandler = () => {
			if( window.scrollY > headerH ) {
				header.classList.add('ak-sticky');
			} else {
				header.classList.remove('ak-sticky');
			}
		};

	document.addEventListener("scroll", event => scrollEventHandler() );
	scrollEventHandler();

}


//menu
const
	logo = header?.querySelector('.wp-block-site-logo'),
	nav = header?.querySelector('nav');

if( logo && nav ) {

	const
		nav_logo_holder = logo.parentNode,
		m_observer = new ResizeObserver( entries => {
			if( window.innerWidth >= 782 ) {
				nav_logo_holder.append( logo );
			} else {
				nav.parentNode.prepend( logo );
			}
		});

	nav_logo_holder.classList.add('ak-nav-logo-holder');
	m_observer.observe( document.body );

}


if( postType.match(/ak_(exhibition|object)/) ) {

	const
		gallery = document.querySelector(".ak-gallery"),
		gallery_resize_observer = new ResizeObserver( entries => {
			gallery.querySelectorAll('img').forEach( img => {
				const h = img.offsetHeight, w = img.offsetWidth;
				if( h && w ) {
					Object.entries({h: h, w: w})
					.forEach( ([key, value]) => img.parentNode.style.setProperty(`--${key}`, `${value}px`) );
				}
			});
		});

	if( gallery ) {

		new Carousel( gallery, {
			adaptiveHeight: true,
			Dots: false
		}, { Thumbs });

		// Bound to the images rather than a data-fancybox attribute: Fancybox resolves
		// href || currentSrc || src, and plura_wp_image() always emits src.
		Fancybox.bind('.ak-gallery .plura-wp-gallery-item img', {
			Images: {
				Panzoom: {},
			},
		});

		gallery_resize_observer.observe( gallery );

	}

}


// plura_wp_data.type is 'ak_object' on the object taxonomy archives too, not only on a
// single object; plura 0.12.0 added the archive flag, which says so directly rather than
// inferring it from the absence of .ak-object-info-holder.
const objectInfo = document.querySelector('.ak-object-info-holder');

if( postType === 'ak_object' && ! plura_wp_data?.archive && objectInfo ) {

	let toggle_status;

	const
		colgroup = objectInfo.parentNode,
		col = objectInfo,
		trigger = document.createElement('div'),
		refresh = status => {
			toggle_status = status;
			colgroup.setAttribute('data-info', toggle_status ? 1 : 0);
		},
		observer = new ResizeObserver( entries => {
			colgroup.style.setProperty('--colw', `${ col.offsetWidth }px`);
			if( window.innerWidth >= 991 ) {
				colgroup.classList.add('ak-info-toggle-enabled');
			} else {
				colgroup.classList.remove('ak-info-toggle-enabled');
			}
		});

	[ ...colgroup.children ].forEach( (el, n) => el.classList.add( ...['ak-object-core-col', `ak-object-core-col${ n + 1 }`] ));
	colgroup.classList.add('ak-object-core');
	trigger.classList.add( ...['ak-object-info-toggle-trigger', 'ak-icon'] );
	colgroup.appendChild( trigger ).addEventListener('click', event => refresh( !toggle_status ) );

	observer.observe( document.body );

}
