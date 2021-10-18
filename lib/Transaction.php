<?php


namespace Mindbox;


class Transaction
{
    private static $instance = null;
    private $id;

    public function __construct()
    {
        $this->id = $this->set();
    }

    protected function set()
    {
        return getmypid() . time();
    }

    public function get()
    {
        return $this->id;
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