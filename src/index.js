/* global wp, metadata */

const { createElement: el, Fragment } = wp.element;
const { InspectorControls, useBlockProps } = wp.blockEditor;
const { PanelBody, SelectControl, ToggleControl, Notice, Spinner } = wp.components;
const { __, sprintf } = wp.i18n;

function plainText( text ) {
	return wp.htmlEntities.decodeEntities( ( text || '' ).replace( /<[^>]*>/g, ' ' ) ).replace( /\s+/g, ' ' ).trim();
}

function articlePreview( post, index, attributes, categoryName ) {
	const hero = index === 0;
	const media = post._embedded?.[ 'wp:featuredmedia' ]?.[ 0 ];
	const size = hero ? 'large' : 'medium';
	const image = media?.media_details?.sizes?.[ size ]?.source_url || media?.source_url;
	const title = plainText( post.title?.rendered ) || __( 'Untitled article', 'aremedia-gutenberg' );
	const words = plainText( post.excerpt?.rendered ).split( ' ' );
	const excerpt = words.slice( 0, 30 ).join( ' ' ) + ( words.length > 30 ? '…' : '' );
	const sponsor = typeof post.meta?.aremedia_sponsor === 'string' ? post.meta.aremedia_sponsor.trim() : '';

	return el( 'article', { key: post.id, className: hero ? 'aremedia-hero' : 'aremedia-card' },
		el( 'a', { className: 'aremedia-link', href: post.link, tabIndex: -1, onClick: ( event ) => event.preventDefault() },
			el( 'div', { className: 'aremedia-image' + ( image ? '' : ' aremedia-placeholder' ), 'aria-hidden': image ? undefined : true },
				image ? el( 'img', { src: image, alt: '' } ) : null
			),
			el( 'div', { className: 'aremedia-copy' },
				attributes.showCategory ? el( 'p', { className: 'aremedia-category' }, categoryName ) : null,
				el( hero ? 'h2' : 'h3', { className: 'aremedia-title' }, title ),
				! hero && sponsor ? el( 'p', { className: 'aremedia-sponsor' },
					/* translators: %s: sponsoring brand name. */
					sprintf( __( 'Sponsored by %s', 'aremedia-gutenberg' ), sponsor )
				) : null
			)
		),
		hero && attributes.showExcerpt && excerpt ? el( 'p', { className: 'aremedia-excerpt' }, excerpt ) : null
	);
}

function Edit( { attributes, setAttributes } ) {
	const { categoryId } = attributes;
	const data = wp.data.useSelect( ( select ) => {
		const store = select( wp.coreData.store );
		const categoryQuery = { context: 'view', per_page: 100, hide_empty: false, orderby: 'name', order: 'asc', page: 1 };
		const firstPage = store.getEntityRecords( 'taxonomy', 'category', categoryQuery );
		const pageCount = store.getEntityRecordsTotalPages( 'taxonomy', 'category', categoryQuery ) || 1;
		let categories = firstPage ? [ ...firstPage ] : null;
		let error = store.getResolutionError( 'getEntityRecords', [ 'taxonomy', 'category', categoryQuery ] );

		for ( let page = 2; categories && page <= pageCount; page++ ) {
			const query = { ...categoryQuery, page };
			const records = store.getEntityRecords( 'taxonomy', 'category', query );
			error = error || store.getResolutionError( 'getEntityRecords', [ 'taxonomy', 'category', query ] );
			categories = records ? categories.concat( records ) : null;
		}

		const postQuery = { context: 'view', per_page: 5, status: 'publish', aremedia_category: categoryId, _embed: true };
		const posts = categoryId ? store.getEntityRecords( 'postType', 'post', postQuery ) : [];
		if ( categoryId ) {
			error = error || store.getResolutionError( 'getEntityRecords', [ 'postType', 'post', postQuery ] );
		}
		return { categories, posts, error };
	}, [ categoryId ] );

	const category = data.categories?.find( ( item ) => item.id === categoryId );
	const options = [ { label: __( 'Select a category', 'aremedia-gutenberg' ), value: 0 },
		...( data.categories || [] ).map( ( item ) => ( { label: wp.htmlEntities.decodeEntities( item.name ), value: item.id } ) ) ];
	let preview;
	if ( data.error ) {
		preview = el( Notice, { status: 'error', isDismissible: false }, __( 'Unable to load articles or categories. Check your connection and reopen the editor to retry.', 'aremedia-gutenberg' ) );
	} else if ( ! categoryId ) {
		preview = el( Notice, { status: 'info', isDismissible: false }, __( 'Select a category in the block settings to preview its latest articles.', 'aremedia-gutenberg' ) );
	} else if ( ! data.categories || ! data.posts ) {
		preview = el( 'div', { role: 'status', 'aria-label': __( 'Loading articles', 'aremedia-gutenberg' ) }, el( Spinner ) );
	} else if ( ! category ) {
		preview = el( Notice, { status: 'warning', isDismissible: false }, __( 'This category is no longer available. Select another category.', 'aremedia-gutenberg' ) );
	} else if ( ! data.posts.length ) {
		preview = el( Notice, { status: 'info', isDismissible: false }, __( 'No published articles in this category yet. Publish an article or select another category.', 'aremedia-gutenberg' ) );
	} else {
		const articles = data.posts.map( ( post, index ) => articlePreview( post, index, attributes, wp.htmlEntities.decodeEntities( category.name ) ) );
		preview = el( Fragment, null, articles[ 0 ], articles.length > 1 ? el( 'div', { className: 'aremedia-grid' }, articles.slice( 1 ) ) : null );
	}

	return el( Fragment, null,
		el( InspectorControls, null,
			el( PanelBody, { title: __( 'Article settings', 'aremedia-gutenberg' ) },
				el( SelectControl, {
					label: __( 'Category', 'aremedia-gutenberg' ),
					value: categoryId,
					options,
					disabled: ! data.categories,
					onChange: ( value ) => setAttributes( { categoryId: Number( value ) } ),
				} ),
				el( ToggleControl, {
					label: __( 'Display category label', 'aremedia-gutenberg' ),
					checked: attributes.showCategory,
					onChange: ( value ) => setAttributes( { showCategory: value } ),
				} ),
				el( ToggleControl, {
					label: __( 'Show subtext (excerpt)', 'aremedia-gutenberg' ),
					checked: attributes.showExcerpt,
					onChange: ( value ) => setAttributes( { showExcerpt: value } ),
				} )
			)
		),
		el( 'div', useBlockProps(), preview )
	);
}

wp.blocks.registerBlockType( metadata.name, {
	...metadata,
	title: __( 'Category Articles', 'aremedia-gutenberg' ),
	description: __( 'Show the newest article and four smaller cards from a category.', 'aremedia-gutenberg' ),
	edit: Edit,
	save: () => null,
} );
