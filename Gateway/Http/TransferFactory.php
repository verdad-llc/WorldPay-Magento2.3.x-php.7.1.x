<?php

namespace Meetanshi\Cardsave\Gateway\Http;

use Meetanshi\Cardsave\Gateway\Http\Client\Curl;

class TransferFactory extends AbstractTransferFactory
{
    public function create(array $request)
    {
        return $this->transferBuilder
            ->setMethod(Curl::POST)
            ->setBody($request)
            ->build();
    }
}
