<?php
/**
 * Card template for post lists.
 *
 * @package VanSheaCreativeBlog
 */
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'post-card' ); ?>>
	<a class="post-card-link" href="<?php the_permalink(); ?>">
		<span class="post-card-media">
			<?php if ( ! vanshea_creative_blog_post_image( 'large', 'post-card-image' ) ) : ?>
				<span class="post-card-placeholder" aria-hidden="true"><?php echo esc_html( substr( wp_strip_all_tags( get_the_title() ), 0, 1 ) ); ?></span>
			<?php endif; ?>
		</span>
		<h2><?php the_title(); ?></h2>
		<p class="post-card-meta">
			<?php vanshea_creative_blog_posted_on(); ?>
		</p>
		<p class="post-card-excerpt"><?php echo esc_html( get_the_excerpt() ); ?></p>
	</a>
</article>
