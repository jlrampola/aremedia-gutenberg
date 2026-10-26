<?php
/** Offline layout fixture using the real PHP template; not a WordPress runtime test. */
ob_start();
require __DIR__ . '/render.php';
ob_end_clean();

$GLOBALS['preview_mode'] = true;
$titles                 = array(
	'A garden that grows with you through every season',
	'Small changes that make a home feel yours',
	'The art of growing something beautiful',
	'A fresh perspective on everyday spaces',
	'Ideas to bring the outdoors into your home',
);
foreach ( $GLOBALS['fixtures'] as $index => $article ) {
	$article->post_title   = $titles[ $index ];
	$article->post_excerpt = 'Discover thoughtful ideas, practical advice and inspiration for a home and garden you love.';
}
$css = file_get_contents( dirname( __DIR__ ) . '/build/style.css' );
$mobile_fixture = in_array( '--mobile', $argv, true );
echo '<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
echo '<title>Category Articles — offline layout fixture</title><style>body{margin:0;background:#fff}main{max-width:1100px;margin:32px auto;padding:0 24px}' . $css . '</style>';
if ( $mobile_fixture ) {
	// Headless Edge enforces a wider browser window; constrain the actual fixture container.
	echo '<style>body{width:390px}main{box-sizing:border-box;width:390px;margin-inline:0}</style>';
}
echo '<main>' . render_block_fixture( array( 'categoryId' => 7 ) ) . '</main>';
?>
<script>
window.addEventListener( 'load', function () {
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
		heroRatio: Math.abs( hero.width / hero.height - 1630 / 918 ) < 0.01,
		columns: mobile ? second.top > first.top : Math.abs( second.top - first.top ) < 1,
		thumbnail: Math.abs( thumbnail.width - ( mobile ? 80 : 112 ) ) < 1 && Math.abs( thumbnail.height - thumbnail.width ) < 1,
		noOverflow: document.documentElement.scrollWidth <= window.innerWidth,
		safeSponsor: ! document.querySelector( 'article script' ) && document.querySelector( '.aremedia-sponsor' ).textContent.includes( 'Evergreen' ),
	};
	document.documentElement.setAttribute( 'data-layout-result', Object.values( checks ).every( Boolean ) ? 'PASS' : 'FAIL' );
	document.documentElement.setAttribute( 'data-layout-checks', JSON.stringify( checks ) );
} );
</script>
</html>
