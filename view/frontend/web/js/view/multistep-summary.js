define([
    'uiComponent',
    'Magento_Checkout/js/model/quote',
    'Magento_Customer/js/model/customer',
    'Magento_Customer/js/customer-data',
    'Magento_Checkout/js/checkout-data',
    'IWD_OneStepCheckout/js/model/multistep-state',
    'IWD_OneStepCheckout/js/model/config'
], function (Component, quote, customer, customerData, checkoutData, state, config) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'IWD_OneStepCheckout/multistep-summary'
        },

        initialize: function () {
            this._super();
            this.currentStep = state.currentStep;
            this.countryData = customerData.get('directory-data');

            return this;
        },

        isMultiStep: function () {
            return config.isMultiStep();
        },

        showInformation: function () {
            if (quote.isVirtual()) {
                return false;
            }

            return this.currentStep() === 'shipping' || this.currentStep() === 'payment';
        },

        showShippingMethod: function () {
            if (quote.isVirtual()) {
                return false;
            }

            return this.currentStep() === 'payment';
        },

        getEmail: function () {
            if (customer.isLoggedIn()) {
                return customer.customerData.email || '';
            }

            return quote.guestEmail || checkoutData.getInputFieldEmailValue() || '';
        },

        getContact: function () {
            var address = quote.shippingAddress(),
                email = this.getEmail(),
                phone = address ? address.telephone : '';

            return phone ? (email + ' · ' + phone) : email;
        },

        getCountryName: function (countryId) {
            var data = this.countryData();

            return data && data[countryId] ? data[countryId].name : countryId;
        },

        getAddress: function () {
            var address = quote.shippingAddress(),
                parts = [],
                street,
                cityLine;

            if (!address) {
                return '';
            }

            street = (address.street || []).filter(Boolean).join(', ');

            if (street) {
                parts.push(street);
            }

            cityLine = [
                address.city,
                [address.regionCode || address.region, address.postcode].filter(Boolean).join(' ')
            ].filter(Boolean).join(', ');

            if (cityLine) {
                parts.push(cityLine);
            }

            if (address.countryId) {
                parts.push(this.getCountryName(address.countryId));
            }

            return parts.join(' · ');
        },

        getMethod: function () {
            var method = quote.shippingMethod();

            if (!method) {
                return '';
            }

            return [method.carrier_title, method.method_title].filter(Boolean).join(' · ');
        },

        editInformation: function () {
            state.goTo('information');
        },

        editShipping: function () {
            state.goTo('shipping');
        }
    });
});
