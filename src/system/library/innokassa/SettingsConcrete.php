<?php

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
class SettingsConcrete implements Innokassa\MDK\Settings\SettingsInterface
{
    public function __construct(array $settings)
    {
        $this->settings = $settings;
    }

    public function getActorId(): string
    {
        return intval($this->get('module_innokassa_actor_id'));
    }

    public function getActorToken(): string
    {
        return $this->get('module_innokassa_actor_token');
    }

    public function getCashbox(): string
    {
        return intval($this->get('module_innokassa_cashbox'));
    }

    public function getLocation(): string
    {
        return $this->get('module_innokassa_location');
    }

    public function getTaxation(): int
    {
        return intval($this->get('module_innokassa_taxation'));
    }

    public function getScheme(): int
    {
        return boolval($this->get('module_innokassa_scheme'));
    }

    public function getOnly2(): bool
    {
        return $this->getScheme() == 1;
    }

    public function get(string $name)
    {
        if (isset($this->settings[$name]) && strlen($this->settings[$name]) > 0) {
            return $this->settings[$name];
        }

        throw new Innokassa\MDK\Exceptions\SettingsException("Настройка '$name' не инициализирована");
    }

    //######################################################################

    protected $settings = null;
}
