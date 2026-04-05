(function () {
    let pollTimer = null;

    function startPolling() {
        stopPolling();

        pollTimer = setInterval(() => {
            $.ajax({
                url: '/api/paymentStatus.php',
                method: 'GET',
                dataType: 'json',
                success: function (data) {
                    console.log('Payment poll:', data);

                    if (data.paid && data.printed) {
                        stopPolling();

                        const overlay = document.querySelector('.overlay');
                        if (overlay) {
                            overlay.innerHTML = '✅ Zahlung erfolgreich – Druck abgeschlossen';
                            setTimeout(() => {
                                overlay.remove();
                            }, 1200);
                        }
                    }
                },
                error: function (xhr, status, err) {
                    console.log('Payment poll failed:', status, err);
                }
            });
        }, 2000);
    }

    function stopPolling() {
        if (pollTimer) {
            clearInterval(pollTimer);
            pollTimer = null;
        }
    }

    const observer = new MutationObserver(() => {
        const overlay = document.querySelector('.overlay');

        if (!overlay) {
            return;
        }

        if (overlay.innerHTML.includes('QR') || overlay.innerHTML.includes('bezahlen')) {
            startPolling();
        }
    });

    observer.observe(document.body, { childList: true, subtree: true });
})();
