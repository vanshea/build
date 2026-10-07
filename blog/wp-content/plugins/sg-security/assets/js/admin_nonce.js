/*global ajaxurl, SGSecurityLogsNonce*/
;(function($) {
	$(window).on('load', function() {
		if (typeof window.SGSecurityAdminNonce !== 'undefined') {
			$.post(ajaxurl, {
				action: 'sgs_clear_logs',
				nonce: window.SGSecurityAdminNonce
			});
		}
	});
})(jQuery);