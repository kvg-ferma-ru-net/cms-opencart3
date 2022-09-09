<?php

use Innokassa\MDK\Entities\ReceiptId\ReceiptIdFactoryMeta;

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
class ReceiptIdFactoryMetaConcrete extends ReceiptIdFactoryMeta
{
    protected function getEngine(): string
    {
        return 'oc3';
    }
}
