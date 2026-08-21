define([
    'mage/utils/wrapper',
    'IWD_OneStepCheckout/js/model/order-fields',
    'IWD_OneStepCheckout/js/model/config'
], function (wrapper, orderFields, config) {
    'use strict';

    return function (placeOrderAction) {
        var cfg = config.getConfig();
        if (!config.isActive() || !(cfg.orderComment || cfg.newsletter || cfg.guestToCustomer)) {
            return placeOrderAction;
        }

        return wrapper.wrap(placeOrderAction, function (originalAction, paymentData) {
            if (paymentData) {
                paymentData.extension_attributes = paymentData.extension_attributes || {};

                if (cfg.orderComment) {
                    paymentData.extension_attributes.iwd_osc_comment = orderFields.comment();
                }

                if (cfg.newsletter) {
                    paymentData.extension_attributes.iwd_osc_subscribe = orderFields.subscribe();
                }

                if (cfg.guestToCustomer) {
                    paymentData.extension_attributes.iwd_osc_create_account = orderFields.createAccount();
                }
            }

            return originalAction();
        });
    };
});
