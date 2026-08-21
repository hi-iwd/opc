define([
    'jquery',
    'Magento_Checkout/js/model/quote',
    'IWD_OneStepCheckout/js/model/config',
    'mage/translate',
    'Magento_Ui/js/lib/view/utils/async'
], function ($, quote, config, $t) {
    'use strict';

    return function (Component) {
        if (!config.isMultiStep()) {
            return Component;
        }

        return Component.extend({
            initialize: function () {
                this._super();

                var self = this,
                    content = config.getConfig().content || {};

                $.async('.checkout-payment-method .step-title:not(.iwd-osc-vcontact-title)', this, function (el) {
                    el.textContent = $t('Payment');

                    if (!el.nextElementSibling ||
                        !el.nextElementSibling.classList.contains('iwd-osc-ms-subtitle')
                    ) {
                        var note = document.createElement('p');

                        note.className = 'iwd-osc-ms-subtitle';
                        note.textContent = content.msSubtitlePayment ||
                            $t('All transactions are secure and encrypted. We never store your full card number.');
                        el.insertAdjacentElement('afterend', note);
                    }
                });

                if (quote.isVirtual()) {
                    $.async(
                        '.checkout-payment-method form[data-role=email-with-possible-login],' +
                        ' .checkout-payment-method .iwd-osc-identity',
                        this,
                        function (anchor) {
                            self.iwdInjectContactHeading(anchor, content);
                        }
                    );
                }

                return this;
            },

            /**
             * @param {Element} anchor
             * @param {Object} content
             */
            iwdInjectContactHeading: function (anchor, content) {
                var step = anchor.closest('#checkout-step-payment') || anchor.parentNode;

                if (step.querySelector('.iwd-osc-vcontact-title')) {
                    return;
                }

                var title = document.createElement('div'),
                    note = document.createElement('p');

                title.className = 'step-title iwd-osc-vcontact-title';
                title.textContent = $t('Contact');

                note.className = 'iwd-osc-ms-subtitle';
                note.textContent = $t("We'll send your order confirmation and receipt to this email.");

                anchor.parentNode.insertBefore(title, anchor);
                title.insertAdjacentElement('afterend', note);
            }
        });
    };
});
