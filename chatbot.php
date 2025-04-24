<!-- Floating Chatbot Widget -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
#chatbot-fab {
    position: fixed;
    bottom: 32px;
    right: 32px;
    z-index: 2000;
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: linear-gradient(135deg, #6C63FF, #4D44DB);
    color: #fff;
    box-shadow: 0 8px 24px rgba(76,99,255,0.18);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    cursor: pointer;
    transition: box-shadow 0.3s;
}
#chatbot-fab:hover {
    box-shadow: 0 12px 36px rgba(76,99,255,0.28);
}
#chatbot-window {
    position: fixed;
    bottom: 110px;
    right: 32px;
    width: 350px;
    max-width: 95vw;
    height: 500px;
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 8px 32px rgba(44,62,80,0.18);
    display: none;
    flex-direction: column;
    z-index: 2100;
    overflow: hidden;
    border: 1px solid #ececec;
}
#chatbot-window.active {
    display: flex;
}
#chatbot-header {
    background: linear-gradient(135deg, #6C63FF, #4D44DB);
    color: #fff;
    padding: 1rem;
    font-weight: 600;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
#chatbot-messages {
    flex: 1;
    padding: 1rem;
    overflow-y: auto;
    background: #f7f8fa;
}
#chatbot-input-area {
    padding: 0.75rem 1rem;
    background: #f4f4f4;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
#chatbot-input {
    flex: 1;
    border: none;
    border-radius: 8px;
    padding: 0.5rem 1rem;
    font-size: 1rem;
    background: #fff;
    box-shadow: 0 2px 6px rgba(0,0,0,0.03);
}
#chatbot-send {
    background: #6C63FF;
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 0.5rem 1rem;
    font-size: 1rem;
    cursor: pointer;
    transition: background 0.2s;
}
#chatbot-send:disabled {
    background: #b3b3fa;
    cursor: not-allowed;
}
.chatbot-msg {
    margin-bottom: 1rem;
    display: flex;
    align-items: flex-end;
    gap: 0.5rem;
}
.chatbot-msg.user {
    flex-direction: row-reverse;
}
.chatbot-msg .msg {
    max-width: 75%;
    padding: 0.7rem 1rem;
    border-radius: 16px;
    font-size: 1rem;
    line-height: 1.45;
    background: #6C63FF;
    color: #fff;
    word-break: break-word;
}
.chatbot-msg.user .msg {
    background: #4D44DB;
    color: #fff;
}
.chatbot-msg.bot .msg {
    background: #f1f1f9;
    color: #222;
}
</style>
<div id="chatbot-fab" title="Chat with Neighborhood Watch Bot">
    <i class="fas fa-comments"></i>
</div>
<div id="chatbot-window">
    <div id="chatbot-header">
        <span><i class="fas fa-robot me-2"></i> Neighborhood Watch Chatbot</span>
        <button id="chatbot-close" class="btn btn-sm btn-light" style="border-radius:50%"><i class="fas fa-times"></i></button>
    </div>
    <div id="chatbot-messages"></div>
    <form id="chatbot-input-area" autocomplete="off">
        <input type="text" id="chatbot-input" placeholder="Type your question..." autocomplete="off" required />
        <button id="chatbot-send" type="submit"><i class="fas fa-paper-plane"></i></button>
    </form>
</div>
<script>
const fab = document.getElementById('chatbot-fab');
const windowEl = document.getElementById('chatbot-window');
const closeBtn = document.getElementById('chatbot-close');
const form = document.getElementById('chatbot-input-area');
const input = document.getElementById('chatbot-input');
const messages = document.getElementById('chatbot-messages');

fab.onclick = () => windowEl.classList.add('active');
closeBtn.onclick = () => windowEl.classList.remove('active');

function appendMessage(text, sender) {
    const msgDiv = document.createElement('div');
    msgDiv.className = 'chatbot-msg ' + sender;
    msgDiv.innerHTML = `<div class="msg">${text}</div>`;
    messages.appendChild(msgDiv);
    messages.scrollTop = messages.scrollHeight;
}

form.onsubmit = async (e) => {
    e.preventDefault();
    const userMsg = input.value.trim();
    if (!userMsg) return;
    appendMessage(userMsg, 'user');
    input.value = '';
    input.disabled = true;
    document.getElementById('chatbot-send').disabled = true;
    appendMessage('<span class="spinner-border spinner-border-sm text-primary"></span>', 'bot');
    try {
        const res = await fetch('chatbot_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({message: userMsg})
        });
        const data = await res.json();
        messages.removeChild(messages.lastChild); // Remove spinner
        appendMessage(data.reply, 'bot');
    } catch (err) {
        messages.removeChild(messages.lastChild);
        appendMessage('Sorry, there was an error connecting to the chatbot.', 'bot');
    }
    input.disabled = false;
    document.getElementById('chatbot-send').disabled = false;
    input.focus();
};
</script>
