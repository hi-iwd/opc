define([
    'uiComponent',
    'ko',
    'Magento_Checkout/js/model/quote',
    'Magento_Catalog/js/price-utils',
    'IWD_OneStepCheckout/js/model/active-payment-registry',
    'IWD_OneStepCheckout/js/model/config',
    'mage/translate'
], function (Component, ko, quote, priceUtils, registry, config, $t) {
    'use strict';

    function relocatableMethods() {
        var cfg = config.getConfig();

        if (cfg && Array.isArray(cfg.relocatableMethods)) {
            return cfg.relocatableMethods;
        }

        return [];
    }

    return Component.extend({
        defaults: {
            template: 'IWD_OneStepCheckout/summary-place-order'
        },

        initialize: function () {
            var cfg;

            this._super();
            cfg = config.getConfig();

            this.autoSave = !!cfg.autoSaveShipping && config.isOnePage();
            this.secureBadge = (cfg.content || {}).secureBadge || '';
            this.isMultiStep = config.isMultiStep();

            this.hasMethod = ko.computed(function () {
                return !!quote.paymentMethod();
            });

            this.canPlace = ko.computed(function () {
                var method = quote.paymentMethod();

                return !!method && relocatableMethods().indexOf(method.method) !== -1;
            });

            this.showButton = ko.computed(function () {
                return this.canPlace() || !this.hasMethod();
            }, this);

            this.showHint = ko.computed(function () {
                return this.hasMethod() && !this.canPlace();
            }, this);

            this.grandTotal = ko.computed(function () {
                var totals = quote.totals();

                if (!totals) {
                    return '';
                }

                return priceUtils.formatPriceLocale(totals.grand_total, quote.getPriceFormat());
            });

            this.ctaLabel = ko.computed(function () {
                var word = this.isMultiStep ? $t('Pay') : $t('Place Order'),
                    total = this.grandTotal();

                if (!total) {
                    return word;
                }

                return this.isMultiStep ? (word + ' ' + total) : (word + ' · ' + total);
            }, this);

            this.noteText = this.isMultiStep ?
                $t('Total includes tax and shipping') :
                (this.autoSave ? $t('Auto-saved as you typed') : '');
            this.showNote = this.isMultiStep || this.autoSave;

            this.canPlace.subscribe(this.toggleRelocatedClass, this);
            this.toggleRelocatedClass(this.canPlace());

            return this;
        },

        /**
         * @param {Boolean} relocated
         */
        toggleRelocatedClass: function (relocated) {
            if (document.body && document.body.classList) {
                document.body.classList.toggle('iwd-osc-relocated', !!relocated);
            }
        },

        placeOrder: function () {
            var method = quote.paymentMethod(),
                active = method && registry.get(method.method);
            if (active && typeof active.placeOrder === 'function') {
                active.placeOrder();

                return;
            }

            this.scrollToIncomplete();
        },

        scrollToIncomplete: function () {
            var target;

            if (this.isMultiStep) {
                target = document.querySelector('.checkout-payment-method');
            } else {
                target = quote.shippingMethod() ?
                    document.getElementById('payment') :
                    document.getElementById('shipping');
            }

            if (target && target.offsetParent !== null && typeof target.scrollIntoView === 'function') {
                target.scrollIntoView({behavior: 'smooth', block: 'start'});
            }
        }
    });
});
