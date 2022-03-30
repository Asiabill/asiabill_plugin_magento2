<?php

namespace Asiabill\Payment\Model;

class PaymentConfigProvider implements \Magento\Checkout\Model\ConfigProviderInterface
{
    protected $code;
    protected $localeResolver;
    protected $currentCustomer;
    protected $checkoutSession;
    protected $repository;
    protected $data;
    protected $method;

    public function __construct(
        \Magento\Framework\Locale\ResolverInterface $localeResolver,
        \Magento\Customer\Helper\Session\CurrentCustomer $currentCustomer,
        \Magento\Checkout\Model\Session $checkoutSession,

        \Magento\Framework\View\Asset\Repository $repository,
        \Magento\Payment\Helper\Data $data
    ) {
        $this->localeResolver = $localeResolver;
        $this->currentCustomer = $currentCustomer;
        $this->checkoutSession = $checkoutSession;
        $this->repository = $repository;
        $this->data = $data;
        $this->method = $this->data->getMethodInstance($this->code);
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        $config = [];
        if ($this->method->isAvailable($this->checkoutSession->getQuote())) {

            $redirect_url = $this->method->getRedirectUrl();
            $icons_location = $this->method->getBasicConfigData('icons_location');

            $img = str_replace('asiabill_','',$this->code);
            $icons = [$this->repository->createAsset('Asiabill_Payment::images/'.$img.'.png', [])->getUrl()];

            $config['payment'][$this->code] = [
                'redirectUrl' => $redirect_url,
                'iconsLocation' => $icons_location,
                'icons' => $icons
            ];

        }

        return $config;
    }


}
