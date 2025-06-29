<?php

$redisHost = getenv('REDIS_HOST');
$redisPort = getenv('REDIS_PORT');

$redis = new Redis();
$redis->connect($redisHost, $redisPort);

$port = getenv('PORT') ?: 8080; // Usa a porta do Railway ou 8000 padrão
$server = new Swoole\WebSocket\Server("0.0.0.0", $port);


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
        $clients[$frame->fd]['color'] = $data['color'];

        $name = $clients[$frame->fd]['name'];
        $color = $clients[$frame->fd]['color'];

        $joinMessage = [
            'type' => 'join',
            'name' => $name,
            'color' => $color,
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
        $color = $clients[$frame->fd]['color'];
        $message = $data['text'];

        if (preg_match('/\d{8,}/', $message)) {
            $server->push($frame->fd, json_encode([
                'type' => 'message',
                'name' => 'Sistema',
                'color' => 'text-gray-700',
                'text' => '🚫 Sua mensagem foi bloqueada por conter números suspeitos.'
            ]));
            return;
        }

        $messageData = [
            'type' => 'message',
            'name' => $name,
            'color' => $color,
            'text' => $message
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

$server->on("request", function ($request, $response) {
    if ($request->server['request_uri'] === '/') {
        // Ler o arquivo index.html e retornar seu conteúdo
        $indexFile = file_get_contents('/app/public/index.html');  // Ou o caminho correto do seu index.html
        $response->header("Content-Type", "text/html");  // Definindo o tipo do conteúdo como HTML
        $response->end($indexFile);  // Envia o conteúdo do index.html
    } 

    if ($request->server['request_uri'] === '/health') {
        $response->end('OK');  // Resposta simples para o Healthcheck
    } else {
        $response->end("Olá, mundo!");
    }
});

$server->start();