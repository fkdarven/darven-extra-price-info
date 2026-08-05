export const createSettingsState = () => ( {
	document: null,
	loadError: '',
	isSaving: false,
	notice: null,
	activeTab: 'pricing',
} );

export const updateSettingsField = ( document, section, field, value ) => ( {
	...document,
	[ section ]: {
		...document[ section ],
		[ field ]: value,
	},
} );
