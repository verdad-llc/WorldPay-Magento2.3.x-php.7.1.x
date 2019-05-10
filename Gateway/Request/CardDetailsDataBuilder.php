<?php

namespace Meetanshi\Cardsave\Gateway\Request;

use Magento\Directory\Model\CountryFactory;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\HTTP\Adapter\CurlFactory;
use Magento\Payment\Gateway\Data\AddressAdapterInterface;
use Magento\Payment\Gateway\Helper\ContextHelper;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Meetanshi\Cardsave\Helper\Data as CardsaveHelper;
use Meetanshi\Cardsave\Helper\Logger as CardsaveLogger;
use Meetanshi\Cardsave\Observer\DataAssignObserver;

/**
 * Class CardDetailsDataBuilder
 *
 * @package Meetanshi\Cardsave\Gateway\Request
 */
class CardDetailsDataBuilder implements BuilderInterface
{
    const MERCHANT_ID = 'MerchantID';
    const PASSWORD = 'Password';
    const AMOUNT = 'Amount';
    const CURRENCY_CODE = 'CurrencyCode';
    const TRANSACTION_TYE = 'TransactionType';
    const ORDER_ID = 'OrderID';
    const ORDER_DESCRIPTION = 'OrderDescription';
    const CARD_NAME = 'CardName';
    const CARD_NUMBER = 'CardNumber';
    const MONTH = 'Month';
    const YEAR = 'Year';
    const CV2 = 'CV2';
    const ADDRESS1 = 'Address1';
    const CITY = 'City';
    const POSTCODE = 'PostCode';
    const COUNTRY_CODE = 'CountryCode';
    const EMAIL_ADDRESS = 'EmailAddress';
    const PHONE_NUMBER = 'PhoneNumber';
    const CUSTOMER_IP_ADDRESS = 'CustomerIPAddress';


    private $curl;
    private $helper;
    private $cardsaveLogger;
    private $encryptor;
    private $countryFactory;

    /**
     * CardDetailsDataBuilder constructor.
     *
     * @param EncryptorInterface $encryptor
     */
    public function __construct(CurlFactory $curl, CardsaveHelper $helper, CardsaveLogger $cardsaveLogger, CountryFactory $countryFactory, EncryptorInterface $encryptor)
    {
        $this->curl = $curl;
        $this->helper = $helper;
        $this->cardsaveLogger = $cardsaveLogger;
        $this->countryFactory = $countryFactory;
        $this->encryptor = $encryptor;
    }

    /**
     * @inheritdoc
     */
    public function build(array $buildSubject)
    {
        $paymentDO = SubjectReader::readPayment($buildSubject);

        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $paymentDO->getPayment();
        $order = $paymentDO->getOrder();
        $billingAddress = $order->getBillingAddress();
        $multiply = 100;
        $amount = SubjectReader::readAmount($buildSubject);
        $total = round($amount * $multiply);

        ContextHelper::assertOrderPayment($payment);
        $data = $payment->getAdditionalInformation();

        $month = $this->formatMonth($data[OrderPaymentInterface::CC_EXP_MONTH]);
        $year = substr($data[OrderPaymentInterface::CC_EXP_YEAR], 2, 3);
        $cardNumber = $this->encryptor->decrypt($data[OrderPaymentInterface::CC_NUMBER_ENC]);
        $cardHolderName = $this->getName($billingAddress);

        $cvn = $this->encryptor->decrypt($data[DataAssignObserver::CC_CID_ENC]);

        $szCurrencyCode = strval(826);
        $country = $this->countryFactory->create()->loadByCode($billingAddress->getCountryId());
        $szCountryCode = strval(826);

        $countryCodes = $this->helper->getCountryCodes();
        foreach ($countryCodes as $iso) {
            if ($country['iso3_code'] == $iso['label']) {
                $szCountryCode = strval($iso['value']);
            }
        }

        $currencyCodes = $this->helper->getCurrencyCodes();
        foreach ($currencyCodes as $codes) {
            if (strtoupper($order->getCurrencyCode()) == $codes['label']) {
                $szCurrencyCode = strval($codes['value']);
            }
        }
        $orderId = $order->getOrderIncrementId();
        $orderPrefix = $this->helper->getOrderPrefix();
        if ($orderPrefix) {
            $orderId = $orderPrefix . $orderId;
        }

        $params = [
            self::MERCHANT_ID => $this->helper->getMerchantID(),
            self::PASSWORD => $this->helper->getMerchantPassword(),
            self::AMOUNT => $total,
            self::CURRENCY_CODE => $szCurrencyCode,
            self::TRANSACTION_TYE => $this->helper->getPaymentType(),
            self::ORDER_ID => $orderId,
            self::ORDER_DESCRIPTION => $this->helper->getOrderDescription(),
            self::CARD_NAME => $cardHolderName,
            self::CARD_NUMBER => $cardNumber,
            self::MONTH => $month,
            self::YEAR => $year,
            self::CV2 => $cvn,
            self::ADDRESS1 => $billingAddress->getStreetLine1(),
            self::CITY => $billingAddress->getCity(),
            self::POSTCODE => $billingAddress->getPostcode(),
            self::COUNTRY_CODE => $szCountryCode,
            self::EMAIL_ADDRESS => $billingAddress->getEmail(),
            self::PHONE_NUMBER => $billingAddress->getTelephone(),
            self::CUSTOMER_IP_ADDRESS => $_SERVER["REMOTE_ADDR"]
        ];

        return $params;
    }

    private function formatMonth($month)
    {
        return !empty($month) ? sprintf('%02d', $month) : null;
    }

    private function getName(AddressAdapterInterface $billingAddress)
    {
        return $billingAddress->getFirstname() . ' ' . $billingAddress->getLastname();
    }
}
