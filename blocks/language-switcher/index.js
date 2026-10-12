( function ( wp ) {
	var el = wp.element.createElement;
	var __ = wp.i18n.__;
	var blockEditor = wp.blockEditor;
	var NAME = 'kybernaut/language-switcher';

	// Allow the switcher inside the Navigation block, where it renders as menu items.
	wp.hooks.addFilter( 'blocks.registerBlockType', 'kybernaut-multilingual/navigation', function ( settings, name ) {
		if ( 'core/navigation' === name && Array.isArray( settings.allowedBlocks ) && settings.allowedBlocks.indexOf( NAME ) === -1 ) {
			settings.allowedBlocks = settings.allowedBlocks.concat( NAME );
		}
		return settings;
	} );

	wp.blocks.registerBlockType( NAME, {
		edit: function ( props ) {
			return el(
				wp.element.Fragment,
				null,
				el(
					blockEditor.InspectorControls,
					null,
					el(
						wp.components.PanelBody,
						{ title: __( 'Settings', 'kybernaut-multilingual' ) },
						el( wp.components.ToggleControl, {
							__nextHasNoMarginBottom: true,
							label: __( 'Show current language', 'kybernaut-multilingual' ),
							checked: props.attributes.showCurrent,
							onChange: function ( value ) {
								props.setAttributes( { showCurrent: value } );
							},
						} )
					)
				),
				el( 'div', blockEditor.useBlockProps(), el( 'em', null, wp.blocks.getBlockType( NAME ).title ) )
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp );
