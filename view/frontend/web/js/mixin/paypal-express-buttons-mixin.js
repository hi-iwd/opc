define([
    'jquery',
    'Magento_Paypal/js/in-context/paypal-sdk',
    'IWD_OneStepCheckout/js/express/paypal-funding-buttons',
    'IWD_OneStepCheckout/js/model/config'
], function ($, paypalSdk, makeFundingButton, config) {
    'use strict';

    var SOURCES = ['PAYPAL', 'VENMO', 'PAYLATER'];

    return function (Wrapper) {
        if (!config.isOnePage()) {
            return Wrapper;
        }
        var nativeRender = Wrapper.renderPayPalButtons;

        Wrapper.renderPayPalButtons = function (element) {
            if (!(window.checkoutConfig || {}).iwdOsc ||
                !$(element).closest('.iwd-osc-express-pay__buttons').length) {
                return nativeRender.call(this, element);
            }

            var clientConfig = this.prepareClientConfig();

            paypalSdk(clientConfig.sdkUrl, clientConfig.dataAttributes).done(function (paypal) {
                var count = 0;

                $(element).empty().addClass('iwd-osc-express-grid');

                SOURCES.forEach(function (name) {
                    var fundingSource = paypal.FUNDING && paypal.FUNDING[name],
                        button,
                        cell;

                    if (!fundingSource) {
                        return;
                    }

                    button = makeFundingButton(paypal, clientConfig, fundingSource);

                    if (!button.isEligible()) {
                        return;
                    }

                    cell = document.createElement('div');
                    cell.className = 'iwd-osc-express-grid__cell';
                    element.appendChild(cell);
                    button.render(cell);
                    count++;
                });

                element.setAttribute('data-express-count', String(count));
            });

            return this;
        };

        return Wrapper;
    };
});
