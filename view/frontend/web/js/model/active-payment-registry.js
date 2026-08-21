define([], function () {
    'use strict';

    var registry = {};

    return {
        set: function (code, component) {
            if (code) {
                registry[code] = component;
            }
        },
        get: function (code) {
            return registry[code];
        }
    };
});
