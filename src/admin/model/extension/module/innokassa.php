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
            `subtype` TINYINT,
            `order_id` VARCHAR(255) NOT NULL,
            `site_id` VARCHAR(255) NOT NULL,
            `receipt_id` VARCHAR(64) NOT NULL,
            `status` TINYINT NOT NULL,
            `type` TINYINT NOT NULL,
            `items` TEXT NOT NULL,
            `taxation` TINYINT NOT NULL,
            `amount` TEXT NOT NULL,
            `customer` TEXT NOT NULL,
            `notify` TEXT NOT NULL,
            `location` VARCHAR(255) NOT NULL,
            `start_time` VARCHAR(32) NOT NULL,
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
