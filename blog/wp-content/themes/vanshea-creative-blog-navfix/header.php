<?php
/**
 * Header template.
 *
 * @package VanSheaCreativeBlog
 */
?><!doctype html>
<html <?php language_attributes(); ?> data-theme="<?php echo esc_attr( vanshea_creative_blog_get_default_theme() ); ?>">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="color-scheme" content="light dark">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div class="bg-shape bg-shape-a"></div>
<div class="bg-shape bg-shape-b"></div>

<header class="site-header">
	<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="brand" aria-label="<?php esc_attr_e( 'Back to blog home', 'vanshea-creative-blog' ); ?>">
		<picture>
			<source srcset="<?php echo esc_url( get_template_directory_uri() . '/assets/web_logomark_240_lite.png' ); ?>" media="(prefers-color-scheme: dark)">
			<img class="brand-logo" src="<?php echo esc_url( get_template_directory_uri() . '/assets/web_logomark_240_dark.png' ); ?>" alt="<?php esc_attr_e( 'VSS logo', 'vanshea-creative-blog' ); ?>">
		</picture>
		<span class="brand-name"><?php esc_html_e( 'Van Shea Creative', 'vanshea-creative-blog' ); ?></span>
	</a>

	<button class="nav-toggle" type="button" aria-expanded="false" aria-controls="siteNav" aria-label="<?php esc_attr_e( 'Open navigation menu', 'vanshea-creative-blog' ); ?>">
		<span class="sr-only"><?php esc_html_e( 'Toggle navigation', 'vanshea-creative-blog' ); ?></span>
		<span class="nav-toggle-icon" aria-hidden="true">
			<span class="nav-toggle-bar"></span>
			<span class="nav-toggle-bar"></span>
			<span class="nav-toggle-bar"></span>
		</span>
	</button>

	<nav id="siteNav" class="nav" aria-label="<?php esc_attr_e( 'Primary navigation', 'vanshea-creative-blog' ); ?>">
		<?php
		if ( has_nav_menu( 'primary' ) ) {
			wp_nav_menu(
				array(
					'theme_location' => 'primary',
					'container'      => false,
					'menu_class'     => 'nav-menu',
					'items_wrap'     => '<ul id="%1$s" class="%2$s">%3$s</ul>',
					'depth'          => 1,
				)
			);
		} else {
			?>
			<a href="https://www.vanshea.com/#work"><?php esc_html_e( 'Work', 'vanshea-creative-blog' ); ?></a>
			<a href="https://www.vanshea.com/case-studies"><?php esc_html_e( 'Case Studies', 'vanshea-creative-blog' ); ?></a>
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-current="page"><?php esc_html_e( 'Blog', 'vanshea-creative-blog' ); ?></a>
			<?php
		}
		?>
	</nav>
</header>

<main id="top" class="site-main">
