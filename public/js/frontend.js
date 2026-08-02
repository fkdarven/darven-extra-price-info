( function () {
	'use strict';

	const toggleSelector = '.darven-epi-installments-toggle';
	const popupSelector = '.darven-epi-installments-popup';
	const closeSelector = '.darven-epi-installments-close';
	const backdropSelector = '.darven-epi-installments-backdrop';
	let activeModal = null;
	let activeTrigger = null;

	if ( document.__darvenEpiInstallmentsModalBound ) {
		return;
	}

	document.__darvenEpiInstallmentsModalBound = true;

	function getModal( trigger ) {
		const modalId = trigger.getAttribute( 'aria-controls' );
		const statement = trigger.closest(
			'.darven-epi-installments-price-statement'
		);

		return Array.prototype.find.call(
			statement ? statement.querySelectorAll( popupSelector ) : [],
			( candidate ) => candidate.id === modalId
		);
	}

	function getFocusableElements( modal ) {
		const selector = [
			'a[href]',
			'button:not([disabled])',
			'input:not([disabled])',
			'select:not([disabled])',
			'textarea:not([disabled])',
			'[tabindex]:not([tabindex="-1"])',
		].join( ',' );

		return Array.prototype.filter.call(
			modal.querySelectorAll( selector ),
			function ( element ) {
				return (
					! element.hidden &&
					'true' !== element.getAttribute( 'aria-hidden' )
				);
			}
		);
	}

	function closeActiveModal( returnFocus ) {
		const trigger = activeTrigger;

		if ( ! activeModal || ! trigger ) {
			return;
		}

		activeModal.hidden = true;
		activeModal.setAttribute( 'aria-hidden', 'true' );
		trigger.setAttribute( 'aria-expanded', 'false' );
		trigger.classList.remove( 'selected' );
		activeModal = null;
		activeTrigger = null;

		if ( returnFocus && document.contains( trigger ) ) {
			trigger.focus();
		}
	}

	function openModal( trigger ) {
		const modal = getModal( trigger );

		if ( ! modal ) {
			return;
		}

		const closeButton = modal.querySelector( closeSelector );

		if ( activeModal && activeModal !== modal ) {
			closeActiveModal( false );
		}

		activeModal = modal;
		activeTrigger = trigger;
		modal.hidden = false;
		modal.setAttribute( 'aria-hidden', 'false' );
		trigger.setAttribute( 'aria-expanded', 'true' );
		trigger.classList.add( 'selected' );

		if ( closeButton ) {
			closeButton.focus();
		}
	}

	function toggleModal( trigger ) {
		if ( 'true' === trigger.getAttribute( 'aria-expanded' ) ) {
			closeActiveModal( true );
			return;
		}

		openModal( trigger );
	}

	function trapFocus( event ) {
		const focusable = getFocusableElements( activeModal );

		if ( 0 === focusable.length ) {
			event.preventDefault();
			return;
		}

		const first = focusable[ 0 ];
		const last = focusable[ focusable.length - 1 ];
		const activeElement = activeModal.ownerDocument.activeElement;

		if (
			event.shiftKey &&
			( activeElement === first ||
				! activeModal.contains( activeElement ) )
		) {
			event.preventDefault();
			last.focus();
		} else if (
			! event.shiftKey &&
			( activeElement === last ||
				! activeModal.contains( activeElement ) )
		) {
			event.preventDefault();
			first.focus();
		}
	}

	document.addEventListener( 'click', function ( event ) {
		const target = event.target;

		if ( ! target || ! target.closest ) {
			return;
		}

		const toggle = target.closest( toggleSelector );
		if ( toggle ) {
			toggleModal( toggle );
			return;
		}

		if ( activeModal && target.closest( closeSelector ) ) {
			closeActiveModal( true );
			return;
		}

		if ( activeModal && target.matches( backdropSelector ) ) {
			closeActiveModal( true );
		}
	} );

	document.addEventListener( 'keydown', function ( event ) {
		const target = event.target;
		const toggle =
			target && target.closest ? target.closest( toggleSelector ) : null;

		if (
			toggle &&
			( 'Enter' === event.key ||
				' ' === event.key ||
				'Spacebar' === event.key )
		) {
			event.preventDefault();
			toggleModal( toggle );
			return;
		}

		if ( ! activeModal ) {
			return;
		}

		if ( 'Escape' === event.key ) {
			event.preventDefault();
			closeActiveModal( true );
		} else if ( 'Tab' === event.key ) {
			trapFocus( event );
		}
	} );
} )();
