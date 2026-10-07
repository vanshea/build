<?php
/**
 * Single content template.
 *
 * @package VanSheaCreativeBlog
 */
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'content-shell' ); ?>>
	<header class="entry-header">
		<p class="post-kicker"><?php esc_html_e( 'Essay', 'vanshea-creative-blog' ); ?></p>
		<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
		<p class="entry-meta">
			<?php vanshea_creative_blog_posted_on(); ?>
			<span aria-hidden="true"> · </span>
			<?php vanshea_creative_blog_posted_by(); ?>
		</p>
		<figure class="entry-featured-image">
			<?php if ( ! vanshea_creative_blog_post_image( 'large', 'entry-featured-image-img' ) ) : ?>
				<span class="post-card-placeholder" aria-hidden="true"><?php echo esc_html( substr( wp_strip_all_tags( get_the_title() ), 0, 1 ) ); ?></span>
			<?php endif; ?>
		</figure>
	</header>

	<div class="entry-content">
		<?php
		the_content();
		wp_link_pages(
			array(
				'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'vanshea-creative-blog' ),
				'after'  => '</div>',
			)
		);
		?>
	</div>

	<footer class="entry-footer">
		<?php the_category( ', ' ); ?>
		<?php the_tags( '<span class="tags"> · ', ', ', '</span>' ); ?>
	</footer>
</article>
