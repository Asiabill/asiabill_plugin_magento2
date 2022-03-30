<?php

namespace Asiabill\Payment\Controller\Result;


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
        $redirect = '';

        if( $this->check() ){

            $session_name = session_name();
            header('Set-Cookie: '.$session_name.' = '.$_COOKIE[$session_name].'; path = /',false);

            $data = $this->_data;
            $this->_logger->addLog('return current order '. $data['orderNo'] );

            if( $data['orderStatus'] == '0' ){
                $redirect = 'checkout/cart';
                $this->messageManager->addErrorMessage( $data['orderInfo'] );
            }else{
                $redirect = 'checkout/onepage/success';
                $this->messageManager->addSuccessMessage( $data['orderInfo'] );
            }

            if( $this->_method->getBasicConfigData('webhook') == '0' ){
                $this->_logger->addLog('result data : '. json_encode($data) );
                $order_status = $this->_method->getTransactionStatus($data['orderStatus']);
                if( $order_status ){
                    $this->_method->setOrderStatus($data,$order_status);
                }
            }

            $this->_logger->addLog('['. $data['orderNo'] .'] redirect to '.$redirect);
        }

        $resultRedirect = $this->resultFactory->create($this->resultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath($redirect);
        return $resultRedirect;

    }


    public function check(){

        if( !isset($_REQUEST['orderNo']) ||
            !isset($_REQUEST['tradeNo']) ||
            !isset($_REQUEST['merNo']) ||
            !isset($_REQUEST['gatewayNo'])){
            return false;
        }

        $this->_data = $this->getRequest()->getParams();

        if( !$this->_method->checkData($this->_data) ){
            return false;
        }

        return true;
    }

}