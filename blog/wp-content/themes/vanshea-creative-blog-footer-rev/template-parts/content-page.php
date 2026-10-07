<?php
/**
 * Page content template.
 *
 * @package VanSheaCreativeBlog
 */
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'content-shell' ); ?>>
	<header class="entry-header">
		<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
		<?php if ( has_post_thumbnail() ) : ?>
			<figure class="entry-featured-image">
				<?php vanshea_creative_blog_post_image( 'large', 'entry-featured-image-img' ); ?>
			</figure>
		<?php endif; ?>
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
</article>
