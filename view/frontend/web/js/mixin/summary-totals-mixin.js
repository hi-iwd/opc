define(['IWD_OneStepCheckout/js/model/config'], function (config) {
    'use strict';

    return function (target) {
        if (!config.isOnePage()) {
            return target;
        }

        return target.extend({
            isFullMode: function () {
                var native = this._super();

                if (native) {
                    return native;
                }

                return !!this.getTotals();
            }
        });
    };
});
