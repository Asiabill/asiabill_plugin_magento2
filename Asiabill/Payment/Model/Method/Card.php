<?php


namespace Asiabill\Payment\Model\Method;

use Magento\Framework\Exception\LocalizedException;

class Card extends \Asiabill\Payment\Model\PaymentMethod
{
    protected $_code = 'asiabill_card';

    protected $_tokenName = 'AsiabillSessionToken';
    protected $_tokenExpire = 800;

    protected $_isInitializeNeeded = true;
    protected $_canAuthorize = false;
    protected $_canCapture = false;
    protected $_canCapturePartial = false;
    protected $_canCaptureOnce = false;

    public function getToken()
    {

        $error = '';
        $token = null;

        $request = parent::getGatewayAccount();

        $request['signInfo'] = $this->_paymentHelper->getV3Sign($request, $this->_key);
        $response = $this->_paymentApi->requestV3($this->_mode, 'sessionToken', $request);

        if (empty($response['error'])) {

            $arr = json_decode($response['body'], true);

            if ($arr['code'] == '0') {

                $publicCookieMetadata = $this->_cookieMetadataFactory->createPublicCookieMetadata()
                    ->setDuration($this->_tokenExpire)
                    ->setPath('/')
                    ->setHttpOnly(false);

                $this->_cookie->setPublicCookie(
                    $this->_tokenName,
                    $arr['data']['sessionToken'],
                    $publicCookieMetadata
                );

                $token = $arr['data']['sessionToken'];

            } else {
                $error = $arr['message'];
            }

        } else {
            $error = $response['error'];
        }

        return [
            'error' => $error,
            'token' => $token
        ];

    }

    public function assignData(\Magento\Framework\DataObject $data)
    {
        parent::assignData($data);

        if( $this->getConfigData('checkout_model') === '1' ){
            return $this;
        }

        $info = $this->getInfoInstance();

        // From Magento 2.0.7 onwards, the data is passed in a different property
        $additionalData = $data->getAdditionalData();
        if ( is_array($additionalData) ){
            $data->setData(array_merge($data->getData(), $additionalData));
        }

        $info->setAdditionalInformation('cc_method_id', $data['cc_method_id']);
        $info->setAdditionalInformation('cc_saved_token', $data['cc_saved_token']);
        $info->setAdditionalInformation('cc_save', $data['cc_save']);

        return $this;
    }

    public function initialize($paymentAction, $stateObject)
    {

        if( $this->getConfigData('checkout_model') == 1 ){
            return $this;
        }

        $info = $this->getInfoInstance();
        $this->_order = $info->getOrder();
        $amount = $this->_order->getGrandTotal();

        if( $this->_order && $amount > 0 ){

            try{
                $response = $this->checkConfirmParameter();
                $this->getToken();
                if( !empty($response['error']) ){
                    $this->_paymentLogger->addLog('confirm error : '.$response['error']);
                    throw new \Exception($response['error']);
                }
                else{

                    $this->_paymentLogger->addLog('response data : ' . $response['body']);
                    $info->setAdditionalInformation('confirm_response', $response['body']);
                    $response_body = json_decode($response['body'],true);

                    $save_trade = null;
                    $save_order = null;
                    $ave_message = '';

                    if( $response_body['code'] != '0' ){
                        if( isset( $response_body['tradeNo'] ) ){
                            $save_trade = $response_body['tradeNo'];
                            $save_order = $response_body['orderNo'];
                            $ave_message = isset($response_body['orderInfo'])?$response_body['orderInfo']:$response_body['message'];
                        }
                    }

                    if( $response_body['code'] == '0' && $response_body['data']['orderStatus'] == '0' ){
                        if( isset( $response_body['data']['tradeNo'] ) ){
                            $save_trade = $response_body['data']['tradeNo'];
                            $save_order = $response_body['data']['orderNo'];
                            $ave_message = $response_body['data']['orderInfo'];
                        }
                    }

                    if( $save_trade ){
                        $order_status = $this->getBasicConfigData('failure_order_status');
                        $this->_paymentLogger->addLog('['. $save_trade .'] ['. $save_order .'] create order status is '.$ave_message);
                        $this->_order->addStatusToHistory($order_status,'tradeNo:'.$save_trade.';orderInfo:'.$ave_message);
                        $this->_order->save();
                    }

                    if( $response_body['code'] == '0' ){
                        if( $response_body['data']['orderStatus'] == '0' ){
                            // 发送银行交易失败
                            throw new \Exception($response_body['data']['orderInfo']);
                        }
                    }
                    else{
                        // code != 0; 挡掉订单，未发送银行
                        throw new \Exception($response_body['message']);
                    }



                }
            }catch (\Exception $e){
                $message = $e->getMessage();
                throw new LocalizedException(__($message));
            }
        }
        else{
            throw new LocalizedException(__('Sorry, unable to process this payment, please try again or use alternative method.'));
        }

        return $this;
    }

    protected function checkConfirmParameter(){

        $info = $this->getInfoInstance();

        $gateway_account =  $this->getGatewayAccount();
        $order_info = $this->getOrderInfo();
        unset($order_info['goods_detail']);
        $shipping = $this->getAddress('shipping','v3');

        $parameter = array_merge($gateway_account,$order_info, [
            'customerPaymentMethodId' => $info->getAdditionalInformation('cc_method_id'),
            'returnUrl' => $this->_returnUrl,
            'callbackUrl' => $this->_callbackUrl,
            'platform' => $this::$name,
            'isMobile' => $this->_paymentHelper->isMobile(),
            'tradeType' => 'web',
            'webSite' => $this->_url->getUrl(),
        ]);

        if( !empty($shipping) ){
            $parameter['shipping'] = $shipping;
        }

        $parameter['signInfo'] = $this->_paymentHelper->getConfirmSign($parameter,$this->_key);
        $this->_paymentLogger->addLog('confirm data : '. json_encode($parameter));
        return  $this->_paymentApi->requestV3($this->_mode,'confirmCharge',$parameter,$this->_cookie->getCookie($this->_tokenName));
    }

    public function getConfirmUrl(){
        return $this->_url->getUrl('asiabill/payment/confirm', ['_secure' => true]);
    }


}


