<?php
/**
 * Offline rendering contract tests. WordPress boundaries are replaced, not the template.
 *
 * @package AreMediaGutenberg
 */

define( 'ABSPATH', __DIR__ );
$GLOBALS['fixtures'] = array();
$GLOBALS['queries']  = array();

function add_action( ...$args ) {}
function add_filter( ...$args ) {}
function get_category( $id ) {
	return 7 === $id ? (object) array( 'name' => 'Gardening & Homes' ) : false;
}
function is_wp_error( $value ) { return false; }
function get_block_wrapper_attributes() { return 'class="wp-block-aremedia-category-articles"'; }
function esc_html( $value ) { return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $value ) { return esc_html( $value ); }
function esc_url( $value ) { return 0 === strpos( $value, 'https://' ) ? esc_attr( $value ) : ''; }
function __( $value, $domain = '' ) { return $value; }
function esc_html__( $value, $domain = '' ) { return esc_html( $value ); }
function get_permalink( $post ) { return 'https://example.test/article/' . $post->ID; }
function get_the_title( $post ) { return $post->post_title; }
function get_the_excerpt( $post ) {
	return $GLOBALS['excerpt_override'] ?? ( $post->post_excerpt ? $post->post_excerpt : strip_shortcodes( $post->post_content ) );
}
function get_post_meta( $id, $key, $single = false ) {
	return 2 === $id ? ( empty( $GLOBALS['preview_mode'] ) ? '<script>Brand</script>' : 'Evergreen' ) : '';
}
function wp_strip_all_tags( $text ) { return strip_tags( $text ); }
function strip_shortcodes( $text ) { return preg_replace( '/\[.*?\]/', '', $text ); }
function wp_trim_words( $text, $count, $more ) {
	$words = preg_split( '/\s+/', trim( strip_tags( $text ) ) );
	return implode( ' ', array_slice( $words, 0, $count ) ) . ( count( $words ) > $count ? $more : '' );
}
function get_the_post_thumbnail( $post, $size, $attributes ) {
	return 3 === $post->ID && empty( $GLOBALS['preview_mode'] ) ? '<img src="https://example.test/image.jpg" alt="" />' : '';
}
class WP_Query {
	public $posts;
	public function __construct( $args ) {
		$GLOBALS['queries'][] = $args;
		$this->posts         = $GLOBALS['fixtures'];
	}
}

$plugin = dirname( __DIR__ );
if ( ! file_exists( $plugin . '/aremedia-gutenberg.php' ) ) {
	fwrite( STDERR, "FAIL: plugin registration is missing.\n" );
	exit( 1 );
}
require $plugin . '/aremedia-gutenberg.php';

function check( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
	$GLOBALS['checks'] = ( $GLOBALS['checks'] ?? 0 ) + 1;
}
function render_block_fixture( $attributes ) {
	ob_start();
	include dirname( __DIR__ ) . '/src/render.php';
	return ob_get_clean();
}

foreach ( array( array(), array( 'categoryId' => 0 ), array( 'categoryId' => -7 ), array( 'categoryId' => 7.5 ), array( 'categoryId' => 99 ) ) as $attributes ) {
	check( '' === render_block_fixture( $attributes ), 'Invalid category must render nothing.' );
}
check( array() === $GLOBALS['queries'], 'Invalid categories must not query posts.' );
check( '' === render_block_fixture( array( 'categoryId' => 7 ) ), 'Empty category must render nothing.' );
$args = end( $GLOBALS['queries'] );
check( 5 === $args['posts_per_page'], 'Query must request five posts.' );
check( 'publish' === $args['post_status'] && false === $args['has_password'], 'Query must exclude nonpublic content.' );
check( array( 'date' => 'DESC', 'ID' => 'DESC' ) === $args['orderby'], 'Query must have deterministic publication order.' );
check( false === $args['tax_query'][0]['include_children'], 'Query must exclude descendant categories.' );
check( array( 7 ) === $args['tax_query'][0]['terms'], 'Query must match selected category.' );
check( true === $args['ignore_sticky_posts'], 'Sticky priority must be ignored.' );

