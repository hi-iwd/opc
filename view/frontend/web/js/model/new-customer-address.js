define([
    'underscore',
    'IWD_Opc/js/model/default-post-code-resolver'
], function (_, DefaultPostCodeResolver) {
    'use strict';

    /**
     * @param {Object} addressData
     * Returns new address object
     */
    return function (addressData) {
        var identifier = Date.now(),
            regionId;

        if (addressData.region && addressData.region.region_id) {
            regionId = addressData.region.region_id;
        } else if (addressData.country_id && addressData.country_id === window.checkoutConfig.defaultCountryId) {
            regionId = window.checkoutConfig.defaultRegionId || undefined;
        }

        if(typeof addressData.custom_attributes == 'string' && !addressData.custom_attributes.length) {
            delete addressData['custom_attributes'];
        }

        return {
            email: addressData.email,
            countryId: addressData['country_id'] || addressData.countryId || window.checkoutConfig.defaultCountryId,
            regionId: regionId || addressData.regionId,
            regionCode: (addressData.region) ? addressData.region.region_code : null,
            region: (addressData.region) ? addressData.region.region : null,
            customerId: addressData.customer_id || addressData.customerId,
            street: addressData.street,
            company: addressData.company,
            telephone: addressData.telephone,
            fax: addressData.fax,
            postcode: addressData.postcode ? addressData.postcode : DefaultPostCodeResolver.resolve(),
            city: addressData.city,
            firstname: addressData.firstname,
            lastname: addressData.lastname,
            middlename: addressData.middlename,
            prefix: addressData.prefix,
            suffix: addressData.suffix,
            vatId: addressData.vat_id,
            saveInAddressBook: addressData.save_in_address_book,
            customAttributes: addressData.custom_attributes,
            isDefaultShipping: function () {
                return addressData.default_shipping;
            },
            isDefaultBilling: function () {
                return addressData.default_billing;
            },
            getType: function () {
                return 'new-customer-address';
            },
            getKey: function () {
                return this.getType();
            },
            getCacheKey: function () {
                return this.getType() + identifier;
            },
            isEditable: function () {
                return true;
            },
            canUseForBilling: function () {
                return true;
            }
        }
    }
});
