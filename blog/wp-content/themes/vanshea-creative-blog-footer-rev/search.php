<?php
/**
 * Search results template.
 *
 * @package VanSheaCreativeBlog
 */

get_header();
?>

<section class="hero">
	<p class="eyebrow"><?php esc_html_e( 'Search', 'vanshea-creative-blog' ); ?></p>
	<h1 class="page-title">
		<?php
		printf(
			/* translators: %s: search query. */
			esc_html__( 'Results for "%s"', 'vanshea-creative-blog' ),
			esc_html( get_search_query() )
		);
		?>
	</h1>
	<?php get_search_form(); ?>
</section>

<?php if ( have_posts() ) : ?>
	<section class="post-grid" aria-label="<?php esc_attr_e( 'Search results', 'vanshea-creative-blog' ); ?>">
		<?php
		while ( have_posts() ) :
			the_post();
			get_template_part( 'template-parts/content', 'card' );
		endwhile;
		?>
	</section>
	<?php the_posts_pagination(); ?>
<?php else : ?>
	<?php get_template_part( 'template-parts/content', 'none' ); ?>
<?php endif; ?>

<?php
get_footer();
