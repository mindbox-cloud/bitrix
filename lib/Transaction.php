<?php


namespace Mindbox;


class Transaction
{
    private static $instance = null;
    private $id;
    protected $logger = null;

    public function __construct()
    {
        $this->id = $this->set();
    }

    protected function set()
    {
        return getmypid() . microtime(true);
    }

    public function get()
    {
        return $this->id;
    }

    public function clear()
    {
        self::$instance = null;
    }

    static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    protected function __clone()
    {
    }
}