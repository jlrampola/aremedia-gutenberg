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
	assert.match( readFileSync( `${ root }/build/index.asset.php`, 'utf8' ), /wp-core-data/ );
	assert.match( readFileSync( `${ root }/build/index.asset.php`, 'utf8' ), /wp-html-entities/ );
} );
