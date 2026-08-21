define([
    'ko'
], function (ko) {
    'use strict';

    return {
        comment: ko.observable(''),
        subscribe: ko.observable(false),
        createAccount: ko.observable(false)
    };
});
