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

    // Verificar se a mensagem é do tipo "join"
    if ($data['type'] === 'join') {
        $clients[$frame->fd]['name'] = $data['name'];

        $joinMessage = "{$data['name']} entrou no chat.";
        $redis->rPush('chat:messages', $joinMessage);

        // Avisar a todos que o usuário entrou
        foreach ($clients as $fd => $client) {
            if ($server->isEstablished($fd)) {
                $server->push($fd, $joinMessage);
            }
        }
        return;
    }

    // Se for mensagem normal
    if ($data['type'] === 'message') {
        $name = $clients[$frame->fd]['name'];
        $message = "{$name}: {$data['text']}";

        $redis->rPush('chat:messages', $message);

        $redis->lTrim('chat:messages', -100, -1);


        foreach ($clients as $fd => $client) {
            if ($server->isEstablished($fd)) {
                $server->push($fd, $message);
            }
        }
    }
});

$server->on('close', function ($server, $fd) use (&$clients, $redis) {
    if (isset($clients[$fd])) {
        $name = $clients[$fd]['name'];
        unset($clients[$fd]);

        $leaveMessage = "{$name} saiu do chat.";
        $redis->rPush('chat:messages', $leaveMessage);

        foreach ($clients as $clientFd => $client) {
            if ($server->isEstablished($clientFd)) {
                $server->push($clientFd, $leaveMessage);
            }
        }
    }
    echo "Conexão fechada: ID {$fd}\n";
});

$server->start();
