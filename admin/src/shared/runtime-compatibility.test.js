import fs from 'fs';
import path from 'path';

const projectRoot = path.resolve( __dirname, '../../..' );
const activeSources = [
	'admin/src/shared/settings-store.js',
	'admin/src/settings/app.js',
	'admin/src/settings/index.js',
	'admin/src/settings/sections/pricing-section.js',
	'admin/src/settings/sections/display-section.js',
	'admin/src/settings/sections/positions-section.js',
	'admin/src/settings/sections/compatibility-section.js',
	'admin/src/product-options/app.js',
	'admin/src/product-options/index.js',
];

describe( 'WordPress 5.0 admin runtime contract', () => {
	it.each( activeSources )(
		'%s uses neither Hooks nor wordpress/components',
		( relativePath ) => {
			const source = fs.readFileSync(
				path.join( projectRoot, relativePath ),
				'utf8'
			);

			expect( source ).not.toMatch(
				/\buse(?:State|Effect|Callback|Memo|Reducer|Ref)\b/
			);
			expect( source ).not.toContain( '@wordpress/components' );
		}
	);

	it.each( [ 'settings', 'product-options' ] )(
		'the %s bundle uses only the basic WordPress element runtime',
		( bundle ) => {
			const built = fs.readFileSync(
				path.join( projectRoot, `build/${ bundle }/index.js` ),
				'utf8'
			);

			expect( built ).not.toMatch(
				/\.use(?:State|Effect|Callback|Memo|Reducer|Ref)\b/
			);
			expect( built ).not.toMatch(
				/wp\.components|window\.wp\.components/
			);
		}
	);
} );
