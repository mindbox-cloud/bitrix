<?php


namespace Mindbox;


use Mindbox\DataBase\MindboxTransactionTable;

class Transaction
{
    private static $instance = null;
    private $id;
    private $orderId = false;
    protected $logger = null;

    public function __construct($orderId = false)
    {
        $this->orderId = $orderId;
        $this->id = $this->set($orderId);
    }

    protected function set($orderId = false)
    {
        // check exist
        $transactionId = $this->createTransactionId();

        if (!empty($orderId)) {
            $this->addTransaction($orderId, $transactionId);
        }

        return $transactionId;
    }

    public static function existOpenTransaction($orderId)
    {
        $result = false;

        $get = MindboxTransactionTable::getList([
            'select' => ['*'],
            'filter' => [
                '!close' => 1,
                'order_id' => $orderId
            ],
            'order' => [
                'id' => 'DESC'
            ],
        ]);

        if ($transaction = $get->fetch()) {
            $result = $transaction;
        }

        return $result;
    }

    public function addTransaction($orderId, $transactionId)
    {
        $result = MindboxTransactionTable::add([
            'order_id' => $orderId,
            'transaction' => $transactionId,
            'close' => 0
        ]);
    }

    protected function createTransactionId()
    {
        return getmypid() . microtime(true);
    }

    public function get()
    {
        return $this->id;
    }

    public static function closeTransaction($recordId)
    {
        MindboxTransactionTable::update(
            $recordId,
            ['close' => 1]
        );
    }

    public function close()
    {
        if (!empty($this->orderId)) {

            if ($existTransaction = self::existOpenTransaction($this->orderId)) {
                self::closeTransaction($existTransaction['id']);
            }
        }

        $this->clear();
    }

    public function clear()
    {
        self::$instance = null;
    }

    static function getInstance($orderId = false)
    {
        if (is_null(self::$instance)) {
            self::$instance = new self($orderId);
        }

        return self::$instance;
    }

    protected function __clone()
    {
    }
}