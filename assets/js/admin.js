/**
 * Stripe CLI Demo - Admin JavaScript
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        var $buyBtn = $('#stripe-cli-demo-buy-btn');
        var $status = $('#stripe-cli-demo-status');

        if ($buyBtn.length === 0) {
            return;
        }

        $buyBtn.on('click', function(e) {
            e.preventDefault();

            // Show loading state
            $buyBtn.prop('disabled', true).text('Processing...');
            $status.removeClass('success error').addClass('loading')
                   .text('Creating checkout session...').show();

            // Create checkout session via AJAX
            $.ajax({
                url: stripeCliDemo.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'stripe_cli_demo_create_checkout',
                    nonce: stripeCliDemo.nonce
                },
                success: function(response) {
                    if (response.success && response.data.checkout_url) {
                        $status.removeClass('loading').addClass('success')
                               .text('Redirecting to Stripe Checkout...');

                        // Redirect to Stripe Checkout
                        window.location.href = response.data.checkout_url;
                    } else {
                        var message = response.data && response.data.message
                            ? response.data.message
                            : 'An error occurred';

                        $status.removeClass('loading').addClass('error')
                               .text('Error: ' + message);

                        $buyBtn.prop('disabled', false).text('Buy Now');
                    }
                },
                error: function(xhr, status, error) {
                    $status.removeClass('loading').addClass('error')
                           .text('Network error: ' + error);

                    $buyBtn.prop('disabled', false).text('Buy Now');
                }
            });
        });

        // Check URL parameters for success/cancel status
        var urlParams = new URLSearchParams(window.location.search);
        var status = urlParams.get('status');
        var sessionId = urlParams.get('session_id');

        if (status === 'success') {
            $status.removeClass('loading error').addClass('success')
                   .html('<strong>Payment successful!</strong> Check the <a href="' +
                         window.location.href.replace(/page=stripe-cli-demo.*/, 'page=stripe-cli-demo-events') +
                         '">Webhook Events</a> page to see the events received.')
                   .show();

            // Clean up URL
            var cleanUrl = window.location.href.split('?')[0] + '?page=stripe-cli-demo';
            window.history.replaceState({}, document.title, cleanUrl);
        } else if (status === 'cancelled') {
            $status.removeClass('loading success').addClass('error')
                   .text('Payment was cancelled.')
                   .show();

            // Clean up URL
            var cleanUrl = window.location.href.split('?')[0] + '?page=stripe-cli-demo';
            window.history.replaceState({}, document.title, cleanUrl);
        }
    });

})(jQuery);
