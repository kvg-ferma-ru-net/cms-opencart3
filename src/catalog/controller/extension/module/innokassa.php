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
        $this->load->library('innokassa/ClientBuilder');
    }

    public function changeOrderStatus($route, $args, $output)
    {
        if (count($args) < 2) {
            return;
        }

        $idOrder  = $args[0];
        $idStatus = $args[1];

        $client = $this->getClient();
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
        return $this->ClientBuilder->getClient();
    }
}
