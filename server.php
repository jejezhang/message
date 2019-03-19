<?php
ini_set('date.timezone','Asia/Shanghai');
class webSocket
{
    public $server;
    public $redis;
    public $clientKey;

    public function __construct()
    {
        $this->server = new \Swoole\WebSocket\Server("0.0.0.0", 8888, SWOOLE_PROCESS, SWOOLE_SOCK_TCP | SWOOLE_SSL);

        $this->server->on('open', [$this, 'onOpen']);
        $this->server->on('message', [$this, 'onMessage']);
        $this->server->on('close', [$this, 'onClose']);
        $this->server->on('request', [$this, 'onRequest']);

       $this->server->set([
           'ssl_key_file' => '/usr/local/nginx/conf/1842744_chat.jeje.me.key',
           'ssl_cert_file' => '/usr/local/nginx/conf/1842744_chat.jeje.me.pem',
           'daemonize' => 0,
        ]);

        $this->server->start();
    }

    public function onOpen($server, $request)
    {
        $clientId = $request->get['client_id'] ?? '';
        /*if (!empty($clientId)) {
          $this->clientKey = 'client';
          $this->redis = new \Swoole\Redis;
          $this->redis->connect('127.0.0.1', 6379, function ($client, $result) use ($server, $request, $clientId) {
              if ($result === false) {
                  echo "connect to redis server failed.\n";
                  return;
              }
              $client->set($this->clientKey . $clientId, $request->fd, function ($client, $result) {
                  var_dump($result);
              });
          });
      }*/

        echo "server: handshake success with fd{$request->fd},客户端ID{$clientId}\n";

        $arr = [
            'from' => 'sendFd',
            'fd' => $request->fd,
        ];

        $server->push($request->fd, json_encode($arr));


        foreach ($server->connections as $fd) {
            if ($server->exist($fd) && $fd != $request->fd) {
                $server->push($fd, json_encode([
                    'from'      => 'onLine',
                    'message'   => 'FD:' . $request->fd . '上线了',
                    ]));
            }
        }
    }

    public function onMessage($server, $frame)
    {
        echo "receive from {$frame->fd}:{$frame->data},opcode:{$frame->opcode},fin:{$frame->finish}\n";

        $data = json_decode($frame->data, true);

        $arr = [
            'from'      => $data['type'],
            'fd'        => $frame->fd,
            'name'      => $data['name'],
            'img'       => $data['img'] ?? '',
            'time'      => date('Y-m-d H:i:s'),
            'message'   => $data['message']
        ];

        if ($data['oneByOneFd']) {
            if ($server->exist($data['oneByOneFd'])) {
                $server->push($data['oneByOneFd'], json_encode($arr));
                $server->push($frame->fd, json_encode($arr));
            } else {
                $server->push($frame->fd, json_encode(['from' => 'close']));
            }
        } else {
            foreach ($server->connections as $fd) {
                if ($server->exist($fd)) {
                    $server->push($fd, json_encode($arr));
                }
            }
        }
    }

    public function onRequest($request, $response)
    {
        $response->header('Access-Control-Allow-Origin', '*');
        $response->header('Content-Type', 'application/json');

        if ($request->server['request_uri'] == '/favicon.ico'
            ||
            $request->server['path_info'] == '/favicon.ico'
        ) {
            return $response->end('{"code":-1, "msg":"Favicon Filter", "data":null}');
        }

        $params = $request->post;

        if (empty($params)) {
           return $response->end('{"code":-1, "msg":"Data Is Invalid", "data":null}');
        }

        $pic = base64_decode($params['pic'], true);

        $imagename = date('YmdHi').uniqid();
        $path = './img/'.$imagename. '.png';
        file_put_contents($path, $pic);

        $result['url'] =  './img/'.$imagename. '.png';

        return $response->end('{"code":0, "msg":"success", "data":'.$result['url'].'}');
    }

    public function onClose($server, $fd)
    {
        echo "client {$fd} closed\n";
    }
}

(new webSocket());