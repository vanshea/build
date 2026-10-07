<?php
/**
 * Footer template.
 *
 * @package VanSheaCreativeBlog
 */
?>
</main>

<?php
$footer_text   = get_theme_mod( 'vsc_footer_text', __( 'Van Shea Sedita. All rights reserved.', 'vanshea-creative-blog' ) );
$default_theme = vanshea_creative_blog_get_default_theme();
$theme_choices = vanshea_creative_blog_theme_choices();
?>

<footer class="site-footer" id="siteFooter" data-footer-pattern>
	<div class="footer-pattern-layer" aria-hidden="true"></div>
	<?php if ( get_theme_mod( 'vsc_show_footer_art', true ) ) : ?>
		<div
			class="site-footer-art"
			data-footer-art-trigger
			role="button"
			tabindex="0"
			aria-label="<?php esc_attr_e( 'Activate footer artwork animation', 'vanshea-creative-blog' ); ?>"
		>
			<img
				src="<?php echo esc_url( get_template_directory_uri() . '/assets/footer-art/site-footer-rev-sm.png' ); ?>"
				alt="<?php esc_attr_e( 'Artwork that is evolving, made by hand, summarizing past work. No A.I. was used or harmed in this creation.', 'vanshea-creative-blog' ); ?>"
				width="1800"
				height="1973"
				loading="lazy"
				decoding="async"
			/>
		</div>
	<?php endif; ?>
	<div class="site-footer-inner">
		<div class="site-footer-controls">
			<div class="footer-actions" aria-label="<?php esc_attr_e( 'Connect with Van Shea', 'vanshea-creative-blog' ); ?>">
				<script
					src="https://assets.calendly.com/assets/external/widget.js"
					type="text/javascript"
					async
				></script>
				<a
					class="theme-link"
					href="https://calendly.com/van-shea/30min"
					target="_blank"
					rel="noreferrer"
					onclick="if (window.Calendly) { Calendly.initPopupWidget({url: 'https://calendly.com/van-shea/30min'}); return false; }"
				>
					<?php esc_html_e( 'Schedule Time', 'vanshea-creative-blog' ); ?>
				</a>
				<a
					class="theme-link"
					href="https://www.linkedin.com/in/vanshea/"
					target="_blank"
					rel="noreferrer"
				>
					<?php esc_html_e( 'LinkedIn', 'vanshea-creative-blog' ); ?>
				</a>
			</div>
			<?php if ( get_theme_mod( 'vsc_show_theme_switcher', true ) ) : ?>
				<div class="theme-switcher" role="group" aria-label="<?php esc_attr_e( 'Choose site theme', 'vanshea-creative-blog' ); ?>">
					<?php foreach ( $theme_choices as $theme_slug => $theme_label ) : ?>
						<button
							class="theme-link<?php echo $theme_slug === $default_theme ? ' is-active' : ''; ?>"
							type="button"
							data-theme="<?php echo esc_attr( $theme_slug ); ?>"
							aria-pressed="<?php echo $theme_slug === $default_theme ? 'true' : 'false'; ?>"
						>
							<?php echo esc_html( $theme_label ); ?>
						</button>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		<p>&copy; <span id="year"><?php echo esc_html( gmdate( 'Y' ) ); ?></span> <?php echo esc_html( $footer_text ); ?></p>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
