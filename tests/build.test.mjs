import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { readFileSync } from 'node:fs';
import { test } from 'node:test';
import { fileURLToPath } from 'node:url';

test( 'build produces synchronized metadata, rendering, styles and executable editor script', () => {
	const root = fileURLToPath( new URL( '..', import.meta.url ) );
	execFileSync( process.execPath, [ 'tools/build.mjs' ], { cwd: root } );
	for ( const file of [ 'block.json', 'render.php', 'style.css' ] ) {
		assert.equal( readFileSync( `${ root }/build/${ file }`, 'utf8' ), readFileSync( `${ root }/src/${ file }`, 'utf8' ) );
	}
	execFileSync( process.execPath, [ '--check', 'build/index.js' ], { cwd: root } );
	execFileSync( process.execPath, [ '--check', 'build/sponsor.js' ], { cwd: root } );
	assert.ok( readFileSync( `${ root }/build/sponsor.js`, 'utf8' ).includes( readFileSync( `${ root }/src/sponsor.js`, 'utf8' ) ) );
	assert.match( readFileSync( `${ root }/build/sponsor.asset.php`, 'utf8' ), /wp-editor/ );
	assert.match( readFileSync( `${ root }/build/sponsor.asset.php`, 'utf8' ), /wp-plugins/ );
	assert.match( readFileSync( `${ root }/build/index.asset.php`, 'utf8' ), /wp-core-data/ );
	assert.match( readFileSync( `${ root }/build/index.asset.php`, 'utf8' ), /wp-html-entities/ );
	for ( const file of [ 'jost-normal.woff2', 'jost-italic.woff2', 'OFL.txt' ] ) {
		assert.deepEqual( readFileSync( `${ root }/build/fonts/${ file }` ), readFileSync( `${ root }/src/fonts/${ file }` ) );
	}
	assert.deepEqual( readFileSync( `${ root }/build/assets/placeholder.svg` ), readFileSync( `${ root }/src/assets/placeholder.svg` ) );
} );
