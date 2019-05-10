<?php

namespace Meetanshi\Cardsave\Gateway\Validator\Direct;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Meetanshi\Cardsave\Gateway\Validator\AbstractResponseValidator;

class ResponseValidator extends AbstractResponseValidator
{
    public function validate(array $validationSubject)
    {
        $response = SubjectReader::readResponse($validationSubject);

        $statusCode = $response['CardDetailsTransactionResult']['_value']['StatusCode'];
        $authCode = $response['TransactionOutputData']['_value']['AuthCode'];
        $message = $response['CardDetailsTransactionResult']['_value']['Message'];

        $errorMessages = [];

        $validationResult = $this->validateResponseCode($statusCode)
            && $this->validateAuthorisationCode($authCode);

        if (!$validationResult) {
            $errorMessages = [__((string)$message)];
        }

        return $this->createResult($validationResult, $errorMessages);
    }
}
