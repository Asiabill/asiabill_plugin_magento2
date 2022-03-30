/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'asiabill_card',
                component: 'Asiabill_Payment/js/view/payment/method-renderer/card'
            },
            {
                type: 'asiabill_alipay',
                component: 'Asiabill_Payment/js/view/payment/method-renderer/alipay'
            },
            {
                type: 'asiabill_wechat',
                component: 'Asiabill_Payment/js/view/payment/method-renderer/wechat'
            },
            {
                type: 'asiabill_crypto',
                component: 'Asiabill_Payment/js/view/payment/method-renderer/crypto'
            },
            {
                type: 'asiabill_directpay',
                component: 'Asiabill_Payment/js/view/payment/method-renderer/directpay'
            },
            {
                type: 'asiabill_ebanx',
                component: 'Asiabill_Payment/js/view/payment/method-renderer/ebanx'
            },
            {
                type: 'asiabill_giropay',
                component: 'Asiabill_Payment/js/view/payment/method-renderer/giropay'
            },
            {
                type: 'asiabill_ideal',
                component: 'Asiabill_Payment/js/view/payment/method-renderer/ideal'
            },
            {
                type: 'asiabill_p24',
                component: 'Asiabill_Payment/js/view/payment/method-renderer/p24'
            },
            {
                type: 'asiabill_paysafecard',
                component: 'Asiabill_Payment/js/view/payment/method-renderer/paysafecard'
            },
            {
                type: 'asiabill_koreacard',
                component: 'Asiabill_Payment/js/view/payment/method-renderer/koreacard'
            },
            {
                type: 'asiabill_kakaopay',
                component: 'Asiabill_Payment/js/view/payment/method-renderer/kakaopay'
            },
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);