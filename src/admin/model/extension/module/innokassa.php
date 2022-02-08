<?php

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
class ModelExtensionModuleInnokassa extends Model
{
    public function install()
    {
        $table = DB_PREFIX . "innokassa_receipts";
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
            `amount` VARCHAR(32) NOT NULL,
            `notify` TEXT NOT NULL, 
            `customer` TEXT NOT NULL, 
            `location` TEXT NOT NULL, 
            `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE = InnoDB";

        $this->db->query($sql);
    }

    public function uninstall()
    {
    }
}
