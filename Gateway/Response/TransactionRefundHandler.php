<?php

namespace Meetanshi\Cardsave\Gateway\Response;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;

class TransactionRefundHandler implements HandlerInterface
{
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDO = SubjectReader::readPayment($handlingSubject);

        $authCode = $response['TransactionOutputData']['_attribute']['CrossReference'];

        $orderPayment = $paymentDO->getPayment();
        $orderPayment->setTransactionId($authCode);

        $orderPayment->setIsTransactionClosed(true);
        $orderPayment->setShouldCloseParentTransaction(!$orderPayment->getCreditmemo()->getInvoice()->canRefund());
    }
}
