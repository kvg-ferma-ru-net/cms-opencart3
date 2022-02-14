<?php

use Innokassa\MDK\Exceptions\StorageException;
use Innokassa\MDK\Exceptions\TransferException;
use Innokassa\MDK\Entities\Atoms\ReceiptSubType;
use Innokassa\MDK\Exceptions\Services\AutomaticException;
use Innokassa\MDK\Exceptions\Base\InvalidArgumentException;

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
class ControllerExtensionModuleInnokassa extends Controller
{
    public function __construct($registry)
    {
        parent::__construct($registry);

        try {
            $this->load->library('innokassa/ClientBuilder');
        } catch (Exception $e) {
        }
    }

    /**
     * Обработчик события изменения статуса заказа
     *
     * @param string $route
     * @param array $args
     * @return void
     */
    public function changeOrderStatus($route, $args)
    {
        if (count($args) < 2) {
            return;
        }

        $idOrder  = $args[0];
        $idStatus = $args[1];

        try {
            $client = $this->getClient();
        } catch (Exception $e) {
            return;
        }

        $settings = $client->componentSettings();

        $receiptSubType = null;

        if ($settings->getScheme() == 0) {
            if ($idStatus == $settings->getOrderStatus1()) {
                $receiptSubType = ReceiptSubType::PRE;
            } elseif ($idStatus == $settings->getOrderStatus2()) {
                $receiptSubType = ReceiptSubType::FULL;
            }
        } elseif ($settings->getScheme() == 1) {
            if ($idStatus == $settings->getOrderStatus2()) {
                $receiptSubType = ReceiptSubType::FULL;
            }
        }

        if ($receiptSubType === null) {
            return;
        }

        $automatic = $client->serviceAutomatic();

        try {
            $automatic->fiscalize($idOrder, $receiptSubType);
        } catch (InvalidArgumentException | TransferException | StorageException $e) {
            throw $e;
        } catch (AutomaticException $e) {
        }
    }

    //######################################################################

    /**
     * Обработка очереди чеков
     *
     * @return void
     */
    public function pipeline()
    {
        $secret = (isset($this->request->get["secret"]) ? $this->request->get["secret"] : null);

        if (!$secret) {
            $this->response->addHeader('HTTP/1.1 403 Forbidden');
            $this->response->setOutput('Forbidden');
            return;
        }

        try {
            $client = $this->getClient();
        } catch (Exception $e) {
            $this->response->addHeader('HTTP/1.1 403 Forbidden');
            return $this->response->setOutput($e->getMessage());
        }

        $settings = $client->componentSettings();

        if ($secret != $settings->get('module_innokassa_pipeline_secret')) {
            $this->response->addHeader('HTTP/1.1 403 Forbidden');
            $this->response->setOutput('Forbidden');
            return;
        }

        $pipeline = $client->servicePipeline();
        $pipeline->updateAccepted();
        $pipeline->updateUnaccepted();
    }

    //######################################################################
    // PRIVATE
    //######################################################################

    /**
     * Получить клиент MDK
     *
     * @return Client
     */
    private function getClient()
    {
        if (!$this->ClientBuilder) {
            throw new Exception('Клиент Innokassa не инициализирован, возможно не введены настройки');
        }
        return $this->ClientBuilder->getClient();
    }
}
