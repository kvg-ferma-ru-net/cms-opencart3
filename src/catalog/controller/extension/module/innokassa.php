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
        if (!$this->isEnableModule()) {
            return;
        }

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

        if (!$settings->getOnly2()) {
            if ($idStatus == $settings->get('module_innokassa_order_status1')) {
                $receiptSubType = ReceiptSubType::PRE;
            } elseif ($idStatus == $settings->get('module_innokassa_order_status2')) {
                $receiptSubType = ReceiptSubType::FULL;
            }
        } elseif ($settings->getOnly2()) {
            if ($idStatus == $settings->get('module_innokassa_order_status2')) {
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
        if (!$this->isEnableModule()) {
            $this->response->addHeader('HTTP/1.1 403 Forbidden');
            $this->response->setOutput('Module Innokassa is disable');
            return;
        }

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

    /**
     * Включен ли модуль
     *
     * @return boolean
     */
    private function isEnableModule()
    {
        if (!$this->ClientBuilder) {
            return false;
        }

        $client = $this->ClientBuilder->getClient();
        $settings = $client->componentSettings();

        return ($settings->get('module_innokassa_status') == 1);
    }
}
