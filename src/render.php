<?php
/**
 * Dynamic category articles markup. No saved article snapshots.
 *
 * @package AreMediaGutenberg
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$category_id = filter_var( $attributes['categoryId'] ?? 0, FILTER_VALIDATE_INT, array( 'options' => array( 'min_range' => 1 ) ) );
if ( false === $category_id ) {
	return;
}

$category = get_category( $category_id );
if ( ! $category || is_wp_error( $category ) ) {
	return;
}

$query_args                  = aremedia_gutenberg_query_args( $category_id );
$query_args['no_found_rows'] = true;
$articles                    = ( new WP_Query( $query_args ) )->posts;
if ( empty( $articles ) ) {
	return;
}

$show_category = $attributes['showCategory'] ?? true;
$show_excerpt  = $attributes['showExcerpt'] ?? true;
?>
<div <?php echo get_block_wrapper_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WordPress escapes wrapper attributes. ?>>
	<?php foreach ( $articles as $index => $article ) : ?>
		<?php
		$is_hero = 0 === $index;
		$title   = get_the_title( $article );
		$title   = '' !== trim( $title ) ? $title : __( 'Untitled article', 'aremedia-gutenberg' );
		$image   = get_the_post_thumbnail( $article, $is_hero ? 'large' : 'medium', array( 'alt' => '' ) );
		$sponsor = $is_hero ? '' : get_post_meta( $article->ID, 'aremedia_sponsor', true );
		$sponsor = is_string( $sponsor ) ? trim( $sponsor ) : '';
		?>
		<?php if ( 1 === $index ) : ?>
			<div class="aremedia-grid">
		<?php endif; ?>
		<article class="<?php echo $is_hero ? 'aremedia-hero' : 'aremedia-card'; ?>">
			<a class="aremedia-link" href="<?php echo esc_url( get_permalink( $article ) ); ?>">
				<div class="aremedia-image<?php echo $image ? '' : ' aremedia-placeholder'; ?>"<?php echo $image ? '' : ' aria-hidden="true"'; ?>>
					<?php echo $image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core generates and escapes attachment markup. ?>
				</div>
				<div class="aremedia-copy">
					<?php if ( $show_category ) : ?>
						<p class="aremedia-category"><?php echo esc_html( $category->name ); ?></p>
					<?php endif; ?>
					<?php if ( $is_hero ) : ?>
						<h2 class="aremedia-title"><?php echo esc_html( $title ); ?></h2>
					<?php else : ?>
						<h3 class="aremedia-title"><?php echo esc_html( $title ); ?></h3>
					<?php endif; ?>
				</div>
			</a>
			<?php if ( $is_hero && $show_excerpt ) : ?>
				<?php
				$excerpt = $article->post_excerpt ? $article->post_excerpt : strip_shortcodes( $article->post_content );
				$excerpt = wp_trim_words( wp_strip_all_tags( $excerpt ), 30, '…' );
				?>
				<?php if ( '' !== trim( $excerpt ) ) : ?>
					<p class="aremedia-excerpt"><?php echo esc_html( $excerpt ); ?></p>
				<?php endif; ?>
			<?php endif; ?>
			<?php if ( '' !== $sponsor ) : ?>
				<p class="aremedia-sponsor">
					<?php
					/* translators: %s: sponsoring brand name. */
					echo esc_html( sprintf( __( 'Sponsored by %s', 'aremedia-gutenberg' ), $sponsor ) );
					?>
				</p>
			<?php endif; ?>
		</article>
	<?php endforeach; ?>
	<?php if ( count( $articles ) > 1 ) : ?>
		</div>
	<?php endif; ?>
</div>
