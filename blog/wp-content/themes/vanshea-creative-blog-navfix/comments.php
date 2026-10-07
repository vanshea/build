<?php
/**
 * Comments template.
 *
 * @package VanSheaCreativeBlog
 */

if ( post_password_required() ) {
	return;
}
?>

<section id="comments" class="comments-area">
	<?php if ( have_comments() ) : ?>
		<h2>
			<?php
			printf(
				/* translators: %s: number of comments. */
				esc_html( _n( '%s comment', '%s comments', get_comments_number(), 'vanshea-creative-blog' ) ),
				esc_html( number_format_i18n( get_comments_number() ) )
			);
			?>
		</h2>

		<ol class="comment-list">
			<?php
			wp_list_comments(
				array(
					'style'      => 'ol',
					'short_ping' => true,
				)
			);
			?>
		</ol>

		<?php the_comments_navigation(); ?>
	<?php endif; ?>

	<?php comment_form(); ?>
</section>
