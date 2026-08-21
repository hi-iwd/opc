define([
    'uiComponent',
    'ko',
    'uiRegistry',
    'Magento_Customer/js/model/customer',
    'IWD_OneStepCheckout/js/model/email-flag',
    'IWD_OneStepCheckout/js/model/order-fields',
    'IWD_OneStepCheckout/js/model/config'
], function (Component, ko, registry, customer, emailFlag, orderFields, config) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'IWD_OneStepCheckout/create-account'
        },

        initialize: function () {
            var self = this,
                cfg;

            this._super();
            cfg = config.getConfig();

            this.createAccount = orderFields.createAccount;
            this.label = (cfg.content || {}).createAccountLabel || '';

            this.isVisible = ko.observable(!!cfg.guestToCustomer && !customer.isLoggedIn());

            emailFlag.passwordVisibleCustom.subscribe(function (visible) {
                self.isVisible(!visible);
            });

            return this;
        }
    });
});
