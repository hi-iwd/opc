define([
    'jquery',
    'mage/translate',
    'IWD_OneStepCheckout/js/model/config',
    'Magento_Ui/js/lib/view/utils/async'
], function ($, $t, config) {
    'use strict';

    return function (Component) {
        if (!config.isActive()) {
            return Component;
        }

        return Component.extend({
            initialize: function () {
                this._super();

                $.async('label[for$="_cc_number"] > span', this, function (el) {
                    el.textContent = $t('Card Number');
                });

                $.async('label[for$="_cc_cid"] > span', this, function (el) {
                    el.textContent = $t('CVV');
                });

                $.async('label[for$="_enable_vault"] > span', this, function (el) {
                    el.textContent = $t('Save for later use');
                });

                $.async('.payment-method-title label', this, function (label) {
                    if (label.querySelector('.iwd-osc-cc-subtitle')) {
                        return;
                    }

                    var sub = document.createElement('span');

                    sub.className = 'iwd-osc-cc-subtitle';
                    sub.textContent = $t('Visa, Mastercard, Amex, Discover · processed by Braintree');
                    label.appendChild(sub);
                });

                $.async('.payment-method-content', this, function (content) {
                    if (content.querySelector('.iwd-osc-cc-trust')) {
                        return;
                    }

                    var bar = document.createElement('div'),
                        text = document.createElement('span'),
                        badge = document.createElement('span');

                    bar.className = 'iwd-osc-cc-trust';

                    text.className = 'iwd-osc-cc-trust__text';
                    text.textContent = $t(
                        'Card fields are encrypted - your card number never touches the store'
                    );

                    badge.className = 'iwd-osc-cc-trust__badge';
                    badge.textContent = $t('3-D Secure 2');

                    bar.appendChild(text);
                    bar.appendChild(badge);
                    content.appendChild(bar);
                });

                $.async('.ccard .field.choice .label', this, function (label) {
                    if (label.querySelector('.iwd-osc-vault-note')) {
                        return;
                    }

                    var note = document.createElement('span');

                    note.className = 'iwd-osc-vault-note';
                    note.textContent = $t('stored as a secure payment token');
                    label.appendChild(note);
                });

                return this;
            }
        });
    };
});
