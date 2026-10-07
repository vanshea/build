<?php
/**
 * Not found template.
 *
 * @package VanSheaCreativeBlog
 */

get_header();
?>

<section class="hero content-shell">
	<p class="eyebrow"><?php esc_html_e( 'Not found', 'vanshea-creative-blog' ); ?></p>
	<h1 class="page-title"><?php esc_html_e( 'That page is not here.', 'vanshea-creative-blog' ); ?></h1>
	<p class="lead"><?php esc_html_e( 'Try a search or head back to the blog index.', 'vanshea-creative-blog' ); ?></p>
	<?php get_search_form(); ?>
</section>

<?php
get_footer();
