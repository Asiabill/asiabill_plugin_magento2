<?php
/**
 * Created by PhpStorm.
 * User: tenny
 * Date: 2021/9/15
 * Time: 11:19
 */

namespace Asiabill\Payment\Controller\Payment;

use Magento\Payment\Model\InfoInterface;

class Confirm extends \Magento\Framework\App\Action\Action
{

    protected $_pageFactory;
    protected $_session;
    protected $_orderFactory;
    protected $_method;
    protected $_logger;
    protected $resultFactory;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Checkout\Model\Session $session,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Asiabill\Payment\Model\PaymentMethod $method,
        \Asiabill\Payment\Model\PaymentLogger $paymentLogger
    ){
        parent::__construct($context);
        $this->_pageFactory = $pageFactory;
        $this->_session = $session;
        $this->_orderFactory = $orderFactory;
        $this->_method = $method;
        $this->_logger = $paymentLogger;
    }

    public function execute()
    {
        $order = $this->_session->getLastRealOrder();
        $redirect = '';

        if( $order ){

            $payment = $order->getPayment();
            $method_code = $payment->getMethodInstance()->getCode();
            $this->_method->setCode($method_code);

            $confirm_response = $payment->getAdditionalInformation('confirm_response');
            if( $confirm_response ){
                $confirm_arr = json_decode($confirm_response,true);
                $data = $confirm_arr['data'];
                if( $data['threeDsType'] == 1 && !empty($data['threeDsUrl']) ){
                    $order_status = $this->_method->getBasicConfigData('pending_order_status');
                    $order->addStatusToHistory($order_status, 'Redirect to 3D verification page.');
                    $order->save();
                    $this->_logger->addLog('['. $data['orderNo'] .'] Redirect to 3D verification page.');
                    $redirect = $data['threeDsUrl'];
                }
                else if ( in_array($data['orderStatus'],['1','-1','-2'])  ){
                    if( $this->_method->getBasicConfigData('webhook') == '0' ) {
                        $order_status = $this->_method->getTransactionStatus($data['orderStatus']);
                        if( $order_status ){
                            $this->_method->setOrderStatus($data,$order_status);
                        }
                    }
                    $redirect = 'checkout/onepage/success';
                }
            }

        }

        $resultRedirect = $this->resultFactory->create($this->resultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath($redirect);
        return $resultRedirect;

        exit();

    }

}