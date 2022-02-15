<?php

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
class ModelExtensionModuleInnokassa extends Model
{
    /**
     * Уставнока модуля (часть модели)
     *
     * @return void
     */
    public function install()
    {
        $table = $this->getTableName();
        $sql = "CREATE TABLE IF NOT EXISTS `$table` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `uuid` VARCHAR(32) NOT NULL,
            `cashbox` INT NOT NULL,
            `siteId` VARCHAR(32) NOT NULL,
            `orderId` INT UNSIGNED NOT NULL, 
            `status` TINYINT UNSIGNED NOT NULL,
            `type` TINYINT UNSIGNED NOT NULL, 
            `subType` TINYINT UNSIGNED NOT NULL, 
            `items` TEXT NOT NULL, 
            `taxation` TINYINT UNSIGNED NOT NULL, 
            `amount` TEXT NOT NULL,
            `notify` TEXT NOT NULL, 
            `customer` TEXT NOT NULL, 
            `location` TEXT NOT NULL, 
            `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE = InnoDB";

        $this->db->query($sql);
    }

    /**
     * Удаление модуля (часть модели)
     *
     * @return void
     */
    public function uninstall()
    {
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
}
