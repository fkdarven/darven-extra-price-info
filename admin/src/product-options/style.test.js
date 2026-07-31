import fs from 'fs';
import path from 'path';

const stylesheet = fs.readFileSync(
	path.join( __dirname, 'style.scss' ),
	'utf8'
);

describe( 'product options styles', () => {
	it( 'keeps toggle labels inside the product options component', () => {
		const labelRule = stylesheet.match( /label\s*\{([\s\S]*?)\n\t\t\}/ );

		expect( labelRule ).not.toBeNull();
		expect( labelRule[ 1 ] ).toContain( 'float: none;' );
		expect( labelRule[ 1 ] ).toContain( 'margin: 0;' );
		expect( labelRule[ 1 ] ).toContain( 'width: auto;' );
		expect( labelRule[ 1 ] ).toContain( 'box-sizing: border-box;' );
	} );
} );
