<?php

namespace Asiabill\Payment\Model\Ui;


class Card extends \Asiabill\Payment\Model\PaymentConfigProvider
{
    protected $code = 'asiabill_card';

    public function getConfig(){

        $config = parent::getConfig();

        if ($this->method->isAvailable($this->checkoutSession->getQuote())) {

            $icons = [];

            $card_icons = $this->method->getConfigData('card_icons');
            if( $card_icons != '' ){
                foreach( explode(',',$card_icons) as $value ){
                    $icons[] = $this->repository->createAsset('Asiabill_Payment::images/'.$value.'.png', [])->getUrl();
                }
            }

            $config['payment'][$this->code]['icons'] = $icons;

            // 站内支付参数
            $checkout_model = $this->method->getConfigData('checkout_model');
            $config['payment'][$this->code]['checkout_model'] = $checkout_model;
            $config['payment'][$this->code]['save_card'] = $this->method->getConfigData('save_card');

            $config['payment'][$this->code]['init'] = [
                'token' => 'null',
                'error' => '',
                'mode' => $this->method->getBasicConfigData('asiabill_mode'),
                'style' => $this->method->getConfigData('elements_style')
            ];

            if( $checkout_model == '0' ){

                $config['payment'][$this->code]['redirectUrl'] = $this->method->getConfirmUrl();

                $token = $this->method->getToken();

                if( empty($token['error']) ){
                    $config['payment'][$this->code]['init']['token'] = $token['token'];
                }else{
                    $config['payment'][$this->code]['init']['error'] = $token['error'];
                }

            }

        }

        return $config;
    }

}
