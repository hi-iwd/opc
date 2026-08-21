define([
    'jquery',
    'mage/translate',
    'IWD_OneStepCheckout/js/model/config',
    'Magento_Ui/js/lib/view/utils/async'
], function ($, $t, config) {
    'use strict';

    return function (Component) {
        if (!config.isOnePage() && !config.isMultiStep()) {
            return Component;
        }

        return Component.extend({
            initialize: function () {
                this._super();

                $.async('.shipping-address-item', this, function (el) {
                    var address = this.address(),
                        defaultId = ((window.checkoutConfig || {}).customerData || {}).default_shipping,
                        addressId = address && (address.customerAddressId || address.id);

                    el.setAttribute('data-selected-label', $t('Shipping to this address'));

                    if (addressId && defaultId && String(addressId) === String(defaultId)) {
                        el.classList.add('iwd-default-address');

                        if (!el.querySelector('.iwd-osc-address-default')) {
                            var badge = document.createElement('span'),
                                br = el.querySelector('br');

                            badge.className = 'iwd-osc-address-default';
                            badge.textContent = $t('Default');

                            if (br) {
                                el.insertBefore(badge, br);
                            } else {
                                el.appendChild(badge);
                            }
                        }
                    }
                }.bind(this));

                return this;
            }
        });
    };
});
