<?php

namespace Asiabill\Payment\Model;

class PaymentMethod extends \Magento\Payment\Model\Method\AbstractMethod
{

    public static $name = "Magento2";
    public static $version = "2.1.1";

    /**
     * Payment code
     * @var string
     */
    protected $_code = 'asiabill_';

    /**
     * Payment Method feature
     * @var bool
     */
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $_isInitializeNeeded = false;
    protected $_canAuthorize = false;
    protected $_canCapture = false;
    protected $_canCapturePartial = false;
    protected $_canCaptureOnce = false;


    protected $_key;
    protected $_mode;
    protected $_returnUrl;
    protected $_callbackUrl;
    protected $_url;
    protected $_moduleList;
    protected $_session;
    protected $_cookie;
    protected $_cookieMetadataFactory;
    protected $_orderFactory;
    protected $_order;
    protected $_managerInterface;
    protected $_orderSender;
    protected $_priceCurrency;
    protected $_messageManager;
    protected $_quoteManagement;
    protected $_quoteRepository;

    protected $_paymentHelper;
    protected $_paymentLogger;
    protected $_paymentApi;


    public function __construct(
        \Magento\Framework\Url $url,
        \Magento\Checkout\Model\Session $session,
        \Magento\Framework\Stdlib\Cookie\PhpCookieManager $phpCookieManager,
        \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\Message\ManagerInterface $managerInterface,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Magento\Directory\Model\PriceCurrency $priceCurrency,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,

        \Asiabill\Payment\Model\PaymentHelper $paymentHelper,
        \Asiabill\Payment\Model\Paymentlogger $paymentLogger,
        \Asiabill\Payment\Model\PaymentApi $paymentApi,


        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null
    ) {

        parent::__construct($context, $registry, $extensionFactory, $customAttributeFactory, $paymentData, $scopeConfig, $logger, $resource, $resourceCollection, []);

        $this->_mode = $this->getBasicConfigData('asiabill_mode');
        $this->_key = $this->_mode == '0'? $this->getBasicConfigData('test_sign_key') :$this->getConfigData('sign_key');

        $this->_returnUrl = $url->getUrl('asiabill/result',['_secure' => true]);
        $this->_callbackUrl = $url->getUrl('asiabill/webhook',['_secure' => true]);
        $this->_url = $url;
        $this->_session = $session;
        $this->_cookie = $phpCookieManager;
        $this->_cookieMetadataFactory = $cookieMetadataFactory;
        $this->_orderFactory = $orderFactory;
        $this->_managerInterface = $managerInterface;
        $this->_orderSender = $orderSender;
        $this->_priceCurrency = $priceCurrency;
        $this->_messageManager = $messageManager;
        $this->_quoteManagement = $quoteManagement;
        $this->_quoteRepository = $quoteRepository;
        $this->_paymentHelper = $paymentHelper;
        $this->_paymentLogger = $paymentLogger;
        $this->_paymentApi = $paymentApi;
        $this->_paymentLogger->startLogger($this->getBasicConfigData('start_log'));

    }

    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        if (parent::isAvailable($quote) && $quote){
            return true;
        }
        return false;
    }

    public function assignData(\Magento\Framework\DataObject $data)
    {
        parent::assignData($data);
        return $this;
    }

    public function refund(\Magento\Payment\Model\InfoInterface $payment, $refundAmount)
    {
        $refund = false;

        $order = $payment->getOrder();

        if( $order->hasInvoices() ){

            $creditMemo = $payment->getCreditMemo();
            $invoiceId = $creditMemo->getInvoice()->getIncrementId();
            $invoice = $order->prepareInvoice()->loadByIncrementId($invoiceId);

            $refundAmount = $this->_priceCurrency->roundPrice($refundAmount);
            $orderAmount = $this->_priceCurrency->roundPrice($order->getGrandTotal()); // 交易金额

            $refundData = [
                'merNo' => $this->getConfigData('mer_no'),
                'gatewayNo' => $this->getConfigData('gateway_no'),
                'tradeNo' => $invoice->getTransactionId(),
                'refundType' => $refundAmount == $orderAmount ? 1 : 2,
                'tradeAmount' => $orderAmount,
                'refundAmount' => $refundAmount,
                'currency' => $order->getBaseCurrencyCode(),
                'refundReason' => $_POST['creditmemo']['comment_text']?:'refund',
                'remark' => 'refund online on '.$this::$name,
                'merTrackNo' => $invoiceId,
            ];

            $refundData['signInfo'] = $this->_paymentHelper->getRefundSign($refundData,$this->_key);

            $this->_paymentLogger->addLog('refund data: '.json_encode($refundData));

            $result = $this->_paymentApi->requestRefund($this->_mode,$refundData);



            if( empty($result['error']) ){
                $xml = $result['body'];

                $this->_paymentLogger->addLog('refund result: '.$xml);

                $obj = simplexml_load_string($xml,'SimpleXMLElement', LIBXML_NOCDATA);

                $arr = json_decode(json_encode($obj),true);

                if( $arr['applyRefund']['code'] == '00' || $arr['applyRefund']['code'] == '110' ){
                    $refund = true;
                    $this->_messageManager->addSuccessMessage(__('Refund success'));
                    $error_messages = '';
                }else{
                    // 退款失败
                    $this->_messageManager->addErrorMessage(__('Refund error: {code: '. $arr['applyRefund']['code'] .'; description: '.$arr['applyRefund']['description'].'}'));
                    $error_messages = __('Refund error');
                }
            }
            else{
                $this->_messageManager->addErrorMessage($result['error']);
                $error_messages = __('Refund error');
            }

        }
        else{
            $error_messages = __('The refund action is not available.');
        }

        if( !$refund ){
            $this->_paymentLogger->addLog($error_messages);
            throw new \Magento\Framework\Exception\LocalizedException($error_messages);
        }

    }

    public function setCode($code)
    {
        $this->_code = $code;
        $this->_key = $this->_mode == '0'? $this->getBasicConfigData('test_sign_key') :$this->getConfigData('sign_key');;
        return $this;
    }

    public function getRedirectUrl()
    {
        return $this->_url->getUrl('asiabill/payment', ['_secure' => true]);
    }

    public function getCheckoutParameter()
    {
        $order = $this->_session->getLastRealOrder();

        if( $order ){

            $this->_order = $order;
            $gateway_account =  $this->getGatewayAccount();
            $order_info = $this->getOrderInfo();
            $billing = $this->getAddress('billing');
            $shipping = $this->getAddress('shipping');

            $parameter = array_merge($gateway_account,$order_info,$billing,$shipping,[
                'paymentMethod' => $this->_paymentHelper->getPaymentMethod($this->_code),
                'returnUrl' => $this->_returnUrl,
                'callbackUrl' => $this->_callbackUrl,
                'interfaceInfo' => $this::$name,
                'isMobile' => $this->_paymentHelper->isMobile(),
                'remark' => $this->_code
            ]);
            unset($parameter['goodsDetail']);
            unset($parameter['ip']);
            $parameter['signInfo'] = $this->_paymentHelper->getV2Sign($parameter,$this->_key);

            $this->_paymentLogger->addLog($parameter);
            return $parameter;
        }

        return [];

    }

    public function getAction()
    {
        $url = $this->_paymentApi->getActionUrl($this->_mode);
        $this->_paymentLogger->addLog('post to : '.$url);
        return $url;
    }

    protected function getGatewayAccount()
    {
        if( $this->_mode == '0' ){
            return [
                'merNo' => $this->getBasicConfigData('test_mer_no'),
                'gatewayNo' => $this->getBasicConfigData('test_gateway_no'),
            ];
        }

        return [
            'merNo' => $this->getConfigData('mer_no'),
            'gatewayNo' => $this->getConfigData('gateway_no'),
        ];
    }

    protected function getOrderInfo(){

        $goods_detail_1 = $goods_detail_2 = [];

        $i = 0;

        foreach ($this->_order->getAllItems() as $item){

            if( $i < 10 ){
                $goods_detail_1[] = [
                    'productName' => htmlspecialchars($item->getName()),
                    'price' => $item->getPrice(),
                    'quantity' => (int)$item->getQtyOrdered(),
                ];
            }
            $goods_detail_2[] = [
                'goodstitle' => htmlspecialchars($item->getName()),
                'goodscount' => $item->getPrice(),
                'goodsprice' => (int)$item->getQtyOrdered()
            ];
            $i++;
        }

        return [
            'orderNo' => $this->_order->getRealOrderId(),
            'orderCurrency' => $this->_order->getOrderCurrencyCode(),
            'orderAmount' => $this->_order->getTotalDue(),
            'ip' => $this->_order->getXForwardedFor(),
            'goods_detail' => json_encode($goods_detail_1),
            'goodsDetail' => $goods_detail_2
        ];

    }

    /**
     * @param string $type billing || shipping
     * @param string $format v2 || v3
     * @return array
     */
    protected function getAddress($type = 'billing', $format = 'v2')
    {
        if( $type == 'billing' ){
            $address = $this->_order->getBillingAddress();
        }else{
            $address = $this->_order->getShippingAddress();
        }

        if( $address ){

            $first_name = $address->getFirstname();
            $last_name = $address->getLastname();
            $phone = $address->getTelephone();
            $country = $address->getCountryId();
            $state = $address->getRegionCode();
            $city = $address->getCity();
            $street = $address->getStreet();
            $postcode = $address->getPostcode();
            $email = $this->_order->getCustomerEmail();

            if( $format == 'v2' ){
                if( $type == 'billing' ){
                    return [
                        'email' => $email,
                        'firstName' => $first_name,
                        'lastName' => $last_name,
                        'phone' => $phone,
                        'country' => $country,
                        'state' => $state,
                        'city' => $city,
                        'address' => implode(' ', $street),
                        'zip' => $postcode
                    ];
                }
                else{
                    return [
                        'shipFirstName' => $first_name,
                        'shipLastName' => $last_name,
                        'shipPhone' => $phone,
                        'shipCountry' => $country,
                        'shipState' => $state,
                        'shipCity' => $city,
                        'shipAddress' => implode(' ', $street),
                        'shipZip' => $postcode
                    ];
                }
            }
            else{
                return [
                    'firstName' => $first_name,
                    'lastName' => $last_name,
                    'email' => $email,
                    'phone' => $phone,
                    'address' => [
                        'country' => $country,
                        'state'=> $state,
                        'city' => $city,
                        'line1' => isset($street[0])?$street[0]:'',
                        'line2' => isset($street[1])?$street[1]:'',
                        'postalCode' => $postcode
                    ]
                ];
            }
        }

        return [];

    }

    public function getBasicConfigData($field){
        $storeId = $this->getStore();
        $path = 'payment/asiabill_card/' . $field;
        return $this->_scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function checkoutResult($data)
    {

        $this->_paymentLogger->addLog($data);

        $result_sign = $this->_paymentHelper->getResultSign($data,$this->_key);

        $this->_paymentLogger->addLog($result_sign);

        $add_order_status = null;
        $success = 0;
        $massage = '';

        if( $result_sign === $data['signInfo'] ){
            $massage = $data['orderInfo'];
            switch ($data['orderStatus']){
                case '1':
                    $add_order_status = $this->getBasicConfigData('success_order_status');
                    $success = 1;
                    break;
                case "-1":
                case "-2":
                    $add_order_status = $this->getBasicConfigData('pending_order_status');
                    if( !in_array($data['remark'],['asiabill_ali','asiaill_wechat']) ){
                        $success = 1;
                    }
                    break;
                case "0":
                    if( substr($data['orderInfo'],0,5) == 'I0061' ){
                        // 重复支付订单
                        $add_order_status = $this->getBasicConfigData('success_order_status');
                    }else{
                        $add_order_status = $this->getBasicConfigData('failure_order_status');
                    }
                    break;
            }
        }

        $return_result = [
            'status' => $add_order_status,
            'success' => $success,
            'massage' => $massage
        ];

        $this->_paymentLogger->addLog($return_result);

        return $return_result;
    }

    public function verification($data){

        $signInfo = $data['signInfo'];

        $check_sign = '';
        if( isset($data['notifyType']) && ( $data['notifyType'] == 'Void' || $data['notifyType'] == 'Capture' ) ){
            if( $data['operationResult'] == 'Success' ){
                $check_sign = $this->_paymentHelper->getNotifySign($data,$this->_key);
            }
        }else{
            if( $this->_code == 'asiabill_card' && $this->getConfigData('checkout_model') == '0'  ){
                unset($data['signInfo']);
                $check_sign = $this->_paymentHelper->getV3Sign($data,$this->_key);
            }else{
                $check_sign = $this->_paymentHelper->getResultSign($data,$this->_key);
            }
        }

        if( isset($signInfo) && strtoupper($signInfo) === $check_sign ){
            return true;
        }

        return false;

    }

    public function getTransactionStatus($code){
        $order_status = false;
        switch ( $code ){
            case 1:
                $order_status = $this->getBasicConfigData('success_order_status');;
                break;
            case -1:
            case -2:
                $order_status = $this->getBasicConfigData('pending_order_status');
                break;
            case 0:
                if( @substr($_POST['orderInfo'],0,5) == 'I0061' ){
                    // 重复支付订单
                    $order_status = $this->getBasicConfigData('success_order_status');
                }else{
                    $order_status = $this->getBasicConfigData('failure_order_status');
                }
                break;
        }
        return $order_status;
    }

    public function setOrderStatus($data,$order_status)
    {

        $order = $this->_orderFactory->create()->loadByIncrementId($data['orderNo']);

        if( !$order ){
            $this->_paymentLogger->addLog('['. $data['orderNo'] .'] order class is null ');
            return false;
        }

        if( $order->getStatus() == $order_status ){
            return false;
        }

        if( in_array($order->getStatus(),['processing','complete','closed']) ){
            $this->_paymentLogger->addLog('['. $data['orderNo'] .'] order is '.$order->getStatus());
            return false;
        }

        $this->_paymentLogger->addLog('['. $data['orderNo'] .'] set order status is '.$order_status);

        $massage = 'tradeNo:'.$data['tradeNo'].';'.( isset($data['orderInfo'])?'orderInfo:'.$data['orderInfo']:'orderStatus:'.$data['orderStatus'] );

        $order->addStatusToHistory($order_status, $massage);

        if( $order->getStatus() == $this->getBasicConfigData('success_order_status')){

            // invoice
            $invoice = $order->prepareInvoice();
            if ( $invoice->getTotalQty() ) {
                $invoice->getOrder()->setIsInProcess(true);
                $invoice->setTransactionId($data['tradeNo']);
                $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
                $invoice->register();
                $invoice->save();
                $order->addRelatedObject($invoice);
            }else{
                $this->_paymentLogger->addLog('['. $data['orderNo'] .'] Cannot create an invoice without products');
            }

            //发送邮件
            try{
                $this->_orderSender->send($order);
            }catch (\Exception $e){
                $this->_paymentLogger->addLog('['. $data['orderNo'] .'] '.$e->getMessage());
            }
        }

        $order->save();

        return true;
    }

    public function checkData($data){

        // 存在对应关键字段
        if( !isset($data['merNo']) ||
            !isset($data['gatewayNo'])){
            return false;
        }
        // 获取订单对应的支付
        $payment = $this->_orderFactory->create()->loadByIncrementId($data['orderNo'])->getPayment();

        if( is_null($payment) ){
            $this->_paymentLogger->addLog('['. $data['orderNo'] .'] $payment is null');
            return false;
        }

        // 支付方式code
        $code = $payment->getMethodInstance()->getCode();
        $this->setCode($code);

        // 签名校验
        if( !$this->verification($data) ){
            $this->_paymentLogger->addLog('Signature verification failed');
            return false;
        }

        return true;
    }



}