$(document).ready(function() {
    // Disable legacy purchase modal trigger only.
    $(document).on('click', '.purchasemodal', function(e) {
        e.preventDefault();
        return false;
    });

    function removeLicenseAlerts() {
        var patterns = [
            'unregistered version',
            'purchase code',
            'register your purchase',
            'purchasemodal'
        ];

        $('.alert, .dashalert, .alert-dismissible').each(function() {
            var $alert = $(this);
            var raw = ($alert.text() || '').toLowerCase();
            var hit = false;

            for (var i = 0; i < patterns.length; i++) {
                if (raw.indexOf(patterns[i]) !== -1) {
                    hit = true;
                    break;
                }
            }

            if (!hit && $alert.find('a.purchasemodal').length) {
                hit = true;
            }

            if (hit) {
                $alert.remove();
            }
        });

        // Remove leftover empty brown bars/alert wrappers.
        $('.alert, .dashalert, .alert-dismissible').each(function() {
            var $el = $(this);
            var text = ($el.text() || '').replace(/\s+/g, '').toLowerCase();
            var hasContentNode = $el.find('a, button, input, img, svg, strong, span, p').filter(function() {
                return ($(this).text() || '').replace(/\s+/g, '').length > 0;
            }).length > 0;

            if (text.length === 0 && !hasContentNode) {
                $el.remove();
            }
        });

        // Extra fallback: remove full-width brown strip containers left in dashboard top.
        $('.content-wrapper .content > div > div').each(function() {
            var $box = $(this);
            var hasAlert = $box.find('.alert, .dashalert').length > 0;
            var cleanText = ($box.text() || '').replace(/\s+/g, '');
            if (!hasAlert && cleanText.length === 0 && $box.height() > 20) {
                $box.remove();
            }
        });
    }

    // Remove immediately and after delayed/injected renders.
    removeLicenseAlerts();
    setTimeout(removeLicenseAlerts, 300);
    setTimeout(removeLicenseAlerts, 1000);

    // Keep filtering if widgets append alerts after AJAX calls.
    if (window.MutationObserver) {
        var observer = new MutationObserver(function() {
            removeLicenseAlerts();
        });
        observer.observe(document.body, { childList: true, subtree: true });
    }
});