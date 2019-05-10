<?php

namespace Meetanshi\Cardsave\Gateway\Response;

use Magento\Payment\Gateway\Helper\ContextHelper;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;

class ResponseMessagesHandler implements HandlerInterface
{
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDO = SubjectReader::readPayment($handlingSubject);
        $payment = $paymentDO->getPayment();
        ContextHelper::assertOrderPayment($payment);
        $statusCode = $response['CardDetailsTransactionResult']['_value']['StatusCode'];

        $messages = 'Transaction approved by bank.';
        $declined = 'Transaction declined by bank.';
        $state = $this->getState($statusCode);

        if ($state) {
            $payment->setAdditionalInformation(
                'approve_messages',
                $messages
            );
        } else {
            $payment->setIsTransactionPending(false);
            $payment->setIsFraudDetected(true);
            $payment->setAdditionalInformation('error_messages', $declined);
        }
    }

    protected function getState($responseCode)
    {
        if ($responseCode == 'null' || $responseCode != '0') {
            return false;
        }
        return true;
    }
}
