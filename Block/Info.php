<?php

namespace Meetanshi\Cardsave\Block;

use Magento\Payment\Block\ConfigurableInfo;

class Info extends ConfigurableInfo
{
    protected function getLabel($field)
    {
        switch ($field) {
            case 'cc_type':
                return __('Card Type');
            case 'card_number':
                return __('Card number');
            case 'card_expiry_date':
                return __('Expiration Date');
            case 'approve_messages':
                return __('Approve Message');
            default:
                return parent::getLabel($field);
        }
    }
}
