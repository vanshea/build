<?php
/**
 * Main index template.
 *
 * @package VanSheaCreativeBlog
 */

get_header();
?>

<?php if ( is_home() && ! is_paged() ) : ?>
	<section class="hero">
		<p class="eyebrow"><?php esc_html_e( 'Notes and essays', 'vanshea-creative-blog' ); ?></p>
		<h1><?php bloginfo( 'name' ); ?></h1>
		<p class="lead"><?php echo esc_html( get_bloginfo( 'description' ) ); ?></p>
	</section>
<?php endif; ?>

<?php if ( have_posts() ) : ?>
	<section class="post-grid" aria-label="<?php esc_attr_e( 'Blog posts', 'vanshea-creative-blog' ); ?>">
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
