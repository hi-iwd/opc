define([
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/action/select-billing-address',
    'IWD_OneStepCheckout/js/model/config'
], function (quote, selectBillingAddress, config) {
    'use strict';

    return function (dataResolver) {
        var originalApplyBillingAddress = dataResolver.applyBillingAddress.bind(dataResolver);

        dataResolver.applyBillingAddress = function () {
            var shippingAddress,
                args = arguments;

            if ((config.isOnePage() || config.isMultiStep()) && !quote.isVirtual() && !quote.billingAddress()) {
                shippingAddress = quote.shippingAddress();

                if (shippingAddress &&
                    shippingAddress.canUseForBilling &&
                    shippingAddress.canUseForBilling()
                ) {
                    selectBillingAddress(shippingAddress);

                    return;
                }
            }

            return originalApplyBillingAddress.apply(dataResolver, args);
        };

        return dataResolver;
    };
});
