<?php

namespace Meetanshi\Cardsave\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;

abstract class AbstractDataBuilder implements BuilderInterface
{
    const PAYMENT = 'Payment';

    const REFUND = 'Refund';
}
