<?php

namespace Meetanshi\Cardsave\Gateway\Validator;

use Magento\Payment\Gateway\Helper\SubjectReader;

/**
 * Class RefundValidator
 *
 * @package Meetanshi\Cardsave\Gateway\Validator
 */
class RefundValidator extends AbstractResponseValidator
{
    /**
     * @inheritdoc
     */
    public function validate(array $validationSubject)
    {
        $response = SubjectReader::readResponse($validationSubject);

        $authCode = $response['CrossReferenceTransactionResult']['_value']['StatusCode'];
        $message = $response['CrossReferenceTransactionResult']['_value']['Message'];

        $errorMessages = [];

        $validationResult = $this->validateResponseCode($authCode);

        if (!$validationResult) {
            $errorMessages = [__((string)$message)];
        }

        return $this->createResult($validationResult, $errorMessages);
    }
}
