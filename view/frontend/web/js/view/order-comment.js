define([
    'uiComponent',
    'IWD_OneStepCheckout/js/model/order-fields',
    'IWD_OneStepCheckout/js/model/config'
], function (Component, orderFields, config) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'IWD_OneStepCheckout/order-comment'
        },

        initialize: function () {
            var cfg;

            this._super();
            cfg = config.getConfig();

            this.comment = orderFields.comment;
            this.isVisible = !!cfg.orderComment;
            this.label = (cfg.content || {}).orderNotesLabel || '';

            return this;
        }
    });
});
