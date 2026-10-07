<?php
/**
 * Archive template.
 *
 * @package VanSheaCreativeBlog
 */

get_header();
?>

<section class="hero">
	<p class="eyebrow"><?php esc_html_e( 'Archive', 'vanshea-creative-blog' ); ?></p>
	<?php the_archive_title( '<h1 class="page-title">', '</h1>' ); ?>
	<?php the_archive_description( '<div class="archive-description">', '</div>' ); ?>
</section>

<?php if ( have_posts() ) : ?>
	<section class="post-grid" aria-label="<?php esc_attr_e( 'Archive posts', 'vanshea-creative-blog' ); ?>">
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
