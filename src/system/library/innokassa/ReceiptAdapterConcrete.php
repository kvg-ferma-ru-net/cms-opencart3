<?php

use Innokassa\MDK\Entities\Atoms\Vat;
use Innokassa\MDK\Entities\ReceiptItem;
use Innokassa\MDK\Entities\Primitives\Amount;
use Innokassa\MDK\Entities\Primitives\Notify;
use Innokassa\MDK\Entities\Atoms\PaymentMethod;
use Innokassa\MDK\Entities\Primitives\Customer;
use Innokassa\MDK\Entities\Atoms\ReceiptSubType;
use Innokassa\MDK\Entities\Atoms\ReceiptItemType;
use Innokassa\MDK\Entities\ReceiptAdapterInterface;
use Innokassa\MDK\Collections\ReceiptItemCollection;

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
class ReceiptAdapterConcrete implements ReceiptAdapterInterface
{
    public function __construct($modelSaleOrder, $settings)
    {
        $this->modelSaleOrder = $modelSaleOrder;
        $this->settings = $settings;
    }

    public function getItems(string $orderId, int $subType): ReceiptItemCollection
    {
        $paymentMethod = (
            $subType == ReceiptSubType::PRE
            ? PaymentMethod::PREPAYMENT_FULL
            : PaymentMethod::PAYMENT_FULL
        );
        $products = $this->modelSaleOrder->getOrderProducts($orderId);
        $items = new ReceiptItemCollection();

        foreach ($products as $product) {
            $item = new ReceiptItem();
            $item
                ->setType(ReceiptItemType::PRODUCT)
                ->setPaymentMethod($paymentMethod)
                ->setName($product['name'])
                ->setPrice($product['price'])
                ->setQuantity($product['quantity'])
                ->setAmount($product['total'])
                ->setVat(
                    new Vat(
                        $product['tax'],
                        $this->settings->getTaxation()
                    )
                );
            $items[] = $item;
        }

        $totals = $this->modelSaleOrder->getOrderTotals($orderId);

        foreach ($totals as $total) {
            if ($total['code'] == 'shipping' && $total['value'] > 0) {
                $item = new ReceiptItem();
                $item
                    ->setType(ReceiptItemType::SERVICE)
                    ->setPaymentMethod($paymentMethod)
                    ->setName($total['title'])
                    ->setPrice($total['value'])
                    ->setQuantity(1)
                    ->setVat(new Vat($this->settings->getShippingVat()));
                $items[] = $item;
            }
        }

        return $items;
    }

    public function getAmount(string $orderId, int $subType): Amount
    {
        $order = $this->modelSaleOrder->getOrder($orderId);
        $amount = new Amount(
            ($subType == ReceiptSubType::PRE ? Amount::CASHLESS : Amount::PREPAYMENT),
            $order['total']
        );

        return $amount;
    }

    public function getCustomer(string $orderId): Customer
    {
        $order = $this->modelSaleOrder->getOrder($orderId);
        $customer = new Customer($order['customer']);

        return $customer;
    }

    public function getNotify(string $orderId): Notify
    {
        $order = $this->modelSaleOrder->getOrder($orderId);
        $notify = new Notify();

        if (!empty($order['email'])) {
            $notify->setEmail($order['email']);
        }

        if (!empty($order['telephone'])) {
            $notify->setPhone($order['telephone']);
        }

        return $notify;
    }

    //######################################################################
    // PRIVATE
    //######################################################################

    private $modelSaleOrder = null;
    private $settings = null;
}
