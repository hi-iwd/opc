define([
    'jquery',
    'IWD_OneStepCheckout/js/model/config',
    'Magento_Ui/js/lib/view/utils/async'
], function ($, config) {
    'use strict';

    return function (Component) {
        if (!config.isMultiStep()) {
            return Component;
        }

        return Component.extend({
            initialize: function () {
                this._super();
                this.iwdRelocateToSummary();

                return this;
            },

            iwdRelocateToSummary: function () {
                $.async('.opc-block-summary', document.body, function (summary) {
                    $.async('.payment-option.discount-code', document.body, function (discount) {
                        if (discount.parentNode === summary) {
                            return;
                        }

                        summary.querySelectorAll('.payment-option.discount-code').forEach(function (stale) {
                            if (stale !== discount) {
                                stale.parentNode.removeChild(stale);
                            }
                        });

                        summary.appendChild(discount);
                    });
                });
            }
        });
    };
});
