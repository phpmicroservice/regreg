<?php

namespace regreg;

use Phalcon\Events\Event;
use pms\Output;

/**
 * 注册服务
 * Class Register
 * @property \swoole_client $register_client

 * @package pms
 */
class Register
{
    private $register_client;
    private $regclient_ip;
    private $regserver_port;
    private $reg_status = false;
    private $option = SD_OPTION;


    /**
     * 配置初始化
     */
    public function __construct(\Swoole\Server $server)
    {
        if (is_string(\pms\env_exist(['REGISTER_SECRET_KEY', 'REGISTER_ADDRESS', 'REGISTER_PORT']))) {
            Output::error('缺少必要的环境变量!');
            $server->shutdown();
        }

        $this->regclient_ip = \pms\get_env('REGISTER_ADDRESS', 'pms_register');
        $this->regserver_port = \pms\get_env('REGISTER_PORT', '9502');


        $this->get_register_client();
        $obj = $this;
        $this->start();

        swoole_timer_tick(5000, function ($timeid) use ($obj) {
            # 进行ping
            $obj->ping();
        });

    }
    /**
     * 开始,链接服务器
     */
    public function start($timeout = 10)
    {
        if (!$this->isConnected) {
            \pms\Output::debug([$this->isConnected, $this->server_ip, $this->server_port], 'client_start');
            return $this->register_client->connect($this->regclient_ip, $this->regserver_port, $timeout);
        }
        return true;

    }

    /**
     * 配置更新
     */
    public function ping()
    {
        if($this->isConnected()){
        $data = [
            'name' => strtolower(SERVICE_NAME),
            'host' => APP_HOST_IP,
            'port' => APP_HOST_PORT,
            'type' => 'tcp'
        ];
        Output::info('ping', 'ping');
        if ($this->reg_status) {
            # 注册完毕进行ping
            $data2=[
                's' => 'reg',
                'r' => '/service/ping',
                'd' => $data
            ];
        } else {
            # 没有注册完毕,先注册
            $data2=[
                's' => 'reg',
                'r' => '/service/reg',
                'd' => $data
            ];
        }
        $re = $this->register_client->send($this->encode($data2));
        if ($re === false) {
            $this->register_client->start();
        }
        \pms\output($re, "ping_re");
        }

       

    }



    /**
     * 发送一个请求
     * @param $router
     * @param $data
     * @return bool
     */
    public function send_ask($server, $router, $data)
    {
        return $this->send();
    }





    /**
     * 获取一个swoole 客户端
     */
    private function get_register_client()
    {
        \pms\Output::debug('get_register_client');
        if ($this->register_client instanceof \Swoole\Client) {
        } else {
            $this->register_client = new \Swoole\Client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);
        }
        $this->register_client->set($this->option);

        $this->register_client->on("connect", [$this, 'connect']);
        $this->register_client->on("receive", [$this, 'receive']);
        $this->register_client->on("error", [$this, 'error']);
        $this->register_client->on("close", [$this, 'close']);
        $this->register_client->on("bufferFull", [$this, 'bufferFull']);
        $this->register_client->on("bufferEmpty", [$this, 'bufferEmpty']);
    }



    /**
     * 发送数据
     * @param $data
     */
    public function send($router, $data)
    {


        return $this->register_client->send([
            $router, $data
        ]);
    }


    /**
     * 判断链接
     * @return bool
     */
    public function isConnected()
    {
        if($this->isConnected){
            return true;
        }
        $this->connect();
        return false;
    }





    /**
     * 当缓存区低于最低水位线时触发此事件。
     */
    public function bufferEmpty(\Swoole\Client $client)
    {
        //$this->call('bufferEmpty', $client);

    }

    /**
     * 当缓存区达到最高水位时触发此事件。
     */
    public function bufferFull(\Swoole\Client $client)
    {
       // $this->call('bufferFull', $client);
    }



    /**
     * 链接出错的
     * @param \register_client $client
     */
    public function error(\Swoole\Client $client)
    {
        \pms\Output::error(['register error'], 'error');
        //$this->call('error', $client);


    }

    /**
     * 当链接关闭
     * @param \register_client $client
     */
    public function close(\Swoole\Client $client)
    {
        $this->isConnected = false;
        \pms\Output::info('client server close');
        //$this->call('close', $client);
    }


    /**
     * 链接成功
     * @param \register_client $client
     */
    public function connect(\Swoole\Client $client)
    {
        $this->isConnected = true;
        //$this->call('connect', $client);

    }


    /**
     * 收到值,真实
     * @param \register_client $cli
     * @param $data
     */
    public function receive(\Swoole\Client $client, $data_string)
    {
        //$this->call('receive', $client, $data_string);
        //$this->save($data);
    }



    /**
     * 保存
     * @param $data
     */
    private function save($data)
    {
        $type = $data['t'];
        if ($type == '/service/reg') {
            $this->reg_status = 1;
        }

    }




    /**
     * 编码
     * @param array $data
     * @return string
     */
    private function encode(array $data): string
    {
        $msg_normal = \pms\Serialize::pack($data);
        $msg_length = pack("N", strlen($msg_normal)) . $msg_normal;
        return $msg_length;
    }

    /**
     * 解码
     * @param $string
     */
    private function decode($data): array
    {
        $length = unpack("N", $data)[1];
        $msg = substr($data, -$length);
        return \pms\Serialize::unpack($msg);
    }


}