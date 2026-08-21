define([
    'jquery',
    'Magento_Customer/js/model/customer',
    'Magento_Checkout/js/model/quote',
    'IWD_OneStepCheckout/js/model/multistep-state',
    'IWD_OneStepCheckout/js/model/config',
    'mage/translate',
    'Magento_Ui/js/lib/view/utils/async'
], function ($, customer, quote, state, config, $t) {
    'use strict';

    var LOGIN_FORM = 'form[data-role=email-with-possible-login]';

    return function (Component) {
        if (!config.isMultiStep() || quote.isVirtual()) {
            return Component;
        }

        return Component.extend({
            initialize: function () {
                this._super();
                state.init();
                this.iwdInjectControls();

                return this;
            },

            iwdInjectControls: function () {
                var self = this,
                    content = config.getConfig().content || {};

                $.async('#shipping .step-title', this, function (el) {
                    self.iwdApplyHeading(
                        el,
                        'Information',
                        content.msSubtitleInformation ||
                            $t("Tell us where to send your order. We'll send your receipt to the email you provide.")
                    );
                });

                $.async('#opc-shipping_method .step-title', this, function (el) {
                    self.iwdApplyHeading(
                        el,
                        'Shipping method',
                        content.msSubtitleShipping || $t("Choose how you'd like your order delivered.")
                    );
                });

                $.async('#checkout-step-shipping form.form-login', this, function (el) {
                    self.iwdInsertGroupHeader(el, 'Contact');
                });

                $.async('#checkout-step-shipping .iwd-osc-identity', this, function (el) {
                    self.iwdInsertGroupHeader(el, 'Contact');
                });

                $.async('#checkout-step-shipping .shipping-address-items', this, function (el) {
                    self.iwdInsertGroupHeader(el, 'Shipping address');
                });

                $.async('#checkout-step-shipping #co-shipping-form', this, function (el) {
                    if (el.closest('#opc-new-shipping-address')) {
                        return;
                    }
                    self.iwdInsertGroupHeader(el, 'Shipping address');
                });

                $.async('#checkout-step-shipping', this, function (el) {
                    if (el.querySelector('.iwd-osc-ms-actions')) {
                        return;
                    }

                    var wrap = document.createElement('div'),
                        back = document.createElement('a'),
                        next = document.createElement('button');

                    wrap.className = 'iwd-osc-ms-actions';

                    back.className = 'iwd-osc-ms-actions__back';
                    back.href = config.getConfig().cartUrl || '';
                    back.innerHTML = '<span>' + $t('Back to cart') + '</span>';

                    next.type = 'button';
                    next.className = 'iwd-osc-ms-actions__next action primary';
                    next.textContent = $t('Continue to shipping');
                    next.addEventListener('click', function () {
                        self.iwdContinueToShipping();
                    });

                    wrap.appendChild(back);
                    wrap.appendChild(next);
                    el.appendChild(wrap);
                });

                $.async('#shipping-method-buttons-container', this, function (el) {
                    var span = el.querySelector('button[data-role=opc-continue] span');

                    if (span) {
                        span.textContent = $t('Continue to payment');
                    }

                    if (!el.querySelector('.iwd-osc-ms-back-info')) {
                        var back = document.createElement('button');

                        back.type = 'button';
                        back.className = 'iwd-osc-ms-back-info action';
                        back.innerHTML = '<span>' + $t('Return to information') + '</span>';
                        back.addEventListener('click', function () {
                            state.goTo('information');
                        });
                        el.insertBefore(back, el.firstChild);
                    }
                });
            },

            /**
             * @param {Element} titleEl
             * @param {String} title
             * @param {String} subtitle
             */
            iwdApplyHeading: function (titleEl, title, subtitle) {
                titleEl.textContent = $t(title);

                if (!titleEl.nextElementSibling ||
                    !titleEl.nextElementSibling.classList.contains('iwd-osc-ms-subtitle')
                ) {
                    var note = document.createElement('p');

                    note.className = 'iwd-osc-ms-subtitle';
                    note.textContent = subtitle;
                    titleEl.insertAdjacentElement('afterend', note);
                }
            },

            /**
             * @param {Element} beforeEl
             * @param {String} text
             */
            iwdInsertGroupHeader: function (beforeEl, text) {
                var step = beforeEl.closest('#checkout-step-shipping') || beforeEl.parentNode,
                    existing = step.querySelectorAll('.iwd-osc-ms-group'),
                    i;

                for (i = 0; i < existing.length; i++) {
                    if (existing[i].getAttribute('data-iwd-group') === text) {
                        return;
                    }
                }

                var header = document.createElement('h3');

                header.className = 'iwd-osc-ms-group';
                header.setAttribute('data-iwd-group', text);
                header.textContent = $t(text);
                beforeEl.parentNode.insertBefore(header, beforeEl);
            },

            iwdContinueToShipping: function () {
                var emailOk = customer.isLoggedIn();

                if (!customer.isLoggedIn()) {
                    $(LOGIN_FORM).validation();
                    emailOk = Boolean($(LOGIN_FORM + ' input[name=username]').valid());
                }

                if (this.isFormInline) {
                    this.source.set('params.invalid', false);
                    this.triggerShippingDataValidateEvent();

                    if (this.source.get('params.invalid')) {
                        this.focusInvalid();

                        return;
                    }
                }

                if (!emailOk) {
                    $(LOGIN_FORM + ' input[name=username]').trigger('focus');

                    return;
                }

                state.goTo('shipping');
            }
        });
    };
});
