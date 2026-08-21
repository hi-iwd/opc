define([
    'jquery',
    'mage/translate',
    'IWD_OneStepCheckout/js/model/config',
    'Magento_Ui/js/lib/view/utils/async'
], function ($, $t, config) {
    'use strict';

    return function (Component) {
        if (!config.isOnePage()) {
            return Component;
        }

        return Component.extend({
            initialize: function () {
                this._super();

                $.async('.estimated-block', this, function (el) {
                    $(el).find('.estimated-label').html($t("Order Summary"));
                });
            }
        });
    };
});
