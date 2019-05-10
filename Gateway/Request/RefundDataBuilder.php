<?php

namespace Meetanshi\Cardsave\Gateway\Request;

use Magento\Directory\Model\CountryFactory;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Meetanshi\Cardsave\Helper\Data;
use Meetanshi\Cardsave\Helper\Logger as CardsaveLogger;

class RefundDataBuilder implements BuilderInterface
{
    const MERCHANT_ID = 'MerchantID';
    const PASSWORD = 'Password';
    const AMOUNT = 'Amount';
    const CURRENCY_CODE = 'CurrencyCode';
    const TRANSACTION_TYE = 'TransactionType';
    const ORDER_ID = 'OrderID';
    const ORDER_DESCRIPTION = 'OrderDescription';
    const CROSS_REFERENCE = 'CrossReference';
    const CUSTOMER_IP_ADDRESS = 'CustomerIPAddress';

    private $helper;
    private $countryFactory;
    private $cardsaveLogger;

    public function __construct(Data $helper, CountryFactory $countryFactory, CardsaveLogger $cardsaveLogger)
    {
        $this->helper = $helper;
        $this->countryFactory = $countryFactory;
        $this->cardsaveLogger = $cardsaveLogger;
    }

    public function build(array $buildSubject)
    {
        $paymentDO = SubjectReader::readPayment($buildSubject);
        $payment = $paymentDO->getPayment();
        $crossReference = $payment->getAdditionalInformation("reference_num");
        $order = $paymentDO->getOrder();

        $multiply = 100;
        $amount = SubjectReader::readAmount($buildSubject);
        $total = round($amount * $multiply);

        $szCurrencyCode = strval(826);

        $currencyCodes = $this->helper->getCurrencyCodes();
        foreach ($currencyCodes as $codes) {
            if (strtoupper($order->getCurrencyCode()) == $codes['label']) {
                $szCurrencyCode = strval($codes['value']);
            }
        }

        $params = [
            self::MERCHANT_ID => $this->helper->getMerchantID(),
            self::PASSWORD => $this->helper->getMerchantPassword(),
            self::AMOUNT => $total,
            self::CURRENCY_CODE => $szCurrencyCode,
            self::TRANSACTION_TYE => 'REFUND',
            self::CROSS_REFERENCE => $crossReference,
            self::ORDER_ID => $order->getOrderIncrementId(),
            self::ORDER_DESCRIPTION => $this->helper->getOrderDescription(),
            self::CUSTOMER_IP_ADDRESS => $_SERVER["REMOTE_ADDR"]
        ];

        return $params;
    }
}
