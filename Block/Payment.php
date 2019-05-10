<?php

namespace Meetanshi\Cardsave\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Payment\Gateway\ConfigInterface;

class Payment extends Template
{
    const PAYMENT_CODE = 'cardsave';

    private $config;

    public function __construct(
        Context $context,
        ConfigInterface $config,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->config = $config;
    }

    public function getPaymentConfig()
    {
        return json_encode(
            [
                'code' => self::PAYMENT_CODE,
            ],
            JSON_UNESCAPED_SLASHES
        );
    }

    public function getCode()
    {
        return self::PAYMENT_CODE;
    }

    public function toHtml()
    {
        return parent::toHtml();
    }
}
