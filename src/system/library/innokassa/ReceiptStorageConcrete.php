<?php

use Innokassa\MDK\Entities\Receipt;
use Innokassa\MDK\Storage\ReceiptFilter;
use Innokassa\MDK\Collections\ReceiptCollection;
use Innokassa\MDK\Entities\ConverterAbstract;
use Innokassa\MDK\Storage\ReceiptStorageInterface;

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
class ReceiptStorageConcrete implements ReceiptStorageInterface
{
    /**
     * @param ConverterAbstract $conv
     * @param DB $db
     */
    public function __construct(ConverterAbstract $conv, DB $db, string $table)
    {
        $this->db = $db;
        $this->conv = $conv;
        $this->table = $table;
    }

    /**
     * @inheritDoc
     */
    public function save(Receipt $receipt): int
    {
        $a = $this->conv->receiptToArray($receipt);

        if ($receipt->getId() > 0) {
            unset($a['id']);
            $this->update($receipt->getId(), $a);
            return $receipt->getId();
        }

        $this->insert($a);
        $id = $this->db->getLastId();
        $receipt->setId($id);
        return $id;
    }

    /**
     * @inheritDoc
     */
    public function getOne(int $id): ?Receipt
    {
        if (!($a = $this->select1($id))) {
            return null;
        }

        $a['items'] = json_decode($a['items'], true);
        $a['amount'] = json_decode($a['amount'], true);
        $a['customer'] = json_decode($a['customer'], true);
        $a['notify'] = json_decode($a['notify'], true);
        return $this->conv->receiptFromArray($a);
    }

    /**
     * @inheritDoc
     */
    public function getCollection(ReceiptFilter $filter, int $limit = 0): ReceiptCollection
    {
        $where = $this->where($filter);

        $a = $this->selectArray($where, $limit);
        $receipts = new ReceiptCollection();

        foreach ($a as $aReceipt) {
            $aReceipt['items'] = json_decode($aReceipt['items'], true);
            $aReceipt['amount'] = json_decode($aReceipt['amount'], true);
            $aReceipt['customer'] = json_decode($aReceipt['customer'], true);
            $aReceipt['notify'] = json_decode($aReceipt['notify'], true);

            $receipt = $this->conv->receiptFromArray($aReceipt);
            $receipts[] = $receipt;
        }

        return $receipts;
    }

    public function min(ReceiptFilter $filter, string $column)
    {
        $where = $this->where($filter);
        $sql = "SELECT MIN($column) FROM `{$this->table}` WHERE $where";
        $result = $this->db->query($sql, true)->row;
        return current($result);
    }

    public function max(ReceiptFilter $filter, string $column)
    {
        $where = $this->where($filter);
        $sql = "SELECT MAX($column) FROM `{$this->table}` WHERE $where";
        $result = $this->db->query($sql, true)->row;
        return current($result);
    }

    public function count(ReceiptFilter $filter): int
    {
        $where = $this->where($filter);
        $sql = "SELECT COUNT(*) FROM `{$this->table}` WHERE $where";
        $res = $this->db->query($sql, true);
        $result = $this->db->query($sql, true)->row;
        return current($result);
    }

    //######################################################################
    // PRIVATE
    //######################################################################

    /**
     * @var DB
     */
    private $db;

    /**
     * @var ConverterAbstract
     */
    private $conv;

    //######################################################################

    private function insert(array $a)
    {
        $keys = implode(
            ', ',
            array_map(
                function ($val) {
                    return "`$val`";
                },
                array_keys($a)
            )
        );
        $values = implode(
            ', ',
            array_map(
                function ($val) {
                    $val = (is_array($val) ? json_encode($val, JSON_UNESCAPED_UNICODE) : strval($val));
                    return "'$val'";
                },
                array_values($a)
            )
        );
        $sql = "INSERT INTO `{$this->table}` ($keys) VALUES ($values)";
        $this->db->query($sql);
    }

    private function update(int $id, array $a)
    {
        $a2 = [];
        foreach ($a as $key => $value) {
            $a2[] = "`$key`='" . (is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : strval($value)) . "'";
        }

        $set = implode(', ', $a2);

        $sql = "UPDATE `{$this->table}` SET $set WHERE `id`=$id";
        $this->db->query($sql);
    }

    private function select1(int $id): array
    {
        $sql = "SELECT * FROM `{$this->table}` WHERE `id`=$id";
        return $this->db->query($sql)->row;
    }

    private function selectArray(string $where, int $limit): array
    {
        $sql = "SELECT * FROM `{$this->table}` WHERE $where";
        if ($limit > 0) {
            $sql .= " LIMIT $limit";
        }
        return $this->db->query($sql)->rows;
    }

    private function where(ReceiptFilter $filter): string
    {
        $aWhere = $filter->toArray();
        $aWhere2 = [];
        foreach ($aWhere as $key => $value) {
            $val = $value['value'];
            if ($val === null) {
                $val = 'null';
            } elseif (is_array($val)) {
                $val = '(' . implode(',', $val) . ')';

                if ($value['op'] == '=') {
                    $value['op'] = ' IN ';
                } else {
                    $value['op'] = ' NOT IN ';
                }
            } else {
                $val = "'$val'";
            }
            $op = $value['op'];
            $aWhere2[] = "{$key}{$op}$val";
        }

        $where = implode(' AND ', $aWhere2);
        return $where;
    }
}
