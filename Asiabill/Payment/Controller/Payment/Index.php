<?php 

namespace Asiabill\Payment\Controller\Payment;

class Index extends \Magento\Framework\App\Action\Action
{

    protected $_pageFactory;
    protected $_session;
    protected $_orderFactory;
    protected $_method;
    protected $_formFactory;
    protected $resultFactory;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Checkout\Model\Session $session,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Asiabill\Payment\Model\PaymentMethod $method
    ){
        parent::__construct($context);
        $this->_pageFactory = $pageFactory;
        $this->_session = $session;
        $this->_orderFactory = $orderFactory;
        $this->_formFactory = $formFactory;
        $this->_method = $method;
    }

    /**
     * 支付重定向，获取参数信息，生成from表单提交
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $orderId = $this->_session->getLastRealOrderId();

        if( $orderId ){

            $session_name = session_name();
            header('Set-Cookie: '.$session_name.' = '.$_COOKIE[$session_name].'; domain = .'.$this->_request->getServer('SERVER_NAME').'; path = /; SameSite=None; Secure',false);

            $order_payment_code = $this->_session->getLastRealOrder()->getPayment()->getMethodInstance()->getCode();
            $this->_method->setCode($order_payment_code);

            /** @var \Magento\Framework\Data\Form $form */
            $form = $this->_formFactory->create();

            $form->setMethod('post');
            $form->setUseContainer(true);
            $form->setId('asiabill_checkout');
            $form->setAction($this->_method->getAction());

            foreach ( $this->_method->getCheckoutParameter() as $field => $value ){
                $form->addField($field, 'hidden', array('name' => $field, 'value' => $value));
            }

            echo $form->toHtml();

            echo '<style>.spinner{margin:100px auto 0;width:150px;text-align:center}.spinner>div{width:20px;height:20px;background-color:#888;border-radius:100%;display:inline-block;-webkit-animation:bouncedelay 1.4s infinite ease-in-out;animation:bouncedelay 1.4s infinite ease-in-out;-webkit-animation-fill-mode:both;animation-fill-mode:both}.spinner .bounce1{-webkit-animation-delay:-0.32s;animation-delay:-0.32s}.spinner .bounce2{-webkit-animation-delay:-0.16s;animation-delay:-0.16s}@-webkit-keyframes bouncedelay{0%,80%,100%{-webkit-transform:scale(0.0)}40%{-webkit-transform:scale(1.0)}}@keyframes bouncedelay{0%,80%,100%{transform:scale(0.0);-webkit-transform:scale(0.0)}40%{transform:scale(1.0);-webkit-transform:scale(1.0)}}</style>
<div class="spinner"><div class="bounce1"></div><div class="bounce2"></div><div class="bounce3"></div></div>';

            echo '<script>document.getElementById("asiabill_checkout").submit().remove()</script>';

            //return  $this->_pageFactory->create(); // 加载block模板

        }else{
            $resultRedirect = $this->resultFactory->create($this->resultFactory::TYPE_REDIRECT);
            $resultRedirect->setPath('checkout/');
            return $resultRedirect;
        }
        exit();

    }

}


