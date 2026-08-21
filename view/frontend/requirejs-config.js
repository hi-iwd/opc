var config = {
    config: {
        mixins: {
            'Magento_Checkout/js/model/step-navigator': {
                'IWD_OneStepCheckout/js/mixin/step-navigator-mixin': true
            },
            'Magento_Checkout/js/view/payment/default': {
                'IWD_OneStepCheckout/js/mixin/payment-default-mixin': true
            },
            'Magento_Checkout/js/view/payment': {
                'IWD_OneStepCheckout/js/mixin/multistep-payment-mixin': true
            },
            'Magento_Checkout/js/view/shipping': {
                'IWD_OneStepCheckout/js/mixin/shipping-mixin': true,
                'IWD_OneStepCheckout/js/mixin/multistep-shipping-mixin': true
            },
            'Magento_InventoryInStorePickupFrontend/js/view/store-selector': {
                'IWD_OneStepCheckout/js/mixin/store-pickup-mixin': true
            },
            'Magento_Checkout/js/action/place-order': {
                'IWD_OneStepCheckout/js/mixin/place-order-mixin': true
            },
            'Magento_Checkout/js/view/summary/abstract-total': {
                'IWD_OneStepCheckout/js/mixin/summary-totals-mixin': true
            },
            'Magento_Checkout/js/view/shipping-address/address-renderer/default': {
                'IWD_OneStepCheckout/js/mixin/address-renderer-mixin': true
            },
            'Magento_Checkout/js/view/form/element/email': {
                'IWD_OneStepCheckout/js/mixin/email-mixin': true
            },
            'Magento_SalesRule/js/view/payment/discount': {
                'IWD_OneStepCheckout/js/mixin/discount-relocate-mixin': true
            },
            'Magento_Checkout/js/view/billing-address': {
                'IWD_OneStepCheckout/js/mixin/billing-address-mixin': true
            },
            'Magento_Checkout/js/model/checkout-data-resolver': {
                'IWD_OneStepCheckout/js/mixin/checkout-data-resolver-mixin': true
            },
            'Magento_Checkout/js/view/estimation': {
                'IWD_OneStepCheckout/js/mixin/estimation-mixin': true
            },
            'PayPal_Braintree/js/view/payment/method-renderer/hosted-fields': {
                'IWD_OneStepCheckout/js/mixin/braintree-trust-mixin': true
            },
            'Magento_Paypal/js/in-context/express-checkout-wrapper': {
                'IWD_OneStepCheckout/js/mixin/paypal-express-buttons-mixin': true
            },
            'Magento_PaymentServicesPaypal/js/view/payment/group': {
                'IWD_OneStepCheckout/js/mixin/paypal-group-mixin': true
            }
        }
    }
};
