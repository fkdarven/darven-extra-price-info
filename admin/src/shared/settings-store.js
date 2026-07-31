import { useCallback, useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import { normalizeRestError } from './api';

export const useSettingsStore = ( apiClient ) => {
	const [ document, setDocument ] = useState( null );
	const [ loadError, setLoadError ] = useState( '' );
	const [ notice, setNotice ] = useState( null );
	const [ isSaving, setIsSaving ] = useState( false );

	useEffect( () => {
		let active = true;

		apiClient
			.loadSettings()
			.then( ( settings ) => {
				if ( active ) {
					setDocument( settings );
				}
			} )
			.catch( ( error ) => {
				if ( active ) {
					setLoadError(
						normalizeRestError(
							error,
							__(
								'The settings could not be loaded.',
								'darven-epi'
							)
						)
					);
				}
			} );

		return () => {
			active = false;
		};
	}, [ apiClient ] );

	const updateField = useCallback( ( section, field, value ) => {
		setDocument( ( current ) => ( {
			...current,
			[ section ]: {
				...current[ section ],
				[ field ]: value,
			},
		} ) );
		setNotice( null );
	}, [] );

	const save = useCallback( async () => {
		setIsSaving( true );
		setNotice( null );

		try {
			const saved = await apiClient.saveSettings( document );
			setDocument( saved );
			setNotice( {
				status: 'success',
				message: __( 'Settings saved.', 'darven-epi' ),
			} );
		} catch ( error ) {
			setNotice( {
				status: 'error',
				message: normalizeRestError(
					error,
					__( 'The settings could not be saved.', 'darven-epi' )
				),
			} );
		} finally {
			setIsSaving( false );
		}
	}, [ apiClient, document ] );

	return {
		document,
		isLoading: null === document && ! loadError,
		loadError,
		isSaving,
		notice,
		updateField,
		save,
	};
};
