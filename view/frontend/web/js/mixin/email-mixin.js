define([
    'jquery',
    'IWD_OneStepCheckout/js/model/config',
    'IWD_OneStepCheckout/js/model/email-flag'
], function ($, config, emailFlag) {
    'use strict';

    return function (Component) {
        if (!config.isOnePage()) {
            return Component;
        }

        return Component.extend({
            checkEmailAvailability: function () {
                this._super();
                $.when(this.isEmailCheckComplete).done(function () {
                    emailFlag.passwordVisibleCustom(false);
                }.bind(this)).fail(function () {
                    emailFlag.passwordVisibleCustom(true);
                }.bind(this)).always(function () {
                }.bind(this));
            }
        });
    };
});
