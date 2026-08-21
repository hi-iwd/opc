define([], function () {
    'use strict';

    function isActive() {
        var cfg = (window.checkoutConfig || {}).iwdOsc || {};

        return !!cfg.enabled;
    }

    function isActiveAndOnePage() {
        var cfg = (window.checkoutConfig || {}).iwdOsc || {};

        return !!cfg.enabled && cfg.layoutMode === 'one_page' && !!cfg.autoSaveShipping;
    }

    function isOnePage() {
        var cfg = (window.checkoutConfig || {}).iwdOsc || {};

        return !!cfg.enabled && cfg.layoutMode === 'one_page';
    }

    function isMultiStep() {
        var cfg = (window.checkoutConfig || {}).iwdOsc || {};

        return !!cfg.enabled && cfg.layoutMode === 'multi_step';
    }

    return {
        isActive: function () {
            return isActive();
        },

        isOnePage: function () {
            return isOnePage();
        },

        isMultiStep: function () {
            return isMultiStep();
        },

        isActiveAndOnePage: function () {
            return isActiveAndOnePage();
        },

        getConfig: function () {
            return (window.checkoutConfig || {}).iwdOsc || {};
        }
    };
});
