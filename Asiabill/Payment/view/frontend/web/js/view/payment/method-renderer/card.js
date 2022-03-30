define(
    [
        'ko',
        'jquery',
        'Magento_Ui/js/model/messageList',
        'Magento_Checkout/js/model/quote',
        'Magento_Customer/js/model/customer',
        'Asiabill_Payment/js/view/payment/method',
        'Magento_Checkout/js/action/place-order',
        'Asiabill_Payment/js/asiabill',
        'mage/translate'
    ],
    function ( ko, $, globalMessageList, quote, customer, Component, placeOrderAction, asiabill, $t ) {
        'use strict';

        addEventListener("getErrorMessage", e => {
            cardError(e.detail.errorMessage);
        });

        let cardError = (err) => {
            if( err ){
                $('#asiabill-card-errors').html(err).removeClass('hide');
            }else {
                $('#asiabill-card-errors').html('').addClass('hide');
            }
        };
        
        return Component.extend({
            defaults: {
                self: this,
                template: 'Asiabill_Payment/payment/card_form',
                code: "asiabill_card"
            },

            initObservable: function () {
                let card_config = this.getConfig();
                let _self = this;

                this._super().observe([
                    'asiabillPaymentDisable',
                    'asiabillInitError',
                    'asiabillPayError',
                    'asiabillMethodId'
                ]);

                this.showError = ko.pureComputed(function () {

                    if( _self.asiabillPayError() ){
                        return _self.asiabillPayError();
                    }

                    if( _self.asiabillInitError() ){
                        return _self.asiabillInitError()
                    }

                });

                this.checkoutModel = ko.pureComputed(function () {
                    if( _self.asiabillInitError() ){
                        return false
                    }
                    return card_config.checkout_model === '0'

                });

                this.saveCard = ko.pureComputed(function() {
                    if( _self.asiabillInitError() ){
                        return false;
                    }
                    return customer.isLoggedIn()
                        && card_config.checkout_model === '0'
                        && card_config.save_card !== '0';
                }, this);

                this.saveChecked = ko.pureComputed(function() {
                    return card_config.save_card === '1'
                }, this);

                return this;
            },

            getConfig: function(){
                return window.checkoutConfig.payment.asiabill_card;
            },

            isPlaceOrderEnabled: function () {

                if (this.asiabillInitError()){
                    return false;
                }

                if (this.asiabillPaymentDisable()){
                    return false;
                }

                return true;
            },

            cardFormInit: function () {

                var formId = asiabill.formId;

                if ($('#'+formId) === null) {
                    return;
                }

                let params = this.getConfig().init;

                if( params.token === 'null' ){
                    this.asiabillInitError(params.error);
                    return;
                }

                asiabill.mode = params.mode === '1' ? 'pro' : 'uat';
                asiabill.apiToken = params.token;
                asiabill.layout.pageMode = params.style === '0' ? 'inner' : 'block';
                asiabill.layout.style.frameMaxHeight = params.style === '0' ? 44 : 100;

                let _self = this;
                asiabill.initElement(function (err) {
                    console.log(err);
                    if( err ){
                        _self.asiabillInitError(err);
                    }else {
                        _self.asiabillInitError(null);
                    }
                });
            },

            placeOrder: function () {

                if( this.getConfig().checkout_model === '1'  ){
                    this.getPlaceOrderCheckoutObject();
                    return false;
                }

                cardError(null);
                this.asiabillPayError(null);

                let _self = this;
                asiabill.createPaymentMethod(function (result) {
                    if( result.data.code === "0" ){
                        _self.asiabillMethodId(result.data.data.customerPaymentMethodId);
                        _self.getPlaceOrderCheckoutObject();
                    }else {
                        _self.asiabillPayError(result.data.message);
                    }
                });

                return false;
            },

            getPlaceOrderCheckoutObject: function () {
                var _self = this;
                var data = _self.getData();
                placeOrderAction(data, _self.messageContainer)
                    .done(function () {
                        if (_self.customRedirect){
                            _self.redirect(_self.getConfig().redirectUrl);
                        } else {
                            $.mage.redirect(_self.getConfig().redirectUrl);
                        }
                    })
                    .error(function (e) {
                        globalMessageList.addErrorMessage({
                            message: $t(e.responseJSON.message)
                        });
                    });
            },

            getData: function() {
                return {
                    'method': this.item.method,
                    'additional_data': {
                        'cc_method_id': this.asiabillMethodId(),
                        'cc_saved_token': 'new',
                        'cc_save': true
                    }
                };
            },
            
        });
    }
);