$rest_args = aremedia_gutenberg_rest_query( array( 'unrelated' => true ), array( 'aremedia_category' => 7 ) );
check( $rest_args['orderby'] === $args['orderby'], 'REST and frontend order must match.' );
check( $rest_args['tax_query'] === $args['tax_query'] && false === $rest_args['has_password'], 'REST and frontend privacy/category filters must match.' );
check( array( 'unrelated' => true ) === aremedia_gutenberg_rest_query( array( 'unrelated' => true ), array() ), 'Other REST consumers must remain unchanged.' );

for ( $i = 1; $i <= 5; ++$i ) {
	$GLOBALS['fixtures'][] = (object) array(
		'ID'           => $i,
		'post_title'   => 1 === $i ? '<script>alert(1)</script> & Hero' : 'Card ' . $i,
		'post_excerpt' => 'A short <b>excerpt</b>.',
		'post_content' => 'Fallback content.',
	);
	$html = render_block_fixture( array( 'categoryId' => 7 ) );
	check( 1 === substr_count( $html, 'class="aremedia-hero"' ), 'Exactly one hero must render.' );
	check( $i - 1 === substr_count( $html, 'class="aremedia-card"' ), 'Sparse output must contain only existing cards.' );
	check( $i === substr_count( $html, 'class="aremedia-category"' ), 'Default category labels must appear on every article.' );
	check( ( $i > 1 ) === ( false !== strpos( $html, 'class="aremedia-grid"' ) ), 'A lone hero must not create an empty grid.' );
}
check( false === strpos( $html, '<script>' ), 'Untrusted title and sponsor must be escaped.' );
check( false !== strpos( $html, '&lt;script&gt;alert(1)&lt;/script&gt; &amp; Hero' ), 'Headline must preserve text safely.' );
check( false !== strpos( $html, 'Sponsored by' ), 'Sponsor must appear on its smaller card.' );
check( 4 === substr_count( $html, 'aremedia-placeholder' ), 'Missing images must use placeholders.' );
check( false !== strpos( $html, 'image.jpg' ), 'Available featured images must render.' );
check( false !== strpos( $html, 'A short excerpt.' ), 'Excerpt must be plain text.' );

$html = render_block_fixture( array( 'categoryId' => 7, 'showCategory' => false, 'showExcerpt' => false ) );
check( false === strpos( $html, 'class="aremedia-category"' ), 'Category toggle must hide all labels.' );
check( false === strpos( $html, 'class="aremedia-excerpt"' ), 'Excerpt toggle must hide subtext.' );
$GLOBALS['fixtures'][0]->post_excerpt = '';
$GLOBALS['fixtures'][0]->post_content = implode( ' ', range( 1, 40 ) );
$html = render_block_fixture( array( 'categoryId' => 7 ) );
check( false !== strpos( $html, '29 30…' ) && false === strpos( $html, '31 32' ), 'Generated excerpt must stop at 30 words.' );
$GLOBALS['fixtures'][0]->post_title = '';
check( false !== strpos( render_block_fixture( array( 'categoryId' => 7 ) ), 'Untitled article' ), 'Empty titles must have an accessible link name.' );
$GLOBALS['fixtures'][0]->post_content = '<figure><figcaption>Caption excluded by WordPress</figcaption></figure><p>Article body.</p>';
$GLOBALS['excerpt_override'] = 'Article body.';
$html = render_block_fixture( array( 'categoryId' => 7 ) );
check( false === strpos( $html, 'Caption excluded' ) && false !== strpos( $html, 'Article body.' ), 'Frontend must use the block-aware WordPress excerpt, not raw content.' );
unset( $GLOBALS['excerpt_override'] );
echo 'PASS: ' . $GLOBALS['checks'] . " PHP rendering checks.\n";
