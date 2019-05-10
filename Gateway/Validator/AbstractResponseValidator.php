<?php

namespace Meetanshi\Cardsave\Gateway\Validator;

use Magento\Payment\Gateway\Validator\AbstractValidator;

/**
 * Class AbstractResponseValidator
 */
abstract class AbstractResponseValidator extends AbstractValidator
{
    protected function validateResponseCode($response)
    {
        return isset($response) && $response === '0';
    }

    protected function validateAuthorisationCode($response)
    {
        return isset($response) && $response != 'null';
    }
}
