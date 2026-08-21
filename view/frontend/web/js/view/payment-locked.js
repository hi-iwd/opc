define([
    'uiComponent',
    'ko',
    'Magento_Checkout/js/model/payment-service',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/checkout-data',
    'Magento_Customer/js/model/customer',
    'IWD_OneStepCheckout/js/model/config'
], function (Component, ko, paymentService, quote, checkoutData, customer, config) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'IWD_OneStepCheckout/payment-locked'
        },

        initialize: function () {
            var self, cfg;

            this._super();
            self = this;
            cfg = config.getConfig();

            this.autoSave = !!cfg.autoSaveShipping;

            var c = cfg.content || {};

            this.lockedTitle = c.lockedTitle || '';

            var rawBody = (this.autoSave ? c.lockedBody : c.lockedBodyPlain) || '',
                links = {'%1': c.lockedLinkShipping || '', '%2': c.lockedLinkDelivery || ''};

            this.bodyParts = rawBody.split(/(%1|%2)/).map(function (seg) {
                return seg === '%1' || seg === '%2'
                    ? {text: links[seg], bold: true}
                    : {text: seg, bold: false};
            });

            this.stepContact = c.lockedStepContact || '';
            this.stepShipping = c.lockedStepShipping || '';
            this.stepDelivery = c.lockedStepDelivery || '';
            this.stepStatus = c.lockedStatus || '';

            this.isLocked = ko.computed(function () {
                return paymentService.getAvailablePaymentMethods().length === 0;
            });

            this.contactDone = ko.computed(function () {
                var email;

                if (customer.isLoggedIn()) {
                    return true;
                }

                quote.shippingAddress();
                email = quote.guestEmail || checkoutData.getValidatedEmailValue();

                return !!email;
            });

            this.deliveryDone = ko.computed(function () {
                return !!quote.shippingMethod();
            });

            this.isLocked.subscribe(function (locked) {
                self.toggleBodyClass(locked);
            });
            this.toggleBodyClass(this.isLocked());

            return this;
        },

        toggleBodyClass: function (locked) {
            if (document.body && document.body.classList) {
                document.body.classList.toggle('iwd-osc-payment-pending', !!locked);
            }
        }
    });
});
