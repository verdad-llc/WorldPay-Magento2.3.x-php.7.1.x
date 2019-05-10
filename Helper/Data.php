<?php

namespace Meetanshi\Cardsave\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\HTTP\Adapter\CurlFactory;
use Magento\Framework\View\Asset\Repository;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\StoreResolver;

class Data extends AbstractHelper
{
    const CONFIG_CARDSAVE_ACTIVE = 'payment/cardsave/active';
    const CONFIG_CARDSAVE_INSTRUCTIONS = 'payment/cardsave/instructions';
    const CONFIG_CARDSAVE_MODE = 'payment/cardsave/mode';
    const CONFIG_CARDSAVE_LIVE_MERCHANT_ID = 'payment/cardsave/live_merchant_id';
    const CONFIG_CARDSAVE_LIVE_PASSWORD = 'payment/cardsave/live_password';
    const CONFIG_CARDSAVE_TEST_MERCHANT_ID = 'payment/cardsave/test_merchant_id';
    const CONFIG_CARDSAVE_TEST_PASSWORD = 'payment/cardsave/test_password';
    const CONFIG_CARDSAVE_SHOW_LOGO = 'payment/cardsave/show_logo';
    const CONFIG_CARDSAVE_FORM_DOMAIN = 'payment/cardsave/gateway_url';
    const CONFIG_CARDSAVE_PAYMENT_ACTION = 'payment/cardsave/payment_action';
    const CONFIG_CARDSAVE_DEBUG = 'payment/cardsave/debug';
    const CONFIG_CARDSAVE_ORDER_PREFIX = 'payment/cardsave/vendor_prefix';

    private $encryptor;
    private $curlFactory;
    private $storeResolver;
    private $storeManager;
    private $repository;
    private $request;

    public function __construct(Context $context, EncryptorInterface $encryptor, CurlFactory $curlFactory, StoreResolver $storeResolver, StoreManagerInterface $storeManager, Repository $repository, RequestInterface $request)
    {
        parent::__construct($context);
        $this->encryptor = $encryptor;
        $this->curlFactory = $curlFactory;
        $this->storeResolver = $storeResolver;
        $this->storeManager = $storeManager;
        $this->repository = $repository;
        $this->request = $request;
    }

    public function isActive()
    {
        return $this->scopeConfig->getValue(self::CONFIG_CARDSAVE_ACTIVE, ScopeInterface::SCOPE_STORE);
    }

    public function getOrderPrefix()
    {
        return $this->scopeConfig->getValue(self::CONFIG_CARDSAVE_ORDER_PREFIX, ScopeInterface::SCOPE_STORE);
    }

    public function getOrderDescription()
    {
        $description = trim($this->scopeConfig->getValue('general/store_information/name', ScopeInterface::SCOPE_STORE));
        if (!$description) {
            return "Magento 2 order";
        }

        return $description;
    }

    public function getMerchantPassword()
    {
        $endpoint = $this->scopeConfig->getValue(self::CONFIG_CARDSAVE_MODE, ScopeInterface::SCOPE_STORE);
        if ($endpoint) {
            return $this->encryptor->decrypt(trim($this->scopeConfig->getValue(self::CONFIG_CARDSAVE_TEST_PASSWORD, ScopeInterface::SCOPE_STORE)));
        } else {
            return $this->encryptor->decrypt(trim($this->scopeConfig->getValue(self::CONFIG_CARDSAVE_LIVE_PASSWORD, ScopeInterface::SCOPE_STORE)));
        }
    }

    public function getMerchantID()
    {
        $endpoint = $this->scopeConfig->getValue(self::CONFIG_CARDSAVE_MODE, ScopeInterface::SCOPE_STORE);
        if ($endpoint) {
            return $this->encryptor->decrypt(trim($this->scopeConfig->getValue(self::CONFIG_CARDSAVE_TEST_MERCHANT_ID, ScopeInterface::SCOPE_STORE)));
        } else {
            return $this->encryptor->decrypt(trim($this->scopeConfig->getValue(self::CONFIG_CARDSAVE_LIVE_MERCHANT_ID, ScopeInterface::SCOPE_STORE)));
        }
    }

