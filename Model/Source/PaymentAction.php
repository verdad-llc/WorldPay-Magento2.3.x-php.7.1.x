<?php

namespace Meetanshi\Cardsave\Model\Source;

use Magento\Framework\Option\ArrayInterface;

class PaymentAction implements ArrayInterface
{
    public function toOptionArray()
    {
        return [
            [
                'value' => 'authorize_capture',
                'label' => __('Authorize and Capture (Payment)')
            ],
            [
                'value' => 'authorize',
                'label' => __('Authorize Only (Deferred)'),
            ],
        ];
    }
}
