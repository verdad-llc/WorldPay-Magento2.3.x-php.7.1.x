<?php

namespace Meetanshi\Cardsave\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Session\SessionManagerInterface;
use Meetanshi\Cardsave\Helper\Data;

class CardsaveConfigProvider implements ConfigProviderInterface
{
    protected $helper;
    protected $checkoutSession;
    protected $coreSession;

    public function __construct(Data $helper, CheckoutSession $checkoutSession, SessionManagerInterface $coreSession)
    {
        $this->helper = $helper;
        $this->checkoutSession = $checkoutSession;
        $this->coreSession = $coreSession;
    }

    public function getConfig()
    {
        $config = [];
        $showLogo = $this->helper->showLogo();
        $imageUrl = $this->helper->getPaymentLogo();
        $instructions = $this->helper->getInstructions();
        $config['cardsave_imageurl'] = ($showLogo) ? $imageUrl : '';
        $config['cardsave_instructions'] = ($instructions) ? $instructions : '';

        return $config;
    }
}
