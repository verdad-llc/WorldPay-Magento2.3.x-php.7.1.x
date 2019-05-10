<?php

namespace Meetanshi\Cardsave\Helper;

use Psr\Log\LoggerInterface;

class Logger
{
    private $logger;
    private $helper;

    public function __construct(LoggerInterface $logger, Data $helper)
    {
        $this->logger = $logger;
        $this->helper = $helper;
    }

    public function debug($message, array $context = [])
    {
        if ($this->helper->isLoggerEnabled()) {
            $message = "CardSave Direct : " . $message;
            $this->logger->debug($message, $context);
        }
    }
}
