<?php

namespace Asiabill\Payment\Controller\Webhook;


class Index extends \Magento\Framework\App\Action\Action
    implements \Magento\Framework\App\CsrfAwareActionInterface
{

    protected $_session;
    protected $_method;
    protected $_orderFactory;
    protected $resultFactory;
    protected $_logger;
    public $_data;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $session,
        \Asiabill\Payment\Model\PaymentMethod $method,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Asiabill\Payment\Model\PaymentLogger $paymentLogger
    ){
        parent::__construct($context);
        $this->_session = $session;
        $this->_method = $method;
        $this->_orderFactory = $orderFactory;
        $this->_logger = $paymentLogger;
    }

    public function createCsrfValidationException(
        \Magento\Framework\App\RequestInterface $request
    ): ?\Magento\Framework\App\Request\InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(
        \Magento\Framework\App\RequestInterface $request
    ): ?bool
    {
        return true;
    }

    public function execute()
    {

        $is_success = false;

        if( $this->check() ){
            $post = $this->_data;
            if( $this->_method->getBasicConfigData('webhook') == '1' ){
                $this->_logger->addLog('webhook post : '. json_encode($post) );
                $order_status = $this->_method->getTransactionStatus($post['orderStatus']);
                if( $order_status ){
                    $this->_method->setOrderStatus($post,$order_status);
                }
            }
            $is_success = true;
        }

        if( isset( $_SERVER['REQUEST_METHOD'] )
            && 'POST' === $_SERVER['REQUEST_METHOD']
        ){

            $input = @file_get_contents('php://input');
            $post = json_decode(trim($input),true);

            if( $this->_method->checkData($post) ){
                if( $this->_method->getBasicConfigData('webhook') == '1' ){
                    $this->_logger->addLog('webhook json : '. $input );
                    $_POST['orderInfo'] =  $post['orderInfo'];
                    $order_status = $this->_method->getTransactionStatus($post['orderStatus']);
                    if( $order_status ){
                        $this->_method->setOrderStatus($post,$order_status);
                    }
                }
                $is_success = true;
            }
        }

        if( $is_success ){
            echo 'success';
        }else{
            echo 'waiting';
            http_response_code(202);
        }

        exit();
    }


    public function check(){

        if( !isset($_REQUEST['orderNo']) ||
            !isset($_REQUEST['tradeNo']) ||
            !isset($_REQUEST['merNo']) ||
            !isset($_REQUEST['gatewayNo']) ||
            !isset($_REQUEST['notifyType']) ){
            return false;
        }

        if( !in_array($_REQUEST['notifyType'],['PaymentResult','OrderStatusChanged','Void','Capture']) ){
            echo 'success'; exit();
        }

        $this->_data = $this->getRequest()->getParams();

        if( !$this->_method->checkData($this->_data) ){
            return false;
        }

        return true;
    }


}