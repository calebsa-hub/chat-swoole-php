<?php

$server = new Swoole\WebSocket\Server("0.0.0.0", 9502);

$clients = [];

$server->on('open', function ($server, $request) use (&$clients) {
    echo "Nova conexão: ID {$request->fd}\n";
    // Criar um espaço para o cliente (ainda sem nome)
    $clients[$request->fd] = ['name' => null];
});

$server->on('message', function ($server, $frame) use (&$clients) {
    $data = json_decode($frame->data, true);

    // Verificar se a mensagem é do tipo "join"
    if ($data['type'] === 'join') {
        $clients[$frame->fd]['name'] = $data['name'];
        // Avisar a todos que o usuário entrou
        foreach ($clients as $fd => $client) {
            if ($server->isEstablished($fd)) {
                $server->push($fd, "{$data['name']} entrou no chat.");
            }
        }
        return;
    }

    // Se for mensagem normal
    if ($data['type'] === 'message') {
        $name = $clients[$frame->fd]['name'];
        foreach ($clients as $fd => $client) {
            if ($server->isEstablished($fd)) {
                $server->push($fd, "{$name}: {$data['text']}");
            }
        }
    }
});

$server->on('close', function ($server, $fd) use (&$clients) {
    if (isset($clients[$fd])) {
        $name = $clients[$fd]['name'];
        unset($clients[$fd]);
        foreach ($clients as $clientFd => $client) {
            if ($server->isEstablished($clientFd)) {
                $server->push($clientFd, "{$name} saiu do chat.");
            }
        }
    }
    echo "Conexão fechada: ID {$fd}\n";
});

$server->start();
