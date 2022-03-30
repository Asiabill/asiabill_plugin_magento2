<?php

namespace Asiabill\Payment\Model\Ui;


class Koreacard extends \Asiabill\Payment\Model\PaymentConfigProvider
{
    protected $code = 'asiabill_koreacard';

    public function getConfig(){

        $config = parent::getConfig();

        if ($this->method->isAvailable($this->checkoutSession->getQuote())) {

            $icons = [];
            $icons[] = $this->repository->createAsset('Asiabill_Payment::images/nh_card.svg', [])->getUrl();
            $icons[] = $this->repository->createAsset('Asiabill_Payment::images/lotte_card.svg', [])->getUrl();
            $icons[] = $this->repository->createAsset('Asiabill_Payment::images/shinhan_card.svg', [])->getUrl();
            $icons[] = $this->repository->createAsset('Asiabill_Payment::images/samsung_card.svg', [])->getUrl();
            $icons[] = $this->repository->createAsset('Asiabill_Payment::images/bc_card.svg', [])->getUrl();

            $config['payment'][$this->code]['icons'] = $icons;

        }

        return $config;
    }

}
