define([
    'uiComponent',
    'IWD_OneStepCheckout/js/model/order-fields',
    'IWD_OneStepCheckout/js/model/config'
], function (Component, orderFields, config) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'IWD_OneStepCheckout/newsletter-subscribe'
        },

        initialize: function () {
            var cfg;

            this._super();
            cfg = config.getConfig();
            this.subscribe = orderFields.subscribe;
            this.isVisible = !!cfg.newsletter;
            this.label = (cfg.content || {}).newsletterLabel || '';

            return this;
        }
    });
});
