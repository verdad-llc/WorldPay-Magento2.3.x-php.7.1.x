<?php

namespace Meetanshi\Cardsave\Gateway\Response;

use Magento\Payment\Gateway\Helper\ContextHelper;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;

class PaymentDetailsHandler implements HandlerInterface
{
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDO = SubjectReader::readPayment($handlingSubject);

        $authCode = $response['TransactionOutputData']['_value']['AuthCode'];
        $crossReferrence = $response['TransactionOutputData']['_attribute']['CrossReference'];

        $payment = $paymentDO->getPayment();
        ContextHelper::assertOrderPayment($payment);

        $payment->setTransactionId($authCode);
        $payment->setLastTransId($authCode);
        $payment->setIsTransactionClosed(false);
        $payment->setAdditionalInformation('transaction_id', $authCode);
        $payment->setAdditionalInformation('reference_num', $crossReferrence);
    }
}
