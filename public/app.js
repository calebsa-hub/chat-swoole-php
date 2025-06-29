let ws;
let username = '';
let userColor = '';

function enterChat() {
    username = document.getElementById('username').value.trim();
    userColor = getRandomColorClass();

    if (username !== '') {
        ws = new WebSocket('ws://localhost:9502');

        ws.onopen = function() {
            document.getElementById('login').style.display = 'none';
            document.getElementById('chat').style.display = 'block';
            ws.send(JSON.stringify({ type: 'join', name: username, color: userColor }));
        };

        ws.onmessage = function(event) {
            const data = JSON.parse(event.data);
            const msgBox = document.getElementById('messages');

            const message = document.createElement('div');
            message.classList.add('mb-2');

            if (data.type === 'message') {
                message.innerHTML = `<strong class="${data.color}">${data.name}:</strong> ${data.text}`;
            } else if (data.type === 'join' || data.type === 'leave') {
                message.textContent = data.text;
                message.style.fontStyle = 'italic';
                message.style.color = 'gray';
            }

            msgBox.appendChild(message);
            msgBox.scrollTop = msgBox.scrollHeight;
        };
    }
}

function sendMessage() {
    const input = document.getElementById('message');
    if (input.value.trim() !== '') {
        ws.send(JSON.stringify({ type: 'message', name: username, text: input.value }));
        input.value = '';
    }
}

function getRandomColorClass() {
    const colors = ['text-red-500', 'text-green-500', 'text-blue-500', 'text-yellow-500', 'text-purple-500', 'text-pink-500', 'text-indigo-500', 'text-orange-500'];
    return colors[Math.floor(Math.random() * colors.length)];
}