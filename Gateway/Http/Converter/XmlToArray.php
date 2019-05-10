<?php

namespace Meetanshi\Cardsave\Gateway\Http\Converter;

use Magento\Framework\Xml\Parser as XmlParser;
use Magento\Payment\Gateway\Http\ConverterException;
use Magento\Payment\Gateway\Http\ConverterInterface;
use Psr\Log\LoggerInterface;

class XmlToArray implements ConverterInterface
{
    protected $logger;

    private $parser;

    public function __construct(
        XmlParser $parser,
        LoggerInterface $logger
    ) {
        $this->parser = $parser;
    }

    public function convert($response)
    {
        $this->parser->loadXML($response);
        $result = $this->parser->xmlToArray();
        if (!empty($result['soap:Envelope']['soap:Body']['CardDetailsTransactionResponse'])) {
            return $result['soap:Envelope']['soap:Body']['CardDetailsTransactionResponse'];
        } elseif (!empty($result['soap:Envelope']['soap:Body']['CrossReferenceTransactionResponse'])) {
            return $result['soap:Envelope']['soap:Body']['CrossReferenceTransactionResponse'];
        } else {
            throw new ConverterException(__('Can\'t read response Card Save'));
        }
    }
}
