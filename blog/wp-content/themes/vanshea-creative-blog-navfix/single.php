<?php
/**
 * Single post template.
 *
 * @package VanSheaCreativeBlog
 */

get_header();
?>

<?php
while ( have_posts() ) :
	the_post();
	get_template_part( 'template-parts/content', 'single' );

	the_post_navigation(
		array(
			'prev_text' => __( 'Previous: %title', 'vanshea-creative-blog' ),
			'next_text' => __( 'Next: %title', 'vanshea-creative-blog' ),
		)
	);

	if ( comments_open() || get_comments_number() ) {
		comments_template();
	}
endwhile;
?>

<?php
get_footer();
