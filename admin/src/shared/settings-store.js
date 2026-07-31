export const createSettingsState = () => ( {
	document: null,
	loadError: '',
	isSaving: false,
	notice: null,
	activeTab: 'general',
} );

export const updateSettingsField = ( document, section, field, value ) => ( {
	...document,
	[ section ]: {
		...document[ section ],
		[ field ]: value,
	},
} );
