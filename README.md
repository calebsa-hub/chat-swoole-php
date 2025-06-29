# 💬 Chat em Tempo Real com PHP Swoole, WebSocket, Redis e TailwindCSS

Este é um projeto de chat em tempo real desenvolvido com **PHP Swoole** e **WebSocket**, utilizando **Redis** para persistência das mensagens e **TailwindCSS** para a interface gráfica.

O objetivo deste projeto foi explorar tecnologias de alta performance em PHP e aprender sobre comunicação bidirecional em tempo real.

## 🚀 Tecnologias Utilizadas

* **PHP** com [Swoole](https://www.swoole.co.uk/) (servidor WebSocket nativo)
* **Redis** para persistir o histórico das mensagens
* **WebSocket** para comunicação em tempo real
* **TailwindCSS** via CDN para estilização moderna e responsiva
* **JavaScript** (Front-end)

## 🌟 Funcionalidades

* Chat em tempo real via WebSocket
* Atribuição de nomes personalizados aos usuários
* Cada usuário recebe uma **cor aleatória** no nome
* Armazenamento das últimas 100 mensagens no Redis
* Filtro para impedir o envio de números de telefone
* Recuperação automática do histórico das últimas mensagens ao entrar no chat
* Interface simples e responsiva com TailwindCSS

## 📂 Estrutura do Projeto

```
chat-swoole/
├── public/
│   ├── index.html      # Interface do chat
│   ├── app.js          # Lógica de front-end (WebSocket)
│   └── style.css       # Customizações de estilo (opcional)
├── server.php          # Servidor WebSocket com PHP Swoole
└── README.md           # Este arquivo
```

## ⚙️ Como Executar Localmente

### Pré-requisitos

* PHP com Swoole instalado
* Redis instalado e em execução
* Servidor web local (ex: `php -S localhost:8000 -t public`)

### Passos:

1. Clone o repositório:

```bash
git clone https://github.com/seuusuario/chat-swoole.git
cd chat-swoole
```

2. Inicie o servidor WebSocket:

```bash
php server.php
```

3. Abra um terminal e sirva o `index.html`:

```bash
php -S localhost:8000 -t public
```

4. Acesse no navegador:

```
http://localhost:8000
```

> 🔹 Obs: Certifique-se de que o Redis está rodando com o comando `redis-cli ping` (deve retornar `PONG`).

---

## 🔒 Segurança

* O projeto inclui um **filtro básico para impedir o envio de números de telefone**.
* Pode ser estendido com filtros adicionais para palavras ofensivas ou integração com bibliotecas de moderação.

---

## 🎥 Demonstração

[Demonstração](https://youtu.be/AQ82x1kI2tk)

---

## ✍️ Contribuição

Contribuições são bem-vindas! Sinta-se à vontade para abrir issues ou enviar pull requests com melhorias.

---

## 📢 Contato

Me acompanhe no [LinkedIn](https://www.linkedin.com/in/calebs-ferreira/) ou entre em contato pelo e-mail: [**calebs.ferreira@gmail.com**](mailto:calebs.ferreira@gmail.com)
