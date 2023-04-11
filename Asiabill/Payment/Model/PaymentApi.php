<?php


namespace Asiabill\Payment\Model;


class PaymentApi extends \Magento\Framework\HTTP\Client\Curl
{
    const DOMAIN = 'https://safepay.asiabill.com';
    const SANDBOX = 'https://testpay.asiabill.com';
    const V3 = '/services/v3';

    protected $_ssl = false;
    protected $_error = '';
    protected $_params;

    public function getActionUrl($model = '0')
    {
        return ($model=='0'?self::SANDBOX:self::DOMAIN).'/Interface/V2';
    }

    public function getV3ApiUrl($model = '0',$api = '')
    {
        return ($model=='0'?self::SANDBOX:self::DOMAIN).self::V3.'/'.$api;
    }

    public function getRefundUrl($model = '0')
    {
        if($model == 0){
            return 'https://aci-test.asiabill.com/ACI/servlet/ApplyRefund/V2';
        }

        return 'https://api.asiabill.com/servlet/ApplyRefund/V2';
    }

    public function requestRefund($model,$params)
    {
        $uri = $this->getRefundUrl($model);
        $this->addHeader('Content-type','application/x-www-form-urlencoded');
        $this->_params = is_array($params) ? http_build_query($params) : $params;
        return $this->makeRequest($uri,$params,'POST');
    }

    public function requestV3($model,$interface,$params,$isToken = false,$method = 'POST')
    {
        $uri = $this->getV3ApiUrl($model,$interface);

        $this->addHeader('Content-type','application/json;charset=\'utf-8\'');
        if( $isToken ){
            $this->addHeader('sessionToken',$isToken);
        }
        $this->_params = is_array($params) ? json_encode($params) : $params;
        return $this->makeRequest($uri,$params,$method);
    }

    public function makeRequest($uri,$params,$method = 'POST')
    {

        $this->_ch = curl_init();
        $this->curlOption(CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS | CURLPROTO_FTP | CURLPROTO_FTPS);
        $this->curlOption(CURLOPT_URL, $uri);
        if ($method == 'POST') {
            $this->curlOption(CURLOPT_POST, 1);
            $this->curlOption(CURLOPT_POSTFIELDS, $this->_params);
        } elseif ($method == "GET") {
            $this->curlOption(CURLOPT_HTTPGET, 1);
        } else {
            $this->curlOption(CURLOPT_CUSTOMREQUEST, $method);
        }

        if (count($this->_headers)) {
            $heads = [];
            foreach ($this->_headers as $k => $v) {
                $heads[] = $k . ': ' . $v;
            }
            $this->curlOption(CURLOPT_HTTPHEADER, $heads);
        }

        if (count($this->_cookies)) {
            $cookies = [];
            foreach ($this->_cookies as $k => $v) {
                $cookies[] = "{$k}={$v}";
            }
            $this->curlOption(CURLOPT_COOKIE, implode(";", $cookies));
        }

        if ($this->_timeout) {
            $this->curlOption(CURLOPT_TIMEOUT, $this->_timeout);
        }

        if ($this->_port != 80) {
            $this->curlOption(CURLOPT_PORT, $this->_port);
        }

        if( !$this->_ssl ){
            $this->curlOption(CURLOPT_SSL_VERIFYPEER, false);
            $this->curlOption(CURLOPT_SSL_VERIFYHOST, false);
        }

        $this->curlOption(CURLOPT_RETURNTRANSFER, 1);
        $this->curlOption(CURLOPT_HEADERFUNCTION, [$this, 'parseHeaders']);

        if (count($this->_curlUserOptions)) {
            foreach ($this->_curlUserOptions as $k => $v) {
                $this->curlOption($k, $v);
            }
        }

        $this->_headerCount = 0;
        $this->_responseHeaders = [];
        $this->_responseBody = curl_exec($this->_ch);
        $err = curl_errno($this->_ch);

        if ($err) {
            $this->_error = curl_error($this->_ch);
        }

        curl_close($this->_ch);

        return [
            'error' => $this->_error,
            'headers' => $this->_responseHeaders,
            'body' => $this->_responseBody
        ];

    }

}