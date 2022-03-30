define(
    [
        'jquery',
        'Asiabill_Payment/js/view/payment/method'
    ],
    function (  $, Component ) {
        'use strict';

        return Component.extend({
            defaults: {
                self: this,
                template: 'Asiabill_Payment/payment/redirect_form',
                code: "asiabill_directpay"
            },
        });
    }
);


