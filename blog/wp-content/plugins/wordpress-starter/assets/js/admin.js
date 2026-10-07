/*global ajaxurl*/
;(function( $ ) {
    $(document).ready(function() {
        // Switch to default dashboard.
        $('.switch-dashboard').on('click', function(e) {
            e.preventDefault();

            var adminUrl = $(this).data('admin-url');

            $.ajax(
                $(this).attr('href')
            )
                .success(function () {
                    window.location.href = adminUrl;
                })

        })

        // Switch to default dashboard.
        $('.sg-restart-wizard').on('click', function(e) {
            e.preventDefault();

            var adminUrl = $(this).data('admin-url');

            $.ajax(
                $(this).attr('href')
            )
                .success(function () {
                    window.location.href = adminUrl;
                })
        })
    })

    $(window).on('load',function(){
        $(document).on('click', '.wp-starter .sg-card__actions-wrapper button:first', function() {
            document.querySelector('#wp-ai-studio-container').shadowRoot.querySelector('.wp-ai-studio-chat__minimized .chat-close-state').click();
        })


    });


})( jQuery )
