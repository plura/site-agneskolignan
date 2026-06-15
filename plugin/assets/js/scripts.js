document.addEventListener('DOMContentLoaded', () => {


	//menu logo mobile

console.log(plura_wp_data);


	//Scroll
	//const ob
	const 

		header = document.querySelector('header'),

		headerH = header.querySelector(':scope > .wp-block-group').offsetHeight,

		scrollEventHandler = () => {

			if( window.scrollY > headerH ) {

				header.classList.add('ak-sticky')

			} else {

				header.classList.remove('ak-sticky');

			}

		};

	document.addEventListener("scroll", event => scrollEventHandler() );
	
	scrollEventHandler();



	//menu
	const 

		logo = header.querySelector('.wp-block-site-logo'),

		nav = header.querySelector('nav'),

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



	//Objects Grid
	//use title link to link entire grid item
	document.querySelectorAll(':is(.plura-wp-posts, .plura-wp-terms) :is(.plura-wp-post, .plura-wp-term).full').forEach( element => {

		element.addEventListener('click', event => {

			window.location.href = element.querySelector(':is(.plura-wp-post-title-link, .plura-wp-term-title-link)').href;

		});

	});


	if( plura_wp_data.type.match(/ak_(exhibition|object)/) ) {

		console.log('ewwer');

		const 

			gallery = document.querySelector(".ak-gallery"),

			gallery_resize_observer = new ResizeObserver( entries => {

				gallery.querySelectorAll('img').forEach( img => {

					const h = img.offsetHeight, w = img.offsetWidth;

					if( h && w ) {

						Object.entries({h: h, w: w})

						.forEach( ([key, value]) => img.parentNode.style.setProperty(`--${key}`, `${value}px`) ) ;

					}

				});

			});


		console.log(gallery);

		if( gallery ) {

			new Carousel( gallery, {

				adaptiveHeight: true,

			  // Your custom options
			  Dots: false
			}, { Thumbs });


			Fancybox.bind('[data-fancybox="ak-gallery"]', {
			  // Custom options for all galleries
			  Images: {
    			Panzoom: {
      					//panMode: "mousemove",
      					//mouseMoveFactor: 1.1,
      					//mouseMoveFriction: 0.12,
    				},
  				},
			});

			gallery_resize_observer.observe( gallery );
			
		}


	}






	//Object Gallery
	if( plura_wp_data.type === 'ak_object' ) {
	

		//info
		let toggle_status;

		const 

			colgroup = document.querySelector('.ak-object-info-holder').parentNode,

			col = colgroup.querySelector('.ak-object-info-holder'),

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


});