define([], function () {
    'use strict';

    return function () {
        var readySelector = '.opc-block-summary',
            observer = null,
            timer = null;

        function reveal() {
            if (timer) {
                clearTimeout(timer);
                timer = null;
            }

            if (observer) {
                observer.disconnect();
                observer = null;
            }

            document.body.classList.remove('iwd-osc-loading');
            document.body.classList.add('iwd-osc-ready');
        }

        if (document.querySelector(readySelector)) {
            reveal();

            return;
        }

        observer = new MutationObserver(function () {
            if (document.querySelector(readySelector)) {
                reveal();
            }
        });
        observer.observe(document.body, {childList: true, subtree: true});

        timer = setTimeout(reveal, 8000);
    };
});
