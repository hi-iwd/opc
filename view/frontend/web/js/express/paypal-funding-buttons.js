define([
    'jquery',
    'Magento_Customer/js/customer-data'
], function ($, customerData) {
    'use strict';

    function performCreateOrder(clientConfig) {
        var params = {
            'quote_id': clientConfig.quoteId,
            'customer_id': clientConfig.customerId || '',
            'form_key': clientConfig.formKey,
            button: clientConfig.button
        };

        return $.Deferred(function (deferred) {
            clientConfig.rendererComponent.beforePayment(deferred.resolve, deferred.reject).then(function () {
                $.post(clientConfig.getTokenUrl, params).done(function (res) {
                    clientConfig.rendererComponent.afterPayment(res, deferred.resolve, deferred.reject);
                }).fail(function (jqXHR, textStatus, err) {
                    clientConfig.rendererComponent.catchPayment(err, deferred.resolve, deferred.reject);
                });
            });
        }).promise();
    }

    function performOnApprove(clientConfig, data, actions) {
        var params = {
            paymentToken: data.orderID,
            payerId: data.payerID,
            paypalFundingSource: customerData.get('paypal-funding-source'),
            'form_key': clientConfig.formKey
        };

        return $.Deferred(function (deferred) {
            clientConfig.rendererComponent.beforeOnAuthorize(deferred.resolve, deferred.reject, actions)
                .then(function () {
                    $.post(clientConfig.onAuthorizeUrl, params).done(function (res) {
                        if (res.success === false) {
                            clientConfig.rendererComponent.catchOnAuthorize(res, deferred.resolve, deferred.reject);

                            return;
                        }
                        clientConfig.rendererComponent.afterOnAuthorize(res, deferred.resolve, deferred.reject, actions);
                        customerData.set('paypal-funding-source', '');
                    }).fail(function (jqXHR, textStatus, err) {
                        clientConfig.rendererComponent.catchOnAuthorize(err, deferred.resolve, deferred.reject);
                        customerData.set('paypal-funding-source', '');
                    });
                });
        }).promise();
    }

    /**
     * @param {Object} paypal
     * @param {Object} clientConfig
     * @param {String} fundingSource
     * @return {Object}
     */
    return function (paypal, clientConfig, fundingSource) {
        var cfgStyles = clientConfig.styles || {},
            style = {
                height: cfgStyles.height || 48
            };

        if (cfgStyles.shape) {
            style.shape = cfgStyles.shape;
        }

        if (cfgStyles.color && fundingSource !== 'venmo') {
            style.color = cfgStyles.color;
        }

        return paypal.Buttons({
            style: style,
            fundingSource: fundingSource,

            onInit: function (data, actions) {
                clientConfig.rendererComponent.validate(actions);
            },

            createOrder: function () {
                return performCreateOrder(clientConfig);
            },

            onApprove: function (data, actions) {
                performOnApprove(clientConfig, data, actions);
            },

            onClick: function (data) {
                customerData.set('paypal-funding-source', data.fundingSource || fundingSource);
                clientConfig.rendererComponent.validate();
                clientConfig.rendererComponent.onClick();
            },

            onCancel: function (data, actions) {
                clientConfig.rendererComponent.onCancel(data, actions);
            },

            onError: function (err) {
                clientConfig.rendererComponent.onError(err);
            }
        });
    };
});
