document.addEventListener('DOMContentLoaded', function() {
    const windowEl = document.getElementById('chatbotWindow');
    const messagesEl = document.getElementById('chatbotMessages');
    const inputEl = document.getElementById('chatbotInput');

    if (!windowEl || !messagesEl || !inputEl) return;

    window.toggleChatbot = function() {
        if (windowEl.style.display === 'none' || !windowEl.style.display) {
            windowEl.style.display = 'flex';
            inputEl.focus();
        } else {
            windowEl.style.display = 'none';
        }
    };

    window.sendQuickPrompt = function(promptText) {
        inputEl.value = promptText;
        submitChatbotMsg();
    };

    window.submitChatbotMsg = function() {
        const text = inputEl.value.trim();
        if (!text) return;

        appendMsg('user', text);
        inputEl.value = '';

        // Typing indicator
        const typingId = 'typing_' + Date.now();
        const typingEl = document.createElement('div');
        typingEl.className = 'chat-msg bot-msg';
        typingEl.id = typingId;
        typingEl.innerHTML = '<i class="fas fa-ellipsis-h fa-spin"></i> PGLife AI is thinking...';
        messagesEl.appendChild(typingEl);
        messagesEl.scrollTop = messagesEl.scrollHeight;

        fetch('api/chatbot.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ message: text })
        })
        .then(res => res.json())
        .then(data => {
            document.getElementById(typingId)?.remove();
            let botContent = data.reply || 'Here is what I found for you:';
            
            // If properties returned
            if (data.properties && data.properties.length > 0) {
                botContent += '<div class="chat-props-list">';
                data.properties.forEach(p => {
                    botContent += `
                        <div class="chat-prop-card">
                            <div class="chat-prop-card-title">${p.name} (${p.city_name})</div>
                            <div>📍 ${p.address}</div>
                            <div class="chat-prop-card-rent">₹${parseInt(p.rent).toLocaleString()}/month • ${p.gender.toUpperCase()}</div>
                            <a href="property_detail.php?property_id=${p.id}" class="chat-prop-card-link" target="_blank">View Details & Book</a>
                        </div>
                    `;
                });
                botContent += '</div>';
            }

            // If comparison returned
            if (data.comparison && data.comparison.length >= 2) {
                const p1 = data.comparison[0];
                const p2 = data.comparison[1];
                botContent += `
                    <div style="margin-top: 8px; font-size: 11px; background: #f1f5f9; padding: 8px; border-radius: 8px;">
                        <strong>Compare: ${p1.name} vs ${p2.name}</strong><br>
                        • Rent: ₹${p1.rent} vs ₹${p2.rent}<br>
                        • Cleanliness: ${p1.rating_clean}★ vs ${p2.rating_clean}★<br>
                        • Safety: ${p1.rating_safety}★ vs ${p2.rating_safety}★
                    </div>
                `;
            }

            appendMsg('bot', botContent);
        })
        .catch(err => {
            document.getElementById(typingId)?.remove();
            appendMsg('bot', 'Sorry, I encountered an error connecting to the AI server. Please try again!');
        });
    };

    inputEl.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') submitChatbotMsg();
    });

    function appendMsg(sender, content) {
        const el = document.createElement('div');
        el.className = 'chat-msg ' + (sender === 'user' ? 'user-msg' : 'bot-msg');
        el.innerHTML = content;
        messagesEl.appendChild(el);
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }
});
