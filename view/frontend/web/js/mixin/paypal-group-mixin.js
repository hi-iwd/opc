define([
    'IWD_OneStepCheckout/js/model/config'
], function (config) {
    'use strict';

    return function (Component) {
        if (!config.isOnePage()) {
            return Component;
        }

        return Component.extend({
            getShowExpress: function () {
                return false;
            }
        });
    };
});
