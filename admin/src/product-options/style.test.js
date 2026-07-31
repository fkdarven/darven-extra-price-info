import fs from 'fs';
import path from 'path';

const styleArtifacts = [
	{
		name: 'SCSS source',
		path: path.join( __dirname, 'style.scss' ),
		scopeSelector: /\.darven-precos-parcelados-product-options\s*\{/,
		labelRule: /&__field\s*\{[\s\S]*?label\s*\{([\s\S]*?)\n\t\t\}/,
	},
	{
		name: 'distributed LTR stylesheet',
		path: path.join(
			__dirname,
			'../../../build/product-options/style-index.css'
		),
		scopeSelector:
			/\.darven-precos-parcelados-product-options__field\s+label\s*\{/,
		labelRule:
			/\.darven-precos-parcelados-product-options__field\s+label\s*\{([^}]*)\}/,
	},
	{
		name: 'distributed RTL stylesheet',
		path: path.join(
			__dirname,
			'../../../build/product-options/style-index-rtl.css'
		),
		scopeSelector:
			/\.darven-precos-parcelados-product-options__field\s+label\s*\{/,
		labelRule:
			/\.darven-precos-parcelados-product-options__field\s+label\s*\{([^}]*)\}/,
	},
];

const inheritedWooCommerceProperties = [
	/float\s*:\s*none(?:\s*;|\s*$)/,
	/margin\s*:\s*0(?:\s*;|\s*$)/,
	/width\s*:\s*auto(?:\s*;|\s*$)/,
	/box-sizing\s*:\s*border-box(?:\s*;|\s*$)/,
];

describe( 'product options styles', () => {
	it.each( styleArtifacts )(
		'keeps toggle labels inside the component in the $name',
		( artifact ) => {
			const stylesheet = fs.readFileSync( artifact.path, 'utf8' );
			const labelRule = stylesheet.match( artifact.labelRule );

			expect( stylesheet ).toMatch( artifact.scopeSelector );
			expect( labelRule ).not.toBeNull();
			inheritedWooCommerceProperties.forEach( ( property ) => {
				expect( labelRule[ 1 ] ).toMatch( property );
			} );
		}
	);
} );
