<?php

use Innokassa\MDK\Entities\Atoms\Vat;
use Innokassa\MDK\Entities\ReceiptItem;
use Innokassa\MDK\Entities\Primitives\Notify;
use Innokassa\MDK\Entities\Atoms\PaymentMethod;
use Innokassa\MDK\Entities\Primitives\Customer;
use Innokassa\MDK\Entities\Atoms\ReceiptSubType;
use Innokassa\MDK\Entities\Atoms\ReceiptItemType;
use Innokassa\MDK\Entities\ReceiptAdapterInterface;
use Innokassa\MDK\Collections\ReceiptItemCollection;
use Innokassa\MDK\Exceptions\Base\InvalidArgumentException;

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
class ReceiptAdapterConcrete implements ReceiptAdapterInterface
{
    /**
     * @param ModelSaleOrder|ModelCheckoutOrder $modelSaleOrder
     * @param SettingsConcrete $settings
     */
    public function __construct($modelSaleOrder, SettingsConcrete $settings)
    {
        $this->modelSaleOrder = $modelSaleOrder;
        $this->settings = $settings;
    }

    /**
     * @inheritDoc
     */
    public function getItems(string $orderId, string $siteId, int $subType): ReceiptItemCollection
    {
        $paymentMethod = (
            $subType == ReceiptSubType::PRE
            ? PaymentMethod::PREPAYMENT_FULL
            : PaymentMethod::PAYMENT_FULL
        );
        $products = $this->modelSaleOrder->getOrderProducts($orderId);
        $items = new ReceiptItemCollection();

        try {
            foreach ($products as $product) {
                $item = new ReceiptItem();
                $item
                    ->setType($this->settings->get('module_innokassa_item_type'))
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
        } catch (Exception $e) {
            throw new InvalidArgumentException(
                "Заказ #$orderId, " . 'позиция ' . $product['name'] . ', ошибка: ' . $e->getMessage()
            );
        }

        // если есть платная доставка - добавляем в позиции
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

    /**
     * @inheritDoc
     */
    public function getTotal(string $orderId, string $siteId): float
    {
        $order = $this->modelSaleOrder->getOrder($orderId);
        return floatval($order['total']);
    }

    /**
     * @inheritDoc
     */
    public function getCustomer(string $orderId, string $siteId): ?Customer
    {
        $order = $this->modelSaleOrder->getOrder($orderId);
        $customerName = trim($order['lastname'] . ' ' . $order['firstname']);
        $customer = ($customerName ? new Customer($customerName) : null);

        return $customer;
    }

    /**
     * @inheritDoc
     */
    public function getNotify(string $orderId, string $siteId): Notify
    {
        $order = $this->modelSaleOrder->getOrder($orderId);
        $notify = new Notify();

        if (!empty($order['email'])) {
            $notify->setEmail($order['email']);
        } elseif (!empty($order['telephone'])) {
            $notify->setPhone($order['telephone']);
        }

        return $notify;
    }

    //######################################################################
    // PRIVATE
    //######################################################################

    /**
     * @var ModelSaleOrder
     */
    private $modelSaleOrder = null;

    /**
     * @var SettingsConcrete
     */
    private $settings = null;
}
