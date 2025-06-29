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
    $uri = $request->server['request_uri'];

    // Serve index.html na raiz
    if ($uri === "/") {
        $indexFile = file_get_contents(__DIR__ . '/public/index.html');
        $response->header("Content-Type", "text/html");
        $response->end($indexFile);
        return;
    }

    // Serve arquivos estáticos (CSS, JS, imagens, etc.)
    $filePath = __DIR__ . '/public' . $uri;

    if (file_exists($filePath)) {
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);

        // Define o Content-Type correto
        $mimeTypes = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'ico' => 'image/x-icon',
            'svg' => 'image/svg+xml',
        ];

        if (isset($mimeTypes[$extension])) {
            $response->header('Content-Type', $mimeTypes[$extension]);
        } else {
            $response->header('Content-Type', 'application/octet-stream');
        }

        $response->end(file_get_contents($filePath));
        return;
    }

    // Healthcheck
    if ($uri === '/health') {
        $response->end('OK');
        return;
    }

    // Se não encontrar o arquivo, responde 404
    $response->status(404);
    $response->end("Página não encontrada");
});

$server->start();