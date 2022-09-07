<?php

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
class SettingsConcrete extends Innokassa\MDK\Settings\SettingsAbstract
{
    public function __construct(array $settings)
    {
        $this->settings = $settings;
    }

    public function getActorId(string $siteId = ''): string
    {
        return $this->get('module_innokassa_actor_id');
    }

    public function getActorToken(string $siteId = ''): string
    {
        return $this->get('module_innokassa_actor_token');
    }

    public function getCashbox(string $siteId = ''): string
    {
        return $this->get('module_innokassa_cashbox');
    }

    public function getLocation(string $siteId = ''): string
    {
        return $this->get('module_innokassa_location');
    }

    public function getTaxation(string $siteId = ''): int
    {
        return intval($this->get('module_innokassa_taxation'));
    }

    public function getShippingVat(string $siteId = ''): int
    {
        return intval($this->get('module_innokassa_shipping_vat'));
    }

    public function getScheme(string $siteId = ''): int
    {
        return intval($this->get('module_innokassa_scheme'));
    }

    public function getOrderStatusReceiptPre(string $siteId = ''): string
    {
        return $this->get('module_innokassa_order_status1');
    }

    public function getOrderStatusReceiptFull(string $siteId = ''): string
    {
        return $this->get('module_innokassa_order_status2');
    }

    public function getVatShipping(string $siteId = ''): int
    {
        return intval($this->get('module_innokassa_shipping_vat'));
    }

    public function getVatDefaultItems(string $siteId = ''): int
    {
        $name = 'module_innokassa_item_vat';
        throw new Innokassa\MDK\Exceptions\SettingsException("Настройка '$name' не используется");
        return 0;
    }

    public function getTypeDefaultItems(string $siteId = ''): int
    {
        return intval($this->get('module_innokassa_item_type'));
    }

    public function get(string $name, string $siteId = '')
    {
        if (isset($this->settings[$name]) && strlen($this->settings[$name]) > 0) {
            return $this->settings[$name];
        }

        throw new Innokassa\MDK\Exceptions\SettingsException("Настройка '$name' не инициализирована");
    }

    //######################################################################

    protected $settings = null;
}
