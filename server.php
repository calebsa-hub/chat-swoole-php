<?php

$redis = new Redis();
$redis->connect('127.0.0.1', 6379);

$server = new Swoole\WebSocket\Server("0.0.0.0", 9502);

$clients = [];

$server->on('open', function ($server, $request) use (&$clients, $redis) {
    echo "Nova conexão: ID {$request->fd}\n";
    
    $clients[$request->fd] = ['name' => null];

    $messages = $redis->lRange('chat:messages', -50, -1);
    foreach ($messages as $msg) {
        $server->push($request->fd, $msg);
    }
});

$server->on('message', function ($server, $frame) use (&$clients, $redis) {
    $data = json_decode($frame->data, true);

    if ($data['type'] === 'join') {
        $clients[$frame->fd]['name'] = $data['name'];
        $name = $clients[$frame->fd]['name'];

        $joinMessage = [
            'type' => 'join',
            'text' => "{$name} entrou no chat."
        ];

        $redis->rPush('chat:messages', json_encode($joinMessage));

        foreach ($clients as $fd => $client) {
            if ($server->isEstablished($fd)) {
                $server->push($fd, json_encode($joinMessage));
            }
        }

        return;
    }

    if ($data['type'] === 'message') {
        $name = $clients[$frame->fd]['name'];

        $messageData = [
            'type' => 'message',
            'name' => $name,
            'text' => $data['text']
        ];

        $redis->rPush('chat:messages', json_encode($messageData));
        $redis->lTrim('chat:messages', -100, -1); // Limitar o histórico a 100 mensagens

        foreach ($clients as $fd => $client) {
            if ($server->isEstablished($fd)) {
                $server->push($fd, json_encode($messageData));
            }
        }
    }
});

$server->on('close', function ($server, $fd) use (&$clients, $redis) {
    if (isset($clients[$fd])) {
        $name = $clients[$fd]['name'];
        unset($clients[$fd]);

        $leaveMessage = [
            'type' => 'leave',
            'text' => "{$name} saiu do chat."
        ];

        $redis->rPush('chat:messages', json_encode($leaveMessage));

        foreach ($clients as $clientFd => $client) {
            if ($server->isEstablished($clientFd)) {
                $server->push($clientFd, json_encode($leaveMessage));
            }
        }
    }

    echo "Conexão fechada: ID {$fd}\n";
});

$server->start();