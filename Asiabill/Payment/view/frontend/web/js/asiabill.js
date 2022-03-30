define(
    [
        'jquery',
        'Magento_Checkout/js/model/quote',
        'Magento_Customer/js/model/customer',
        'asiabilljs'
    ],
    function ($,quote,customer,asiabillPayment) {
        'use strict';

        return {
            asiabillPay: null,
            formId: 'asiabill-card',
            formWrapperId: 'asiabill-card-element',
            frameId: 'asiabill-card-frame',
            mode: 'pro',
            apiToken: null,
            layout: {
                pageMode: 'block',
                style: {
                    frameMaxHeight: 100,
                    input: {
                        FontSize: 14,
                        FontFamily: '',
                        FontWeight: '',
                        Color: '',
                        ContainerBorder: '1px solid #ddd;',
                        ContainerBg: '',
                        ContainerSh: ''
                    }
                }
            },
            paymentMethodId: null,
            paymentMethodErr: null,

            initElement: function (callback) {
                var message = null;

                try {
                    this.asiabillPay = window.AsiabillPay(this.apiToken);
                    this.asiabillPay.elementInit("payment_steps", {
                        formId: this.formId,
                        formWrapperId: this.formWrapperId,
                        frameId: this.frameId,
                        mode: this.mode,
                        customerId: this.customerId,
                        autoValidate:false,
                        layout: this.layout

                    }).then((res) => {
                        console.log("initRES", res);
                    }).catch((err) => {
                        console.log("initERR", err);
                        callback('initERR');
                    });
                }catch (e) {
                    if (typeof e != "undefined" && typeof e.message != "undefined") {
                        message = 'Could not initialize asiabillPayment: ' + e.message;
                    } else {
                        message = 'Could not initialize asiabillPayment';
                    }
                    callback(message);
                }

            },

            createPaymentMethod: function (callback) {

                let billingAddress = quote.billingAddress();
                let street = billingAddress.street;

                let owner = {
                    "billingDetail": {
                        "address": {
                            "city": billingAddress.city,
                            "country": billingAddress.countryId,
                            "line1": street[0],
                            "line2": street[1],
                            "postalCode": billingAddress.postcode,
                            "state": billingAddress.region
                        },
                        "email": quote.guestEmail? quote.guestEmail: customer.customerData.email,
                        "firstName": billingAddress.firstname,
                        "lastName": billingAddress.lastname ,
                        "phone": billingAddress.telephone
                    },
                    "card": {
                        "cardNo": "",
                        "cardExpireMonth": "",
                        "cardExpireYear": "",
                        "cardSecurityCode": "",
                        "issuingBank": ""
                    }
                };

                let success = false;

                this.asiabillPay.confirmPaymentMethod({
                    apikey: this.apiToken,
                    trnxDetail: owner
                }).then((result) => {
                    callback(result);
                    if( result.data.code === "0" ){
                        success = true
                    }
                });

                return success;

            }

        }
    }
);