    public function showLogo()
    {
        return $this->scopeConfig->getValue(self::CONFIG_CARDSAVE_SHOW_LOGO, ScopeInterface::SCOPE_STORE);
    }

    public function getPaymentLogo()
    {
        $params = ['_secure' => $this->request->isSecure()];
        return $this->repository->getUrlWithParams('Meetanshi_Cardsave::images/cardsave.png', $params);
    }

    public function getInstructions()
    {
        return $this->scopeConfig->getValue(self::CONFIG_CARDSAVE_INSTRUCTIONS, ScopeInterface::SCOPE_STORE);
    }

    public function isLoggerEnabled()
    {
        return $this->scopeConfig->getValue(self::CONFIG_CARDSAVE_DEBUG, ScopeInterface::SCOPE_STORE);
    }

    public function getPaymentType()
    {
        $action = $this->scopeConfig->getValue(self::CONFIG_CARDSAVE_PAYMENT_ACTION, ScopeInterface::SCOPE_STORE);
        if ($action == 'authorize_capture') {
            return 'SALE';
        } else {
            return 'PREAUTH';
        }
    }

    public function getDirectPaymentUrl()
    {
        return $this->scopeConfig->getValue(self::CONFIG_CARDSAVE_FORM_DOMAIN, ScopeInterface::SCOPE_STORE);
    }

    public function getStoreName()
    {
        return $this->storeManager->getStore()->getName();
    }

    public function getCaptureXMLData($data)
    {
        $xml = '<?xml version="1.0" encoding="utf-8"?>
            <soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
            xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
            <soap:Body>
                <CardDetailsTransaction xmlns="https://www.thepaymentgateway.net/">
                    <PaymentMessage>
                        <MerchantAuthentication MerchantID="' . trim($data['MerchantID']) . '" Password="' . trim($data['Password']) . '" />
                        <TransactionDetails Amount="' . $data['Amount'] . '" CurrencyCode="' . $data['CurrencyCode'] . '">
                            <MessageDetails TransactionType="' . $data['TransactionType'] . '" />
                            <OrderID>' . $this->clean($data['OrderID'], 50) . '</OrderID>
                            <OrderDescription>' . $this->clean($data['OrderDescription'], 256) . '</OrderDescription>
                            <TransactionControl>
                                <EchoCardType>TRUE</EchoCardType>
                                <EchoAVSCheckResult>TRUE</EchoAVSCheckResult>
                                <EchoCV2CheckResult>TRUE</EchoCV2CheckResult>
                                <EchoAmountReceived>TRUE</EchoAmountReceived>
                                <DuplicateDelay>20</DuplicateDelay>
                                <CustomVariables>
                                    <GenericVariable Name="MyInputVariable" Value="Ping" />
                                </CustomVariables>
                            </TransactionControl>
                        </TransactionDetails>
                        <CardDetails>
                            <CardName>' . $this->clean($data['CardName'], 100) . '</CardName>
                            <CardNumber>' . $data['CardNumber'] . '</CardNumber>
                            <StartDate Month="" Year="" />
                            <ExpiryDate Month="' . $data['Month'] . '" Year="' . $data['Year'] . '" />
                            <CV2>' . $data['CV2'] . '</CV2>
                            <IssueNumber></IssueNumber>
                        </CardDetails>
                        <CustomerDetails>
                            <BillingAddress>
                                <Address1>' . $this->clean($data['Address1'], 100) . '</Address1>
                                <Address2></Address2>
                                <Address3></Address3>
                                <Address4></Address4>
                                <City>' . $this->clean($data['City'], 50) . '</City>
                                <State></State>
                                <PostCode>' . $this->clean($data['PostCode'], 50) . '</PostCode>
                                <CountryCode>' . $data['CountryCode'] . '</CountryCode>
                            </BillingAddress>
                            <EmailAddress>' . $this->clean($data['EmailAddress'], 100) . '</EmailAddress>
                            <PhoneNumber>' . $this->clean($data['PhoneNumber'], 30) . '</PhoneNumber>
                            <CustomerIPAddress>' . $data['CustomerIPAddress'] . '</CustomerIPAddress>
                        </CustomerDetails>
                        <PassOutData>Some data to be passed out</PassOutData>
                    </PaymentMessage>
                </CardDetailsTransaction>
            </soap:Body>
            </soap:Envelope>';

        return $xml;
    }

