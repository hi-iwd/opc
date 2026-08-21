define([
    'Magento_Checkout/js/model/quote',
    'IWD_OneStepCheckout/js/model/config'
], function (quote, config) {
    'use strict';

    return function (Component) {
        if (!config.isOnePage()) {
            return Component;
        }

        return Component.extend({
            initObservable: function () {
                this._super();

                this.isAddressDetailsVisible.subscribe(this.iwdNormalizeBillingView, this);
                quote.billingAddress.subscribe(this.iwdNormalizeBillingView, this);
                quote.shippingAddress.subscribe(this.iwdNormalizeBillingView, this);
                this.iwdNormalizeBillingView();

                return this;
            },

            iwdNormalizeBillingView: function () {
                if (this.canUseShippingAddress() &&
                    this.isAddressDetailsVisible() &&
                    !this.iwdIsSameAsShipping()
                ) {
                    this.isAddressDetailsVisible(false);
                }
            },

            iwdIsSameAsShipping: function () {
                var billing = quote.billingAddress(),
                    shipping = quote.shippingAddress();

                if (quote.isVirtual() || billing == null || shipping == null) { //eslint-disable-line eqeqeq
                    return false;
                }

                return billing.getCacheKey() === shipping.getCacheKey();
            }
        });
    };
});
