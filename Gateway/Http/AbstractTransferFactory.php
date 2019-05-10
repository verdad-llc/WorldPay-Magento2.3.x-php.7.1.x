<?php

namespace Meetanshi\Cardsave\Gateway\Http;

use Magento\Framework\Xml\Generator;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Http\TransferBuilder;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;
use Meetanshi\Cardsave\Helper\Data as CardsaveHelper;

/**
 * Class AbstractTransferFactory
 */
abstract class AbstractTransferFactory implements TransferFactoryInterface
{
    /**
     * @var ConfigInterface
     */
    protected $config;

    /**
     * @var TransferBuilder
     */
    protected $transferBuilder;

    /**
     * @var Generator
     */
    protected $generator;
    protected $cardsaveHelper;
    /**
     * Transaction Type
     *
     * @var string
     */
    private $action;

    /**
     * AbstractTransferFactory constructor.
     *
     * @param ConfigInterface $config
     * @param TransferBuilder $transferBuilder
     * @param Generator $generator
     * @param null $action
     */
    public function __construct(
        ConfigInterface $config,
        TransferBuilder $transferBuilder,
        Generator $generator,
        CardsaveHelper $cardsaveHelper,
        $action = null
    ) {
        $this->config = $config;
        $this->transferBuilder = $transferBuilder;
        $this->generator = $generator;
        $this->action = $action;
        $this->cardsaveHelper = $cardsaveHelper;
    }
}
