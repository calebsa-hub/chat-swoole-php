# Usar uma imagem PHP com suporte ao Swoole
FROM phpswoole/swoole:latest

# Instalar Redis CLI (opcional, caso precise)
RUN apt-get update && apt-get install -y redis-tools

# Criar diretório de trabalho
WORKDIR /app

# Copiar os arquivos do projeto para o container
COPY . .

# Expor a porta (Railway injeta a variável PORT)
EXPOSE 8000

# Comando para iniciar o servidor
CMD ["php", "server.php"]