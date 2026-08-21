define([
    'IWD_OneStepCheckout/js/model/active-payment-registry',
    'IWD_OneStepCheckout/js/model/config'
], function (registry, config) {
    'use strict';

    return function (Component) {
        if (!config.isOnePage() && !config.isMultiStep()) {
            return Component;
        }

        return Component.extend({
            initialize: function () {
                this._super();

                if (typeof this.getCode === 'function') {
                    registry.set(this.getCode(), this);
                }

                return this;
            }
        });
    };
});
