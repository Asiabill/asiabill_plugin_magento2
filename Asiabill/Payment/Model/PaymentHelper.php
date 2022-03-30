<?php

namespace Asiabill\Payment\Model;



class PaymentHelper
{

    function __construct(\Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress)
    {
        $this->_remoteAddress = $remoteAddress;
    }

    public function getPaymentMethod($code = '')
    {
        $payment_list = [
            'asiabill_alipay' => 'Alipay',
            'asiabill_card' => 'Credit Card',
            'asiabill_crypto' => 'CryptoPayment',
            'asiabill_directpay' => 'directpay',
            'asiabill_ebanx' => 'Ebanx',
            'asiabill_giropay' => 'giropay',
            'asiabill_ideal' => 'ideal',
            'asiabill_p24' => 'p24',
            'asiabill_paysafecard' => 'paysafecard',
            'asiabill_wechat' => 'WeChatPay',
            'asiabill_koreacard' => 'Korea Local cards',
        ];

        if( key_exists($code,$payment_list) ){
            return $payment_list[$code];
        }
        return '';

    }

    public function isMobile()
    {
        $useragent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        $useragent_commentsblock = preg_match('|\(.*?\)|', $useragent, $matches) > 0 ? $matches[0] : '';


        $mobile_os_list = array('Google Wireless Transcoder','Windows CE','WindowsCE','Symbian','Android','armv6l','armv5','Mobile','CentOS','mowser','AvantGo','Opera Mobi','J2ME/MIDP','Smartphone','Go.Web','Palm','iPAQ');
        $mobile_token_list = array('Profile/MIDP','Configuration/CLDC-','160×160','176×220','240×240','240×320','320×240','UP.Browser','UP.Link','SymbianOS','PalmOS','PocketPC','SonyEricsson','Nokia','BlackBerry','Vodafone','BenQ','Novarra-Vision','Iris','NetFront','HTC_','Xda_','SAMSUNG-SGH','Wapaka','DoCoMo','iPhone','iPod');

        $found_mobile = $this->CheckSubstrs($mobile_os_list, $useragent_commentsblock) || $this->CheckSubstrs($mobile_token_list,$useragent);

        if ($found_mobile){
            return 1;   //手机登录
        }
        return 0;  //电脑登录

    }

    public function CheckSubstrs($substrs, $text){
        foreach($substrs as $substr){
            if(false !== strpos($text, $substr)){
                return true;
            }
        }
        return false;
    }

    public function getV2Sign($data = [],$key = '')
    {
        $string = $data['merNo'] . $data['gatewayNo'] . $data['orderNo'] . $data['orderCurrency'] . $data['orderAmount'] . $data['returnUrl'] . $key;
        return self::signInfo($string);
    }

    public function getV3Sign($data = [], $key = ''){

        // 排序
        ksort($data);
        if( isset( $data['goodsDetail'] ) ) unset($data['goodsDetail']);

        $string = '';
        foreach ($data as $k => $value){
            if( is_array($value) ){
                $value = $this->getV3Sign($value,false);
            }
            if( $value !== '' && $value !== null && $value !== false ){
                // 拼接参数,参与加密的字符转为小写
                $str = trim($value);
                $string .= $str;
            }
        }
        if( $key == '' ){
            return $string;
        }

        return self::signInfo(strtolower($string.$key));
    }

    public function getConfirmSign($data = [], $key = ''){
        $string = $data['merNo'] . $data['gatewayNo'] . $data['orderNo'] . $data['orderCurrency'] . $data['orderAmount'] .$data['customerPaymentMethodId'] ;
        return self::signInfo(strtolower($string.$key));
    }

    public function getResultSign($data = [],$key = '')
    {
        $string = $data['merNo'] . $data['gatewayNo'] . $data['tradeNo'] . $data['orderNo'] . $data['orderCurrency'] . $data['orderAmount'] . $data['orderStatus'] . $data['orderInfo'] . $key;
        return self::signInfo($string);
    }

    public function getNotifySign($data = [],$key = '')
    {
        $string = $data['notifyType'] . $data['operationResult'] . $data['merNo'] . $data['gatewayNo'] . $data['tradeNo'] . $data['orderNo'] . $data['orderCurrency'] . $data['orderAmount'] . $data['orderStatus'] .  $key;
        return self::signInfo($string);
    }

    public function getRefundSign($data = [],$key = '')
    {
        $string = $data['merNo'] . $data['gatewayNo'] . $data['tradeNo'] . $data['currency'] . $data['refundAmount'] . $key;
        return self::signInfo($string);
    }

    private function signInfo($string = '')
    {
        $sign_info = strtoupper(hash("sha256" , $string));
        return $sign_info;
    }

    public function getCusIp(){
        return $this->_remoteAddress->getRemoteAddress();
    }

}