    public function clean($string, $numberLimit)
    {
        // remove restricted characters
        $toReplace = ["#", "\\", ">", "<", "\"", "[", "]"];
        $string = str_replace($toReplace, "", $string);

        // remove html special chars and turn into html equivalent value
        $string = htmlspecialchars($string);

        // now ensure it doesnt exceed the allowed amount
        $string = substr($string, 0, $numberLimit);

        //return clean string
        return $string;
    }

    public function getRefundXMLData($data)
    {
        $xml = '<?xml version="1.0" encoding="utf-8" ?><soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema"><soap:Body>
			<CrossReferenceTransaction xmlns="https://www.thepaymentgateway.net/">
                <PaymentMessage>
                    <MerchantAuthentication MerchantID="' . trim($data['MerchantID']) . '" Password="' . trim($data['Password']) . '" />
                    <TransactionDetails Amount="' . $data['Amount'] . '" CurrencyCode="' . $data['CurrencyCode'] . '">
                        <MessageDetails TransactionType="' . $data['TransactionType'] . '" NewTransaction="FALSE" CrossReference="' . $data['CrossReference'] . '" />
                        <OrderID>' . $this->clean($data['OrderID'], 50) . '</OrderID>
                        <OrderDescription>' . $this->clean($data['OrderDescription'], 256) . '</OrderDescription>
                    </TransactionDetails>                        
                </PaymentMessage>
            </CrossReferenceTransaction>
            </soap:Body>
			</soap:Envelope>';

        return $xml;
    }

