import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { test } from 'node:test';
import { runInNewContext } from 'node:vm';

function sponsorEditor( postType = 'post', meta = { aremedia_sponsor: 'Existing brand', other: 'Keep me' } ) {
	let plugin;
	const changes = [];
	const wp = {
		plugins: { registerPlugin: ( name, settings ) => { plugin = settings; } },
		element: { createElement: ( type, props, ...children ) => ( { type, props, children } ) },
		editor: { PluginDocumentSettingPanel: 'panel' },
		components: { TextControl: 'input' },
		data: { useSelect: ( callback ) => callback( () => ( { getCurrentPostType: () => postType } ) ) },
		coreData: { useEntityProp: ( kind, type, property ) => {
			assert.equal( kind, 'postType' );
			assert.equal( type, postType );
			assert.equal( property, 'meta' );
			return [ meta, ( next ) => changes.push( next ) ];
		} },
		i18n: { __: ( value ) => value },
	};
	runInNewContext( readFileSync( new URL( '../src/sponsor.js', import.meta.url ), 'utf8' ), { wp } );
	return { render: () => plugin.render(), changes };
}

test( 'sponsor panel loads existing metadata and edits or clears it without losing other fields', () => {
	const editor = sponsorEditor();
	const panel = editor.render();
	assert.equal( panel.props.title, 'Sponsorship' );
	const field = panel.children[ 0 ];
	assert.equal( field.props.label, 'Sponsor name' );
	assert.equal( field.props.value, 'Existing brand' );
	field.props.onChange( 'New brand' );
	field.props.onChange( '' );
	assert.equal( editor.changes[ 0 ].aremedia_sponsor, 'New brand' );
	assert.equal( editor.changes[ 0 ].other, 'Keep me' );
	assert.equal( editor.changes[ 1 ].aremedia_sponsor, '' );
} );

test( 'sponsor panel is absent on pages and while metadata is loading', () => {
	assert.equal( sponsorEditor( 'page' ).render(), null );
	assert.equal( sponsorEditor( 'post', null ).render(), null );
	const field = sponsorEditor( 'post', {} ).render().children[ 0 ];
	assert.equal( field.props.value, '' );
} );
