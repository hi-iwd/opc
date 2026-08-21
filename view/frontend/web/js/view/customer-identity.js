define([
    'uiComponent',
    'Magento_Customer/js/model/customer',
    'IWD_OneStepCheckout/js/model/config'
], function (Component, customer, config) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'IWD_OneStepCheckout/customer-identity'
        },

        initialize: function () {
            var data = (window.checkoutConfig || {}).customerData || {},
                first = (data.firstname || '').trim(),
                last = (data.lastname || '').trim(),
                cfg = config.getConfig();

            this._super();

            this.isVisible = customer.isLoggedIn();
            this.customerName = (first + ' ' + last).trim();
            this.customerEmail = data.email || '';
            this.initials = ((first.charAt(0) || '') + (last.charAt(0) || '')).toUpperCase();
            this.signOutUrl = cfg.signOutUrl || '';

            return this;
        }
    });
});
