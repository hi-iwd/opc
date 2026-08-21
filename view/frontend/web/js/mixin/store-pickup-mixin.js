define([
    'jquery',
    'underscore',
    'IWD_OneStepCheckout/js/model/config'
], function ($, _, config) {
    'use strict';

    var DEBOUNCE_MS = 600,
        SAVE_LOCK_MS = 1500,
        EMAIL_SELECTOR = '#store-selector form[data-role=email-with-possible-login] input[name=username]';

    return function (Component) {
        if (!config.isActiveAndOnePage()) {
            return Component;
        }

        return Component.extend({
            initialize: function () {
                var self;

                this._super();
                self = this;

                this.iwdSaving = false;

                this.iwdPickupAutoSave = _.debounce(function () {
                    if (self.iwdSaving || !self.selectedLocation()) {
                        return;
                    }

                    if (!self.iwdPickupLooksComplete()) {
                        return;
                    }

                    self.iwdSaving = true;
                    self.setPickupInformation();

                    setTimeout(function () {
                        self.iwdSaving = false;
                    }, SAVE_LOCK_MS);
                }, DEBOUNCE_MS);

                this.selectedLocation.subscribe(function () {
                    self.iwdPickupAutoSave();
                });

                $(document)
                    .off('input.iwdOscPickup change.iwdOscPickup blur.iwdOscPickup', EMAIL_SELECTOR)
                    .on('input.iwdOscPickup change.iwdOscPickup blur.iwdOscPickup', EMAIL_SELECTOR, function () {
                        self.iwdPickupAutoSave();
                    });

                return this;
            },

            iwdPickupLooksComplete: function () {
                var email = document.querySelector(EMAIL_SELECTOR);

                if (!this.selectedLocation()) {
                    return false;
                }

                if (!email) {
                    return true;
                }

                return !!email.value.trim();
            }
        });
    };
});
