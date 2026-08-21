define([
    'jquery',
    'underscore',
    'Magento_Checkout/js/model/quote',
    'mage/translate',
    'IWD_OneStepCheckout/js/model/config',
    'Magento_Ui/js/lib/view/utils/async'
], function ($, _, quote, $t, config) {
    'use strict';

    var DEBOUNCE_MS = 600,
        SAVE_LOCK_MS = 1500;

    return function (Component) {
        if (!config.isActiveAndOnePage()) {
            return Component;
        }

        return Component.extend({
            initialize: function () {
                var self;

                this._super();
                self = this;

                if (document.body && document.body.classList) {
                    document.body.classList.add('iwd-osc-autosave');
                }

                this.iwdSaving = false;

                this.iwdAutoSave = _.debounce(function () {
                    if (self.iwdSaving || !quote.shippingMethod()) {
                        return;
                    }

                    if (typeof self.isNextBtnVisible === 'function' && !self.isNextBtnVisible()) {
                        return;
                    }

                    if (!self.iwdShippingLooksComplete()) {
                        return;
                    }

                    self.iwdSaving = true;
                    self.setShippingInformation();

                    setTimeout(function () {
                        self.iwdSaving = false;
                    }, SAVE_LOCK_MS);
                }, DEBOUNCE_MS);

                quote.shippingMethod.subscribe(function () {
                    self.iwdAutoSave();
                });

                $(document)
                    .off('input.iwdOsc change.iwdOsc blur.iwdOsc', '#co-shipping-form :input')
                    .on('input.iwdOsc change.iwdOsc blur.iwdOsc', '#co-shipping-form :input', function () {
                        self.iwdAutoSave();
                    });
                $.async('.action-show-popup', this, function (el) {
                    el.setAttribute('data-iwd-hint', $t('Opens the address form'));
                });
                return this;
            },

            iwdShippingLooksComplete: function () {
                var email = document.querySelector('#customer-email'),
                    form = document.querySelector('#co-shipping-form'),
                    complete = true;

                if (email && email.offsetParent !== null && !email.value) {
                    return false;
                }

                if (!form) {
                    return false;
                }

                form.querySelectorAll('input, select, textarea').forEach(function (el) {
                    var required;

                    if (el.offsetParent === null) {
                        return;
                    }

                    required = el.getAttribute('aria-required') === 'true'
                        || el.classList.contains('required-entry')
                        || (el.getAttribute('data-validate') || '').indexOf('required') !== -1
                        || el.name === 'street[0]';

                    if (required && !el.value) {
                        complete = false;
                    }
                });

                return complete;
            }
        });
    };
});
