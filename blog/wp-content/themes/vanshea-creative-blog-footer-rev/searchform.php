<?php
/**
 * Search form template.
 *
 * @package VanSheaCreativeBlog
 */
?>

<form role="search" method="get" class="search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label>
		<span class="screen-reader-text"><?php esc_html_e( 'Search for:', 'vanshea-creative-blog' ); ?></span>
		<input type="search" class="search-field" placeholder="<?php esc_attr_e( 'Search the blog', 'vanshea-creative-blog' ); ?>" value="<?php echo esc_attr( get_search_query() ); ?>" name="s">
	</label>
	<input type="submit" class="search-submit" value="<?php esc_attr_e( 'Search', 'vanshea-creative-blog' ); ?>">
</form>
