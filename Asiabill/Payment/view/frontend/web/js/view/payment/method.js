
define(
    [
        'ko',
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/action/select-payment-method',
        //'Asiabill_Payment/js/action/get-payment-url',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/model/quote',
        'Magento_Customer/js/customer-data',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Ui/js/model/messageList',
        'mage/translate'
    ],
    function (ko, $, Component, placeOrderAction, selectPaymentMethodAction, additionalValidators, quote, customerData, fullScreenLoader, globalMessageList, $t
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                self: this,
                template: 'Asiabill_Payment/payment/redirect_form',
                customRedirect: false
            },
            redirectAfterPlaceOrder: false,

            initObservable: function() {
                this._super();
                this.hasIcons = ko.pureComputed(function() {
                    return window.checkoutConfig.payment[this.code].iconsLocation !== 'none';
                }, this);

                this.iconsRight = ko.pureComputed(function() {
                    return window.checkoutConfig.payment[this.code].iconsLocation === 'right';
                }, this);

                return this;
            },

            getIcon: function() {
                if (typeof this.code === "undefined"){
                    return "";
                }
                return window.checkoutConfig.payment[this.code].icons;
            },

            icons: function() {
                var icons = this.getIcon();
                var icon_path = [];
                for ( var i = 0, len = icons.length; i < len; i++ ){
                    icon_path.push({path: icons[i]});
                }
                return icon_path;
            },

            /** Redirect to Bank */
            placeOrder: function () {
                var self = this;
                var data = self.getData();
                placeOrderAction(data, self.messageContainer)
                .done(function () {
                    if (self.customRedirect){
                        self.redirect(window.checkoutConfig.payment[data.method].redirectUrl);
                    } else {
                        $.mage.redirect(window.checkoutConfig.payment[data.method].redirectUrl);
                    }
                }).error(function (e) {
                    globalMessageList.addErrorMessage({
                        message: $t(e.responseJSON.message)
                    });
                });
                return false;
            }
        });
    }
);
