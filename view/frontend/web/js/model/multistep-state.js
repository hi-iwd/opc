define([
    'ko',
    'Magento_Checkout/js/model/step-navigator'
], function (ko, stepNavigator) {
    'use strict';

    var order = ['information', 'shipping', 'payment'],
        currentStep = ko.observable('information'),
        bound = false;

    function reflect(step) {
        if (document.body) {
            document.body.setAttribute('data-iwd-step', step);
        }
    }

    /**
     * @param {String} code
     * @return {Object|undefined}
     */
    function findStep(code) {
        return stepNavigator.steps().filter(function (step) {
            return step.code === code;
        })[0];
    }

    function bindNative() {
        var payment = findStep('payment');

        if (!payment || typeof payment.isVisible !== 'function') {
            return false;
        }

        payment.isVisible.subscribe(function (visible) {
            if (visible) {
                currentStep('payment');
            } else if (currentStep() === 'payment') {
                currentStep('shipping');
            }
        });

        if (payment.isVisible()) {
            currentStep('payment');
        }

        return true;
    }

    return {
        order: order,
        currentStep: currentStep,

        init: function () {
            if (bound) {
                return;
            }

            bound = true;

            currentStep.subscribe(reflect);
            reflect(currentStep());

            if (!bindNative()) {
                var sub = stepNavigator.steps.subscribe(function () {
                    if (bindNative()) {
                        sub.dispose();
                    }
                });
            }
        },

        /**
         * @param {String} step
         */
        goTo: function (step) {
            if (order.indexOf(step) === -1) {
                return;
            }

            if (step === 'payment') {
                stepNavigator.navigateTo('payment');

                return;
            }

            currentStep(step);

            var payment = findStep('payment');

            if (payment && typeof payment.isVisible === 'function' && payment.isVisible()) {
                stepNavigator.navigateTo('shipping');
            }
        }
    };
});
