<?php

namespace regreg;

use pms\bear\ClientCounnect;
use pms\Output;


class Regcs extends \Phalcon\Di\Injectable
{
    public function connect()
    {
        Output::output('connect', 'Proxycs');
    }

    public function receive(array $params)
    {
        //counnect
        if ($params[0] instanceof ClientCounnect) {
            $ClientCounnect = $params[0];
            $data = $params[0]->getData();


        }


    }
    /**
     * 编码
     * @param array $data
     * @return string
     */
    private function encode($data): string
    {
        $msg_normal = \pms\Serialize::pack($data);
        return $msg_normal;
    }

    public function error()
    {
        Output::output('error', 'Proxycs');
    }

    public function close()
    {
        Output::output('close', 'Proxycs');
    }

    public function bufferFull()
    {

    }

    public function bufferEmpty()
    {

    }

}