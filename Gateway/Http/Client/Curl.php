<?php

namespace Meetanshi\Cardsave\Gateway\Http\Client;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Adapter;
use Magento\Payment\Gateway\Http\ClientException;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\ConverterException;
use Magento\Payment\Gateway\Http\ConverterInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Model\Method\Logger;
use Meetanshi\Cardsave\Helper\Data as CardsaveHelper;
use Meetanshi\Cardsave\Helper\Logger as CardsaveLogger;

class Curl implements ClientInterface
{
    /**
     * HTTP protocol versions
     */
    const HTTP_1 = '1.1';
    const HTTP_0 = '1.0';

    /**
     * HTTP request methods
     */
    const GET = 'GET';
    const POST = 'POST';
    const PUT = 'PUT';
    const HEAD = 'HEAD';
    const DELETE = 'DELETE';
    const TRACE = 'TRACE';
    const OPTIONS = 'OPTIONS';
    const CONNECT = 'CONNECT';
    const MERGE = 'MERGE';
    const PATCH = 'PATCH';

    /**
     * Request timeout
     */
    const REQUEST_TIMEOUT = 30;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    /**
     * @var Adapter\Curl
     */
    private $curl;

    private $helper;

    private $converter;

    private $cardsaveLogger;

    public function __construct(
        Logger $logger,
        ResponseFactory $responseFactory,
        Adapter\Curl $curl,
        CardsaveHelper $helper,
        CardsaveLogger $cardsaveLogger,
        ConverterInterface $converter = null
    ) {
        $this->logger = $logger;
        $this->responseFactory = $responseFactory;
        $this->curl = $curl;
        $this->helper = $helper;
        $this->cardsaveLogger = $cardsaveLogger;
        $this->converter = $converter;
    }

    /**
     * @inheritdoc
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $log = [
            'request' => json_encode($transferObject->getBody(), JSON_UNESCAPED_SLASHES)
        ];

        $this->cardsaveLogger->debug('Curl Request', $log);

        try {
            $result = [];

            $data = $transferObject->getBody();

            $xml = '';

            if ($data['TransactionType'] == 'SALE' || $data['TransactionType'] == 'PREAUTH') {
                $xml = $this->helper->getCaptureXMLData($data);
                $link = 'CardDetailsTransaction';
            } else {
                $xml = $this->helper->getRefundXMLData($data);
                $link = 'CrossReferenceTransaction';
            }

            $headers = [
                'SOAPAction:https://www.thepaymentgateway.net/' . $link . '',
                'Content-Type: text/xml; charset = utf-8',
                'Connection: close'
            ];

            $gatewayId = 1;
            $domain = $this->helper->getDirectPaymentUrl();
            $port = "4430";
            $transactionAttempts = 1;
            $soapSuccess = false;

            while (!$soapSuccess && $gatewayId <= 3 && $transactionAttempts <= 3) {
                /*** builds the URL to post to (rather than it being hard coded -
                 * means we can loop through all 3 gateway servers)* */
                try {
                    $url = 'https://gw' . $gatewayId . '.' . $domain . ':' . $port . '/';
                    $this->curl->write(
                        $transferObject->getMethod(),
                        $url,
                        self::HTTP_1,
                        $headers,
                        $xml
                    );
                    $response = $this->read();

                    $error = $this->curl->getErrno();

                    if ($error == 0) {
                        $result = $this->converter
                            ? $this->converter->convert($response)
                            : [$response];

                        $this->cardsaveLogger->debug('Curl Response', $result);

                        $statusCode = $this->helper->getXMLValue("StatusCode", $response, "[0-9]+");
                        $message = $this->helper->getXMLValue("Message", $response, ".+");

                        if (is_numeric($statusCode)) {
                            $soapSuccess = true;
                        }

                        /** attempt to communicate was unsuccessful... increment the transaction attempt if <=2 */
                        if ($transactionAttempts <= 2) {
                            $transactionAttempts++;
                        } else {

                            /**reset transaction attempt to 1 & incremend $gatewayId (to use next numeric gateway number
                             * (eg. use gw2 rather than gw1 now))*/

                            $transactionAttempts = 1;
                            $gatewayId++;
                        }
                    }
                } catch (\Exception $e) {
                    throw new ClientException(
                        __($e->getMessage())
                    );
                } catch (ConverterException $e) {
                    throw $e;
                } finally {
                    $this->cardsaveLogger->debug('Curl Exception Finally', $log);
                }
            }
            if ($statusCode != '0') {
                throw new LocalizedException(__($message));
                return;
            }
        } catch (\Exception $e) {
            throw new ClientException(__($e->getMessage()));
        } finally {
        }

        return $result;
    }

    public function read()
    {
        return $this->responseFactory->create($this->curl->read())->getBody();
    }
}
