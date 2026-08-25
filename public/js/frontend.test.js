const popupMarkup = ( id ) => `
	<div class="darven-epi-installments-price-statement">
		<button type="button" class="darven-epi-installments-toggle" aria-expanded="false" aria-controls="${ id }-dialog">View installments</button>
		<div id="${ id }-dialog" class="messagepop pop darven-epi-installments-popup" hidden aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="${ id }-title">
			<div class="darven-epi-installments-backdrop"></div>
			<div class="darven-epi-installments-dialog" role="document">
				<h2 id="${ id }-title" class="screen-reader-text darven-epi-installments-title">Installment options</h2>
				<button type="button" class="darven-epi-installments-close" aria-label="Close installment options">Close</button>
				<a href="#details">Details</a>
			</div>
		</div>
	</div>`;

const click = ( element ) =>
	element.dispatchEvent(
		new window.MouseEvent( 'click', { bubbles: true } )
	);

const keydown = ( element, key, shiftKey = false ) =>
	element.dispatchEvent(
		new window.KeyboardEvent( 'keydown', {
			bubbles: true,
			key,
			shiftKey,
		} )
	);

const loadFrontend = () => {
	jest.resetModules();
	require( './frontend' );
};

describe( 'installment popup', () => {
	let registeredEvents;

	beforeAll( () => {
		const listenerSpy = jest.spyOn( document, 'addEventListener' );

		delete document.__darvenEpiInstallmentsModalBound;
		delete global.jQuery;
		loadFrontend();
		loadFrontend();
		registeredEvents = listenerSpy.mock.calls.map( ( [ event ] ) => event );
		listenerSpy.mockRestore();
	} );

	beforeEach( () => {
		keydown( document, 'Escape' );
		document.body.innerHTML =
			popupMarkup( 'first' ) + popupMarkup( 'second' );
	} );

	it( 'uses one delegated listener per event even when the script is evaluated twice', () => {
		expect(
			registeredEvents.filter( ( event ) => event === 'click' )
		).toHaveLength( 1 );
		expect(
			registeredEvents.filter( ( event ) => event === 'keydown' )
		).toHaveLength( 1 );
	} );

	it.each( [ 'Enter', ' ' ] )( 'opens from the %s key', ( key ) => {
		const trigger = document.querySelector(
			'.darven-epi-installments-toggle'
		);
		const modal = document.getElementById(
			trigger.getAttribute( 'aria-controls' )
		);

		keydown( trigger, key );

		expect( modal.hidden ).toBe( false );
		expect( trigger.getAttribute( 'aria-expanded' ) ).toBe( 'true' );
		expect( document.activeElement ).toBe(
			modal.querySelector( '.darven-epi-installments-close' )
		);
	} );

	it( 'opens on click and keeps only one dialog open', () => {
		const triggers = document.querySelectorAll(
			'.darven-epi-installments-toggle'
		);
		const firstModal = document.getElementById(
			triggers[ 0 ].getAttribute( 'aria-controls' )
		);
		const secondModal = document.getElementById(
			triggers[ 1 ].getAttribute( 'aria-controls' )
		);

		click( triggers[ 0 ] );
		click( triggers[ 1 ] );

		expect( firstModal.hidden ).toBe( true );
		expect( triggers[ 0 ].getAttribute( 'aria-expanded' ) ).toBe( 'false' );
		expect( secondModal.hidden ).toBe( false );
		expect( triggers[ 1 ].getAttribute( 'aria-expanded' ) ).toBe( 'true' );
	} );

	it( 'opens when Element.closest is unavailable', () => {
		const nativeClosest = Element.prototype.closest;
		Element.prototype.closest = undefined;
		const trigger = document.querySelector(
			'.darven-epi-installments-toggle'
		);
		const modal = document.getElementById(
			trigger.getAttribute( 'aria-controls' )
		);

		try {
			click( trigger );
			expect( modal.hidden ).toBe( false );
		} finally {
			Element.prototype.closest = nativeClosest;
		}
	} );

	it( 'resolves duplicate dialog ids inside the clicked statement', () => {
		document.body.innerHTML =
			popupMarkup( 'shared' ) + popupMarkup( 'shared' );
		const triggers = document.querySelectorAll(
			'.darven-epi-installments-toggle'
		);
		const modals = document.querySelectorAll(
			'.darven-epi-installments-popup'
		);
		const closeButtons = document.querySelectorAll(
			'.darven-epi-installments-close'
		);

		click( triggers[ 1 ] );

		expect( modals[ 0 ].hidden ).toBe( true );
		expect( modals[ 1 ].hidden ).toBe( false );
		expect( document.activeElement ).not.toBe( closeButtons[ 0 ] );
		expect( document.activeElement ).toBe( closeButtons[ 1 ] );
	} );

	it.each( [ 'close button', 'backdrop', 'Escape' ] )(
		'closes with %s and returns focus to the trigger',
		( closeMethod ) => {
			const trigger = document.querySelector(
				'.darven-epi-installments-toggle'
			);
			const modal = document.getElementById(
				trigger.getAttribute( 'aria-controls' )
			);

			click( trigger );
			if ( closeMethod === 'close button' ) {
				click(
					modal.querySelector( '.darven-epi-installments-close' )
				);
			} else if ( closeMethod === 'backdrop' ) {
				click(
					modal.querySelector( '.darven-epi-installments-backdrop' )
				);
			} else {
				keydown( modal, 'Escape' );
			}

			expect( modal.hidden ).toBe( true );
			expect( modal.getAttribute( 'aria-hidden' ) ).toBe( 'true' );
			expect( trigger.getAttribute( 'aria-expanded' ) ).toBe( 'false' );
			expect( document.activeElement ).toBe( trigger );
		}
	);

	it( 'traps focus inside the open dialog', () => {
		const trigger = document.querySelector(
			'.darven-epi-installments-toggle'
		);
		const modal = document.getElementById(
			trigger.getAttribute( 'aria-controls' )
		);
		const closeButton = modal.querySelector(
			'.darven-epi-installments-close'
		);
		const lastLink = modal.querySelector( 'a' );

		click( trigger );
		keydown( closeButton, 'Tab', true );
		expect( document.activeElement ).toBe( lastLink );

		keydown( lastLink, 'Tab' );
		expect( document.activeElement ).toBe( closeButton );
	} );
} );
