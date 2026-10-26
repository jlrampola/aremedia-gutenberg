import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { test } from 'node:test';
import { runInNewContext } from 'node:vm';

const metadata = JSON.parse( readFileSync( new URL( '../src/block.json', import.meta.url ) ) );
const source = () => readFileSync( new URL( '../src/index.js', import.meta.url ), 'utf8' );

function editor( { categories = [ { id: 7, name: 'Gardening' } ], posts = [], error = null, pages = 1 } = {} ) {
	let settings;
	const queries = [];
	const changes = [];
	const store = {
		getEntityRecords( kind, name, query ) {
			queries.push( { kind, name, query } );
			return name === 'category' ? ( query.page === 2 ? [ { id: 101, name: 'Second page' } ] : categories ) : posts;
		},
		getEntityRecordsTotalPages: () => pages,
		getResolutionError: () => error,
	};
	const wp = {
		blocks: { registerBlockType: ( name, value ) => { assert.equal( name, metadata.name ); settings = value; } },
		element: {
			Fragment: 'fragment',
			createElement: ( type, props, ...children ) => ( { type, props: props || {}, children: children.flat( Infinity ).filter( ( child ) => child !== null && child !== false ) } ),
		},
		blockEditor: { InspectorControls: 'inspector', useBlockProps: () => ( { className: 'wp-block-aremedia-category-articles' } ) },
		components: { PanelBody: 'panel', SelectControl: 'select', ToggleControl: 'toggle', Notice: 'notice', Spinner: 'spinner' },
		data: { useSelect: ( select ) => select( () => store ) },
		coreData: { store: 'core' },
		i18n: { __: ( value ) => value, sprintf: ( text, value ) => text.replace( '%s', value ) },
		htmlEntities: { decodeEntities: ( value ) => value.replaceAll( '&amp;', '&' ) },
	};
	runInNewContext( source(), { wp, metadata } );
	return {
		queries,
		changes,
		render: ( attributes = {} ) => settings.edit( { attributes: { categoryId: 7, showCategory: true, showExcerpt: true, ...attributes }, setAttributes: ( next ) => changes.push( next ) } ),
		save: () => settings.save(),
	};
}

function nodes( tree, predicate ) {
	if ( ! tree || typeof tree !== 'object' ) {
		return [];
	}
	return [ ...( predicate( tree ) ? [ tree ] : [] ), ...( tree.children || [] ).flatMap( ( child ) => nodes( child, predicate ) ) ];
}

const post = ( id ) => ( {
	id,
	link: `https://example.test/article/${ id }`,
	title: { rendered: `Article ${ id }` },
	excerpt: { rendered: '<p>Plain excerpt &amp; more.</p>' },
	meta: { aremedia_sponsor: id === 2 ? 'Brand name' : '' },
	_embedded: {},
} );

test( 'preview queries five public posts through the shared exact-category contract', () => {
	const app = editor();
	app.render();
	const query = app.queries.find( ( entry ) => entry.name === 'post' ).query;
	assert.equal( query.aremedia_category, 7 );
	assert.equal( query.per_page, 5 );
	assert.equal( query.status, 'publish' );
	assert.equal( query.context, 'view' );
	assert.equal( query._embed, true );
	assert.equal( app.save(), null );
} );

test( 'category dropdown includes empty categories and subsequent result pages', () => {
	const app = editor( { pages: 2 } );
	const tree = app.render();
	const select = nodes( tree, ( node ) => node.type === 'select' )[ 0 ];
	assert.equal( select.props.options.length, 3 );
	assert.equal( select.props.options[ 2 ].value, 101 );
	assert.equal( app.queries[ 0 ].query.hide_empty, false );
	assert.equal( app.queries[ 0 ].query.context, 'view' );
	select.props.onChange( '101' );
	assert.equal( app.changes[ 0 ].categoryId, 101 );
} );

test( 'preview renders one hero and only the available cards', () => {
	for ( let count = 1; count <= 5; count++ ) {
		const tree = editor( { posts: Array.from( { length: count }, ( _, i ) => post( i + 1 ) ) } ).render();
		assert.equal( nodes( tree, ( node ) => node.props.className === 'aremedia-hero' ).length, 1 );
		assert.equal( nodes( tree, ( node ) => node.props.className === 'aremedia-card' ).length, count - 1 );
		assert.equal( nodes( tree, ( node ) => node.props.className === 'aremedia-grid' ).length, count > 1 ? 1 : 0 );
	}
} );

test( 'toggles hide labels and hero excerpt, while sponsor and placeholders remain', () => {
	const app = editor( { posts: [ post( 1 ), post( 2 ) ] } );
	const tree = app.render( { showCategory: false, showExcerpt: false } );
	assert.equal( nodes( tree, ( node ) => [ 'aremedia-category', 'aremedia-excerpt' ].includes( node.props.className ) ).length, 0 );
	assert.equal( nodes( tree, ( node ) => node.props.className?.includes( 'aremedia-placeholder' ) ).length, 2 );
	assert.match( JSON.stringify( tree ), /Sponsored by Brand name/ );
	nodes( tree, ( node ) => node.type === 'toggle' )[ 0 ].props.onChange( true );
	assert.equal( app.changes[ 0 ].showCategory, true );
} );

test( 'selection, empty, deleted-category, loading and failed requests give feedback', () => {
	assert.match( JSON.stringify( editor().render( { categoryId: 0 } ) ), /Select a category/ );
	assert.match( JSON.stringify( editor().render() ), /No published articles/ );
	assert.match( JSON.stringify( editor( { categories: [] } ).render() ), /no longer available/ );
	assert.ok( nodes( editor( { posts: null } ).render(), ( node ) => node.type === 'spinner' ).length );
	assert.match( JSON.stringify( editor( { posts: null, error: { message: 'Forbidden' } } ).render() ), /Unable to load/ );
	const app = editor();
	app.render( { categoryId: 0 } );
	assert.equal( app.queries.some( ( query ) => query.name === 'post' ), false );
} );

test( 'media, text, excerpt limit and preview links render safely', () => {
	const article = post( 1 );
	article.title.rendered = '';
	article.excerpt.rendered = Array.from( { length: 40 }, ( _, i ) => String( i + 1 ) ).join( ' ' );
	article._embedded[ 'wp:featuredmedia' ] = [ { source_url: 'https://example.test/image.jpg', media_details: { sizes: { large: { source_url: 'https://example.test/large.jpg' } } } } ];
	const tree = editor( { posts: [ article ] } ).render();
	assert.match( JSON.stringify( tree ), /Untitled article/ );
	assert.match( JSON.stringify( tree ), /29 30…/ );
	assert.doesNotMatch( JSON.stringify( tree ), /31 32/ );
	assert.equal( nodes( tree, ( node ) => node.type === 'img' )[ 0 ].props.alt, '' );
	let prevented = false;
	const link = nodes( tree, ( node ) => node.type === 'a' )[ 0 ];
	link.props.onClick( { preventDefault: () => { prevented = true; } } );
	assert.equal( prevented, true );
	assert.equal( link.props.tabIndex, -1 );
} );
