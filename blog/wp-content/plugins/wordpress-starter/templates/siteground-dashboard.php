<div id="sg-admin-container"></div>

<p class="switch-do-default-dashboard">
	<?php
	echo sprintf(
		__( 'If you wish you can always <a href="%1$s" class="switch-dashboard" data-admin-url="%2$s">switch to default</a> dashboard.', 'siteground-wizard' ),
		wp_nonce_url( admin_url( 'admin-ajax.php?action=switch_dashboard&value=yes' ), 'switch_dashboard_nonce', 'switch_dashboard' ),
		admin_url( '/' )
	);
	?>
</p>
