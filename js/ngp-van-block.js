/* globals ngpVanBlock */
(function(  ){
	wp.blocks.registerBlockType( 'ngp-van/map', {
		title: ngpVanBlock.strings.label,
		icon: 'location-alt',
		category: 'common',

		attributes: {
			events: '',
		},

		edit: function( props ) {
			console.log( props );
			return wp.element.createElement( 'div', {}, 'this is a div' );
		},

		save: function( props ) {
			console.log( props );
			return null;
		}
	} );
})(  );
