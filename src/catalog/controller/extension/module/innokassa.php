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

    public function changeOrderStatus($route, $args, $output)
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