    public function getCountryCodes()
    {
        return [
            ['value' => "826", 'label' => "GB"],
            ['value' => "840", 'label' => "US"],
            ['value' => "36", 'label' => "AU"],
            ['value' => "124", 'label' => "CA"],
            ['value' => "276", 'label' => "DE"],
            ['value' => "250", 'label' => "FR"],
            ['value' => "533", 'label' => "AW"],
            ['value' => "4", 'label' => "AF"],
            ['value' => "24", 'label' => "AO"],
            ['value' => "660", 'label' => "AI"],
            ['value' => "248", 'label' => "AX"],
            ['value' => "8", 'label' => "AL"],
            ['value' => "20", 'label' => "AD"],
            ['value' => "530", 'label' => "AN"],
            ['value' => "784", 'label' => "AE"],
            ['value' => "32", 'label' => "AR"],
            ['value' => "51", 'label' => "AM"],
            ['value' => "16", 'label' => "AS"],
            ['value' => "10", 'label' => "AQ"],
            ['value' => "260", 'label' => "TF"],
            ['value' => "28", 'label' => "AG"],
            ['value' => "40", 'label' => "AT"],
            ['value' => "31", 'label' => "AZ"],
            ['value' => "108", 'label' => "BI"],
            ['value' => "56", 'label' => "BE"],
            ['value' => "204", 'label' => "BJ"],
            ['value' => "854", 'label' => "BF"],
            ['value' => "50", 'label' => "BD"],
            ['value' => "100", 'label' => "BG"],
            ['value' => "48", 'label' => "BH"],
            ['value' => "44", 'label' => "BS"],
            ['value' => "70", 'label' => "BA"],
            ['value' => "652", 'label' => "BL"],
            ['value' => "112", 'label' => "BY"],
            ['value' => "84", 'label' => "BZ"],
            ['value' => "60", 'label' => "BM"],
            ['value' => "68", 'label' => "BO"],
            ['value' => "76", 'label' => "BR"],
            ['value' => "52", 'label' => "BB"],
            ['value' => "96", 'label' => "BN"],
            ['value' => "64", 'label' => "BT"],
            ['value' => "74", 'label' => "BV"],
            ['value' => "72", 'label' => "BW"],
            ['value' => "140", 'label' => "CF"],
            ['value' => "166", 'label' => "CC"],
            ['value' => "756", 'label' => "CH"],
            ['value' => "152", 'label' => "CL"],
            ['value' => "156", 'label' => "CN"],
            ['value' => "384", 'label' => "CI"],
            ['value' => "120", 'label' => "CM"],
            ['value' => "180", 'label' => "CD"],
            ['value' => "178", 'label' => "CG"],
            ['value' => "184", 'label' => "CK"],
            ['value' => "170", 'label' => "CO"],
            ['value' => "174", 'label' => "KM"],
            ['value' => "132", 'label' => "CV"],
            ['value' => "188", 'label' => "CR"],
            ['value' => "192", 'label' => "CU"],
            ['value' => "162", 'label' => "CX"],
            ['value' => "136", 'label' => "KY"],
            ['value' => "196", 'label' => "CY"],
            ['value' => "203", 'label' => "CZ"],
            ['value' => "262", 'label' => "DJ"],
            ['value' => "212", 'label' => "DM"],
            ['value' => "208", 'label' => "DK"],
            ['value' => "214", 'label' => "DO"],
            ['value' => "12", 'label' => "DZ"],
            ['value' => "218", 'label' => "EC"],
            ['value' => "818", 'label' => "EG"],
            ['value' => "232", 'label' => "ER"],
            ['value' => "732", 'label' => "EH"],
            ['value' => "724", 'label' => "ES"],
            ['value' => "233", 'label' => "EE"],
            ['value' => "231", 'label' => "ET"],
            ['value' => "246", 'label' => "FI"],
            ['value' => "242", 'label' => "FJ"],
            ['value' => "238", 'label' => "FK"],
            ['value' => "234", 'label' => "FO"],
            ['value' => "583", 'label' => "FM"],
            ['value' => "266", 'label' => "GA"],
            ['value' => "268", 'label' => "GE"],
            ['value' => "831", 'label' => "GG"],
            ['value' => "288", 'label' => "GH"],
            ['value' => "292", 'label' => "GI"],
            ['value' => "324", 'label' => "GN"],
            ['value' => "312", 'label' => "GP"],
            ['value' => "270", 'label' => "GM"],
            ['value' => "624", 'label' => "GW"],
            ['value' => "226", 'label' => "GQ"],
            ['value' => "300", 'label' => "GR"],
            ['value' => "308", 'label' => "GD"],
            ['value' => "304", 'label' => "GL"],
            ['value' => "320", 'label' => "GT"],
            ['value' => "254", 'label' => "GF"],
            ['value' => "316", 'label' => "GU"],
            ['value' => "328", 'label' => "GY"],
            ['value' => "344", 'label' => "HK"],
            ['value' => "334", 'label' => "HM"],
            ['value' => "340", 'label' => "HN"],
            ['value' => "191", 'label' => "HR"],
            ['value' => "332", 'label' => "HT"],
            ['value' => "348", 'label' => "HU"],
            ['value' => "360", 'label' => "ID"],
            ['value' => "833", 'label' => "IM"],
            ['value' => "356", 'label' => "IN"],
            ['value' => "86", 'label' => "IO"],
            ['value' => "372", 'label' => "IE"],
            ['value' => "364", 'label' => "IR"],
            ['value' => "368", 'label' => "IQ"],
            ['value' => "352", 'label' => "IS"],
            ['value' => "376", 'label' => "IL"],
            ['value' => "380", 'label' => "IT"],
            ['value' => "388", 'label' => "JM"],
            ['value' => "832", 'label' => "JE"],
            ['value' => "400", 'label' => "JO"],
            ['value' => "392", 'label' => "JP"],
            ['value' => "398", 'label' => "KZ"],
            ['value' => "404", 'label' => "KE"],
            ['value' => "417", 'label' => "KG"],
            ['value' => "116", 'label' => "KH"],
            ['value' => "296", 'label' => "KI"],
            ['value' => "659", 'label' => "KN"],
            ['value' => "410", 'label' => "KR"],
            ['value' => "414", 'label' => "KW"],
            ['value' => "418", 'label' => "LA"],
            ['value' => "422", 'label' => "LB"],
            ['value' => "430", 'label' => "LR"],
            ['value' => "434", 'label' => "LY"],
            ['value' => "662", 'label' => "LC"],
            ['value' => "438", 'label' => "LI"],
            ['value' => "144", 'label' => "LK"],
            ['value' => "426", 'label' => "LS"],
            ['value' => "440", 'label' => "LT"],
            ['value' => "442", 'label' => "LU"],
            ['value' => "428", 'label' => "LV"],
            ['value' => "446", 'label' => "MO"],
            ['value' => "663", 'label' => "MF"],
            ['value' => "504", 'label' => "MA"],
            ['value' => "492", 'label' => "MC"],
            ['value' => "498", 'label' => "MD"],
            ['value' => "450", 'label' => "MG"],
            ['value' => "462", 'label' => "MV"],
            ['value' => "484", 'label' => "MX"],
            ['value' => "584", 'label' => "MH"],
            ['value' => "807", 'label' => "MK"],
            ['value' => "466", 'label' => "ML"],
            ['value' => "470", 'label' => "MT"],
            ['value' => "104", 'label' => "MM"],
            ['value' => "499", 'label' => "ME"],
            ['value' => "496", 'label' => "MN"],
            ['value' => "580", 'label' => "MP"],
            ['value' => "508", 'label' => "MZ"],
            ['value' => "478", 'label' => "MR"],
            ['value' => "500", 'label' => "MS"],
            ['value' => "474", 'label' => "MQ"],
            ['value' => "480", 'label' => "MU"],
            ['value' => "454", 'label' => "MW"],
            ['value' => "458", 'label' => "MY"],
            ['value' => "175", 'label' => "YT"],
            ['value' => "516", 'label' => "NA"],
            ['value' => "540", 'label' => "NC"],
            ['value' => "562", 'label' => "NE"],
            ['value' => "574", 'label' => "NF"],
            ['value' => "566", 'label' => "NG"],
            ['value' => "558", 'label' => "NI"],
            ['value' => "570", 'label' => "NU"],
            ['value' => "528", 'label' => "NL"],
            ['value' => "578", 'label' => "NO"],
            ['value' => "524", 'label' => "NP"],
            ['value' => "520", 'label' => "NR"],
            ['value' => "554", 'label' => "NZ"],
            ['value' => "512", 'label' => "OM"],
            ['value' => "586", 'label' => "PK"],
            ['value' => "591", 'label' => "PA"],
            ['value' => "612", 'label' => "PN"],
            ['value' => "604", 'label' => "PE"],
            ['value' => "608", 'label' => "PH"],
            ['value' => "585", 'label' => "PW"],
            ['value' => "598", 'label' => "PG"],
            ['value' => "616", 'label' => "PL"],
            ['value' => "630", 'label' => "PR"],
            ['value' => "408", 'label' => "KP"],
            ['value' => "620", 'label' => "PT"],
            ['value' => "600", 'label' => "PY"],
            ['value' => "275", 'label' => "PS"],
            ['value' => "258", 'label' => "PF"],
            ['value' => "634", 'label' => "QA"],
            ['value' => "638", 'label' => "RE"],
            ['value' => "642", 'label' => "RO"],
            ['value' => "643", 'label' => "RU"],
            ['value' => "646", 'label' => "RW"],
            ['value' => "682", 'label' => "SA"],
            ['value' => "736", 'label' => "SD"],
            ['value' => "686", 'label' => "SN"],
            ['value' => "702", 'label' => "SG"],
            ['value' => "239", 'label' => "GS"],
            ['value' => "654", 'label' => "SH"],
            ['value' => "744", 'label' => "SJ"],
            ['value' => "90", 'label' => "SB"],
            ['value' => "694", 'label' => "SL"],
            ['value' => "222", 'label' => "SV"],
            ['value' => "674", 'label' => "SM"],
            ['value' => "706", 'label' => "SO"],
            ['value' => "666", 'label' => "PM"],
            ['value' => "688", 'label' => "RS"],
            ['value' => "678", 'label' => "ST"],
            ['value' => "740", 'label' => "SR"],
            ['value' => "703", 'label' => "SK"],
            ['value' => "705", 'label' => "SI"],
            ['value' => "752", 'label' => "SE"],
            ['value' => "748", 'label' => "SZ"],
            ['value' => "690", 'label' => "SC"],
            ['value' => "760", 'label' => "SY"],
            ['value' => "796", 'label' => "TC"],
            ['value' => "148", 'label' => "TD"],
            ['value' => "768", 'label' => "TG"],
            ['value' => "764", 'label' => "TH"],
            ['value' => "762", 'label' => "TJ"],
            ['value' => "772", 'label' => "TK"],
            ['value' => "795", 'label' => "TM"],
            ['value' => "626", 'label' => "TL"],
            ['value' => "776", 'label' => "TO"],
            ['value' => "780", 'label' => "TT"],
            ['value' => "788", 'label' => "TN"],
            ['value' => "792", 'label' => "TR"],
            ['value' => "798", 'label' => "TV"],
            ['value' => "158", 'label' => "TW"],
            ['value' => "834", 'label' => "TZ"],
            ['value' => "800", 'label' => "UG"],
            ['value' => "804", 'label' => "UA"],
            ['value' => "581", 'label' => "UM"],
            ['value' => "858", 'label' => "UY"],
            ['value' => "860", 'label' => "UZ"],
            ['value' => "336", 'label' => "VA"],
            ['value' => "670", 'label' => "VC"],
            ['value' => "862", 'label' => "VE"],
            ['value' => "92", 'label' => "VG"],
            ['value' => "850", 'label' => "VI"],
            ['value' => "704", 'label' => "VN"],
            ['value' => "548", 'label' => "VU"],
            ['value' => "876", 'label' => "WF"],
            ['value' => "882", 'label' => "WS"],
            ['value' => "887", 'label' => "YE"],
            ['value' => "710", 'label' => "ZA"],
            ['value' => "894", 'label' => "ZM"],
            ['value' => "716", 'label' => "ZW"]
        ];
    }

