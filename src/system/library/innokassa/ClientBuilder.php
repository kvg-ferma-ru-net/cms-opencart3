<?php

namespace Innokassa;

use Innokassa\MDK\Client;
use Innokassa\MDK\Net\Transfer;
use Innokassa\MDK\Net\ConverterApi;
use Innokassa\MDK\Logger\LoggerFile;
use Innokassa\MDK\Net\NetClientCurl;
use Innokassa\MDK\Services\ManualBase;
use Innokassa\MDK\Services\PrinterBase;
use Innokassa\MDK\Services\PipelineBase;
use Innokassa\MDK\Services\AutomaticBase;
use Innokassa\MDK\Services\ConnectorBase;
use Innokassa\MDK\Storage\ConverterStorage;

include_once(DIR_SYSTEM . 'library/innokassa/mdk/src/autoload.php');
include_once('SettingsConcrete.php');
include_once('ReceiptAdapterConcrete.php');
include_once('ReceiptStorageConcrete.php');

/**
 * Сборкщик клиента Innokassa MDK
 */
class ClientBuilder
{
    /**
     * @param Registry $registry
     */
    public function __construct($registry)
    {
        $modelOrder = null;
        try {
            // пробуем загрузить из admin
            $registry->get('load')->model('sale/order');
            $modelOrder = $registry->get('model_sale_order');
        } catch (\Exception $e) {
            // не получилось, значит пробуем из catalog
            $registry->get('load')->model('checkout/order');
            $modelOrder = $registry->get('model_checkout_order');
        }

        $registry->get('load')->model('setting/setting');
        $settingsArray = $registry->get('model_setting_setting')->getSetting("module_innokassa");

        $settings = new \SettingsConcrete($settingsArray);
        $adapter = new \ReceiptAdapterConcrete($modelOrder, $settings);
        $storage = new \ReceiptStorageConcrete(
            new ConverterStorage(),
            $registry->get('db'),
            $this->getTableName()
        );

        $logger = new LoggerFile();

        $transfer = new Transfer(
            new NetClientCurl(),
            new ConverterApi(),
            $settings->getActorId(),
            $settings->getActorToken(),
            $settings->getCashbox(),
            $logger
        );

        $automatic = new AutomaticBase($settings, $storage, $transfer, $adapter);
        $manual = new ManualBase($storage, $transfer, $settings);
        $pipeline = new PipelineBase($storage, $transfer);
        $printer = new PrinterBase($storage, $transfer);
        $connector = new ConnectorBase($transfer);

        $this->client = new Client(
            $settings,
            $adapter,
            $storage,
            $automatic,
            $manual,
            $pipeline,
            $printer,
            $connector,
            $logger
        );
    }

    /**
     * Получить клиент MDK
     *
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Получить название таблицы
     *
     * @return string
     */
    public function getTableName()
    {
        return DB_PREFIX . 'innokassa_receipts';
    }

    //######################################################################
    // PRIVATE
    //######################################################################

    /**
     * @var Client
     */
    private $client = null;
}