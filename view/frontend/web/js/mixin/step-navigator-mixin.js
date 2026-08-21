define([
    'mage/utils/wrapper',
    'IWD_OneStepCheckout/js/model/config'
], function (wrapper, config) {
    'use strict';

    function isVirtualQuote() {
        var quoteData = (window.checkoutConfig || {}).quoteData || {};

        return !!Number(quoteData.is_virtual);
    }

    function showAll(nav) {
        nav.steps().forEach(function (step) {
            if (step && typeof step.isVisible === 'function') {
                step.isVisible(true);
            }
        });
    }

    var mixin = {
        navigateTo: function (originalFn) {
            originalFn();
            showAll(this);
        },
        handleHash: function (originalFn) {
            originalFn();
            showAll(this);
        },
        next: function (originalFn) {
            originalFn();
            showAll(this);
        }
    };

    return function (target) {
        var cfg = config.getConfig(),
            mode = cfg.layoutMode || 'one_page',
            extended;

        if (!config.isActive()) {
            return target;
        }

        if (!document.body || !document.body.classList.contains('checkout-index-index')) {
            return target;
        }

        document.body.classList.add('iwd-osc');
        document.body.classList.add('iwd-osc--' + mode.replace('_', '-'));

        if (isVirtualQuote()) {
            document.body.classList.add('iwd-osc-virtual');
        }

        if (mode !== 'one_page') {
            return target;
        }

        extended = wrapper.extend(target, mixin);

        extended.steps.subscribe(function () {
            showAll(extended);
            setTimeout(function () {
                showAll(extended);
            }, 0);
        });

        return extended;
    };
});