    public function getCurrencyCodes()
    {
        return [
            ['value' => "634", 'label' => "NGN"],
            ['value' => "678", 'label' => "STD"],
            ['value' => "943", 'label' => "MZN"],
            ['value' => "826", 'label' => "GBP"],
            ['value' => "654", 'label' => "SHP"],
            ['value' => "704", 'label' => "VND"],
            ['value' => "952", 'label' => "XOF"],
            ['value' => "356", 'label' => "INR"],
            ['value' => "807", 'label' => "MKD"],
            ['value' => "959", 'label' => "XAU"],
            ['value' => "410", 'label' => "KRW"],
            ['value' => "946", 'label' => "RON"],
            ['value' => "949", 'label' => "TRY"],
            ['value' => "532", 'label' => "ANG"],
            ['value' => "788", 'label' => "TND"],
            ['value' => "646", 'label' => "RWF"],
            ['value' => "504", 'label' => "MAD"],
            ['value' => "174", 'label' => "KMF"],
            ['value' => "484", 'label' => "MXN"],
            ['value' => "478", 'label' => "MRO"],
            ['value' => "233", 'label' => "EEK"],
            ['value' => "400", 'label' => "JOD"],
            ['value' => "292", 'label' => "GIP"],
            ['value' => "690", 'label' => "SCR"],
            ['value' => "422", 'label' => "LBP"],
            ['value' => "232", 'label' => "ERN"],
            ['value' => "496", 'label' => "MNT"],
            ['value' => "328", 'label' => "GYD"],
            ['value' => "970", 'label' => "COU"],
            ['value' => "974", 'label' => "BYR"],
            ['value' => "608", 'label' => "PHP"],
            ['value' => "598", 'label' => "PGK"],
            ['value' => "951", 'label' => "XCD"],
            ['value' => "52", 'label' => "BBD"],
            ['value' => "944", 'label' => "AZN"],
            ['value' => "434", 'label' => "LYD"],
            ['value' => "706", 'label' => "SOS"],
            ['value' => "950", 'label' => "XAF"],
            ['value' => "840", 'label' => "USD"],
            ['value' => "68", 'label' => "BOB"],
            ['value' => "214", 'label' => "DOP"],
            ['value' => "818", 'label' => "EGP"],
            ['value' => "170", 'label' => "COP"],
            ['value' => "986", 'label' => "BRL"],
            ['value' => "961", 'label' => "XAG"],
            ['value' => "973", 'label' => "AOA"],
            ['value' => "962", 'label' => "XPT"],
            ['value' => "414", 'label' => "KWD"],
            ['value' => "604", 'label' => "PEN"],
            ['value' => "702", 'label' => "SGD"],
            ['value' => "862", 'label' => "VEB"],
            ['value' => "953", 'label' => "XPF"],
            ['value' => "558", 'label' => "NIO"],
            ['value' => "348", 'label' => "HUF"],
            ['value' => "948", 'label' => "CHW"],
            ['value' => "116", 'label' => "KHR"],
            ['value' => "956", 'label' => "XBB"],
            ['value' => "156", 'label' => "CNY"],
            ['value' => "834", 'label' => "TZS"],
            ['value' => "997", 'label' => "GEL"],
            ['value' => "242", 'label' => "FJD"],
            ['value' => "941", 'label' => "RSD"],
            ['value' => "104", 'label' => "MMK"],
            ['value' => "84", 'label' => "BZD"],
            ['value' => "710", 'label' => "ZAR"],
            ['value' => "760", 'label' => "SYP"],
            ['value' => "512", 'label' => "OMR"],
            ['value' => "324", 'label' => "GNF"],
            ['value' => "196", 'label' => "CYP"],
            ['value' => "960", 'label' => "XDR"],
            ['value' => "716", 'label' => "ZWD"],
            ['value' => "972", 'label' => "TJS"],
            ['value' => "462", 'label' => "MVR"],
            ['value' => "979", 'label' => "MXV"],
            ['value' => "860", 'label' => "UZS"],
            ['value' => "12", 'label' => "DZD"],
            ['value' => "332", 'label' => "HTG"],
            ['value' => "963", 'label' => "XTS"],
            ['value' => "32", 'label' => "ARS"],
            ['value' => "642", 'label' => "ROL"],
            ['value' => "984", 'label' => "BOV"],
            ['value' => "440", 'label' => "LTL"],
            ['value' => "480", 'label' => "MUR"],
            ['value' => "426", 'label' => "LSL"],
            ['value' => "262", 'label' => "DJF"],
            ['value' => "886", 'label' => "YER"],
            ['value' => "748", 'label' => "SZL"],
            ['value' => "192", 'label' => "CUP"],
            ['value' => "548", 'label' => "VUV"],
            ['value' => "360", 'label' => "IDR"],
            ['value' => "51", 'label' => "AMD"],
            ['value' => "894", 'label' => "ZMK"],
            ['value' => "90", 'label' => "SBD"],
            ['value' => "132", 'label' => "CVE"],
            ['value' => "999", 'label' => "XXX"],
            ['value' => "524", 'label' => "NPR"],
            ['value' => "203", 'label' => "CZK"],
            ['value' => "44", 'label' => "BSD"],
            ['value' => "96", 'label' => "BND"],
            ['value' => "50", 'label' => "BDT"],
            ['value' => "404", 'label' => "KES"],
            ['value' => "947", 'label' => "CHE"],
            ['value' => "964", 'label' => "XPD"],
            ['value' => "398", 'label' => "KZT"],
            ['value' => "352", 'label' => "ISK"],
            ['value' => "64", 'label' => "BTN"],
            ['value' => "533", 'label' => "AWG"],
            ['value' => "230", 'label' => "ETB"],
            ['value' => "800", 'label' => "UGX"],
            ['value' => "968", 'label' => "SRD"],
            ['value' => "882", 'label' => "WST"],
            ['value' => "454", 'label' => "MWK"],
            ['value' => "985", 'label' => "PLN"],
            ['value' => "124", 'label' => "CAD"],
            ['value' => "776", 'label' => "TOP"],
            ['value' => "208", 'label' => "DKK"],
            ['value' => "108", 'label' => "BIF"],
            ['value' => "764", 'label' => "THB"],
            ['value' => "458", 'label' => "MYR"],
            ['value' => "364", 'label' => "IRR"],
            ['value' => "600", 'label' => "PYG"],
            ['value' => "977", 'label' => "BAM"],
            ['value' => "446", 'label' => "MOP"],
            ['value' => "780", 'label' => "TTD"],
            ['value' => "703", 'label' => "SKK"],
            ['value' => "958", 'label' => "XBD"],
            ['value' => "430", 'label' => "LRD"],
            ['value' => "191", 'label' => "HRK"],
            ['value' => "694", 'label' => "SLL"],
            ['value' => "756", 'label' => "CHF"],
            ['value' => "969", 'label' => "MGA"],
            ['value' => "270", 'label' => "GMD"],
            ['value' => "418", 'label' => "LAK"],
            ['value' => "516", 'label' => "NAD"],
            ['value' => "392", 'label' => "JPY"],
            ['value' => "320", 'label' => "GTQ"],
            ['value' => "554", 'label' => "NZD"],
            ['value' => "578", 'label' => "NOK"],
            ['value' => "376", 'label' => "ILS"],
            ['value' => "957", 'label' => "XBC"],
            ['value' => "498", 'label' => "MDL"],
            ['value' => "998", 'label' => "XBA"],
            ['value' => "344", 'label' => "HKD"],
            ['value' => "417", 'label' => "KGS"],
            ['value' => "858", 'label' => "UYU"],
            ['value' => "60", 'label' => "BMD"],
            ['value' => "682", 'label' => "SAR"],
            ['value' => "643", 'label' => "RUB"],
            ['value' => "470", 'label' => "MTL"],
            ['value' => "340", 'label' => "HNL"],
            ['value' => "72", 'label' => "BWP"],
            ['value' => "368", 'label' => "IQD"],
            ['value' => "188", 'label' => "CRC"],
            ['value' => "144", 'label' => "LKR"],
            ['value' => "752", 'label' => "SEK"],
            ['value' => "136", 'label' => "KYD"],
            ['value' => "8", 'label' => "ALL"],
            ['value' => "48", 'label' => "BHD"],
            ['value' => "795", 'label' => "TMM"],
            ['value' => "938", 'label' => "SDG"],
            ['value' => "590", 'label' => "PAB"],
            ['value' => "152", 'label' => "CLP"],
            ['value' => "980", 'label' => "UAH"],
            ['value' => "428", 'label' => "LVL"],
            ['value' => "288", 'label' => "GHS"],
            ['value' => "978", 'label' => "EUR"],
            ['value' => "976", 'label' => "CDF"],
            ['value' => "586", 'label' => "PKR"],
            ['value' => "408", 'label' => "KPW"],
            ['value' => "388", 'label' => "JMD"],
            ['value' => "990", 'label' => "CLF"],
            ['value' => "971", 'label' => "AFN"],
            ['value' => "975", 'label' => "BGN"],
            ['value' => "36", 'label' => "AUD"],
            ['value' => "238", 'label' => "FKP"],
            ['value' => "901", 'label' => "TWD"],
            ['value' => "784", 'label' => "AED"]
        ];
    }

    public function getXMLValue($XMLElement, $XML, $pattern)
    {
        $soapArray = null;
        $ToReturn = null;
        if (preg_match('#<' . $XMLElement . '>(' . $pattern . ')</' . $XMLElement . '>#iU', $XML, $soapArray)) {
            $ToReturn = $soapArray[1];
        } else {
            $ToReturn = $XMLElement . " Not Found";
        }

        return $ToReturn;
    }
}
