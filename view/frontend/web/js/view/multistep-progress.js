define([
    'ko',
    'uiComponent',
    'Magento_Checkout/js/model/quote',
    'IWD_OneStepCheckout/js/model/multistep-state',
    'IWD_OneStepCheckout/js/model/config',
    'mage/translate'
], function (ko, Component, quote, state, config, $t) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'IWD_OneStepCheckout/multistep-progress'
        },

        initialize: function () {
            var self = this;

            this._super();
            this.currentStep = state.currentStep;
            this.steps = [
                {code: 'information', label: $t('Information')},
                {code: 'shipping', label: $t('Shipping')},
                {code: 'payment', label: $t('Payment')}
            ];
            this.totalSteps = this.steps.length;

            //  Compact "Step X of N" + current label, for the mobile bar.
            this.currentIndex = ko.computed(function () {
                return state.order.indexOf(state.currentStep()) + 1;
            });
            this.currentLabel = ko.computed(function () {
                var code = state.currentStep(),
                    match = self.steps.filter(function (step) {
                        return step.code === code;
                    })[0];

                return match ? match.label : '';
            });

            state.init();

            return this;
        },

        isMultiStep: function () {
            return config.isMultiStep() && !quote.isVirtual();
        },

        /**
         * @param {String} code
         * @return {String}
         */
        stepClass: function (code) {
            var current = state.order.indexOf(state.currentStep()),
                index = state.order.indexOf(code);

            if (index === current) {
                return '_active';
            }

            return index < current ? '_complete' : '_upcoming';
        },

        /**
         * @param {String} code
         * @return {Boolean}
         */
        canNavigate: function (code) {
            return state.order.indexOf(code) < state.order.indexOf(state.currentStep());
        },

        /**
         * @param {Object} step
         * @return {Boolean}
         */
        navigate: function (step) {
            if (this.canNavigate(step.code)) {
                state.goTo(step.code);
            }

            return true;
        },

        /**
         * @param {Object} step
         * @param {Event} event
         * @return {Boolean}
         */
        navigateKey: function (step, event) {
            if (event.keyCode === 13 || event.keyCode === 32) {
                this.navigate(step);

                return false;
            }

            return true;
        }
    });
});
