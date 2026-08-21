define([
    'uiComponent',
    'ko',
    'Magento_Customer/js/model/address-list',
    'Magento_Checkout/js/model/quote',
    'mage/translate'
], function (Component, ko, addressList, quote, $t) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'IWD_OneStepCheckout/section',
            number: '',
            title: '',
            hint: '',
            hintClass: '',
            isFirst: false,
            hintType: '',
            badge: '',
            badgeClass: ''
        },

        /** @inheritdoc */
        initialize: function () {
            var self = this;

            this._super();

            this.savedAddressLabel = ko.computed(function () {
                var count;

                if (self.hintType !== 'saved-addresses') {
                    return '';
                }

                count = addressList().filter(function (address) {
                    return typeof address.getType === 'function'
                        && address.getType() === 'customer-address';
                }).length;

                if (count < 1) {
                    return '';
                }

                return (count === 1
                    ? count + ' ' + $t('saved address')
                    : count + ' ' + $t('saved addresses'))
                    + ' · ' + $t('form hidden while one is selected');
            });

            this.zipSuffix = ko.computed(function () {
                var address, zip;

                if (self.hintType !== 'delivery-zip') {
                    return '';
                }

                address = quote.shippingAddress();
                zip = address && address.postcode ? String(address.postcode).trim() : '';

                return zip !== '' ? ' · ' + $t('zip') + ' ' + zip : '';
            });

            return this;
        }
    });
});
