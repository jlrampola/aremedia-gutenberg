<?php
/** Offline layout fixture using the real PHP template; not a WordPress runtime test. */
ob_start();
require __DIR__ . '/render.php';
ob_end_clean();

$GLOBALS['preview_mode'] = true;
$typography_fixture     = in_array( '--typography', $argv, true );
$reference_fixture      = in_array( '--reference', $argv, true ) || $typography_fixture;
$GLOBALS['reference_mode'] = $reference_fixture;
$titles                 = array(
	'A garden that grows with you through every season',
	'Small changes that make a home feel yours',
	'The art of growing something beautiful',
	'A fresh perspective on everyday spaces',
	'Ideas to bring the outdoors into your home',
);
if ( $reference_fixture ) {
	$titles = array(
		'Lorem ipsum dolor sit amet consectetur adipisicing with the latest issue of Sample Title!',
		'Lorem ipsum dolor sit amet consectetur elit?',
		'Ut enim ad minim veniam quis nostrud exercitation…',
		'Duis aute irure dolor in reprehenderit voluptate…',
		'Excepteur sint occaecat cupidatat non proident?',
	);
}
foreach ( $GLOBALS['fixtures'] as $index => $article ) {
	$article->post_title   = $titles[ $index ];
	$article->post_excerpt = $reference_fixture ? 'Sed do eiusmod tempor incididunt ut labore.' : 'Discover thoughtful ideas, practical advice and inspiration for a home and garden you love.';
}
$css = file_get_contents( dirname( __DIR__ ) . '/build/style.css' );
foreach ( array( 'jost-normal.woff2', 'jost-italic.woff2' ) as $font ) {
	$data = base64_encode( file_get_contents( dirname( __DIR__ ) . '/build/fonts/' . $font ) );
	$css  = str_replace( './fonts/' . $font, 'data:font/woff2;base64,' . $data, $css );
}
$mobile_fixture = in_array( '--mobile', $argv, true );
echo '<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
echo '<title>Category Articles — offline layout fixture</title><style>body{margin:0;background:#fff}main{max-width:1100px;margin:32px auto;padding:0 24px}' . $css . '</style>';
if ( $mobile_fixture ) {
	// Headless Edge enforces a wider browser window; constrain the actual fixture container.
	echo '<style>body{width:390px}main{box-sizing:border-box;width:390px;margin-inline:0}</style>';
}
if ( $reference_fixture ) {
	echo '<style>body{width:1720px}main{box-sizing:border-box;max-width:1720px;width:1720px;margin:40px 0;padding-inline:32px}</style>';
}
if ( $typography_fixture ) {
	echo '<style>body{width:700px}main{max-width:674px;width:674px;margin:10px 0;padding-inline:26px}.aremedia-hero>.aremedia-link>.aremedia-image{display:none}.wp-block-aremedia-category-articles .aremedia-hero .aremedia-copy{margin-block-start:0}</style>';
}
echo '<main>' . render_block_fixture( array( 'categoryId' => 7 ) ) . '</main>';
?>
<script>
window.addEventListener( 'load', async function () {
	await document.fonts.ready;
	const block = document.querySelector( '.wp-block-aremedia-category-articles' );
	const cards = Array.from( document.querySelectorAll( '.aremedia-card' ) );
	const hero = document.querySelector( '.aremedia-hero .aremedia-image' ).getBoundingClientRect();
	const mobile = block.clientWidth <= 600;
	const first = cards[ 0 ].getBoundingClientRect();
	const second = cards[ 1 ].getBoundingClientRect();
	const thumbnail = cards[ 0 ].querySelector( '.aremedia-image' ).getBoundingClientRect();
	const checks = {
		expectedWidth: <?php echo $mobile_fixture ? 'block.clientWidth <= 342' : 'true'; ?>,
		articleCount: document.querySelectorAll( 'article' ).length === 5,
		heroRatio: <?php echo $typography_fixture ? 'true' : 'Math.abs( hero.width / hero.height - 1630 / 918 ) < 0.01'; ?>,
		columns: mobile ? second.top > first.top : Math.abs( second.top - first.top ) < 1,
		thumbnail: Math.abs( thumbnail.width - ( mobile ? 80 : block.clientWidth * 300 / 1656 ) ) < 1 && Math.abs( thumbnail.height - thumbnail.width ) < 1,
		noOverflow: document.documentElement.scrollWidth <= <?php echo $typography_fixture ? '700' : ( $reference_fixture ? '1720' : 'window.innerWidth' ); ?>,
		safeSponsor: ! document.querySelector( 'article script' ) && document.querySelector( '.aremedia-sponsor' ).textContent.includes( '<?php echo $reference_fixture ? 'BRAND NAME' : 'Evergreen'; ?>' ),
	};
	checks.localFont = Array.from( document.fonts ).some( font => font.family.replaceAll( '"', '' ) === 'AreMedia Jost' && font.status === 'loaded' );
	if ( <?php echo $typography_fixture ? 'true' : 'false'; ?> ) { delete checks.heroRatio; }
	if ( <?php echo $reference_fixture && ! $typography_fixture ? 'true' : 'false'; ?> ) {
		const card = document.querySelector( '.aremedia-card' );
		checks.referenceType = Math.abs( parseFloat( getComputedStyle( document.querySelector( '.aremedia-hero .aremedia-title' ) ).fontSize ) - 92 ) < 0.25 && Math.abs( parseFloat( getComputedStyle( card.querySelector( '.aremedia-title' ) ).fontSize ) - 58 ) < 0.1;
		checks.referenceGeometry = Math.abs( thumbnail.top - 1528 ) < 3 && Math.abs( second.left - 880 ) < 1 && Math.abs( card.querySelector( '.aremedia-copy' ).getBoundingClientRect().left - 368 ) < 1;
	}
	if ( <?php echo $typography_fixture ? 'true' : 'false'; ?> ) {
		const cardTitle = getComputedStyle( cards[ 0 ].querySelector( '.aremedia-title' ) );
		const label = getComputedStyle( document.querySelector( '.aremedia-category' ) );
		const excerpt = getComputedStyle( document.querySelector( '.aremedia-excerpt' ) );
		checks.typography = cardTitle.fontFamily.startsWith( '"Times New Roman"' ) && Math.abs( parseFloat( cardTitle.fontSize ) - 21.75 ) < 0.05 && cardTitle.fontWeight === '400' && parseFloat( label.fontSize ) === 10 && label.fontWeight === '500' && Math.abs( parseFloat( excerpt.fontSize ) - 15 ) < 0.05;
	}
	document.documentElement.setAttribute( 'data-layout-measurements', JSON.stringify( { viewport: window.innerWidth, scroll: document.documentElement.scrollWidth, width: block.clientWidth, hero: hero.toJSON(), first: first.toJSON(), second: second.toJSON(), thumbnail: thumbnail.toJSON() } ) );
	document.documentElement.setAttribute( 'data-layout-result', Object.values( checks ).every( Boolean ) ? 'PASS' : 'FAIL' );
	document.documentElement.setAttribute( 'data-layout-checks', JSON.stringify( checks ) );
} );
</script>
</html>
