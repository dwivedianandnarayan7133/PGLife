<!-- AI Chatbot Assistant Floating Widget -->
<div id="pglifeChatbotWidget" class="chatbot-widget-container">
    <div id="chatbotWindow" class="chatbot-window" style="display:none;">
        <div class="chatbot-header">
            <div class="chatbot-bot-info">
                <div class="chatbot-avatar"><i class="fas fa-robot"></i></div>
                <div>
                    <div class="chatbot-name">PGLife AI Assistant</div>
                    <div class="chatbot-status"><span class="status-dot"></span> Online & Ready</div>
                </div>
            </div>
            <button class="chatbot-close-btn" onclick="toggleChatbot()"><i class="fas fa-times"></i></button>
        </div>
        
        <div id="chatbotMessages" class="chatbot-messages">
            <div class="chat-msg bot-msg">
                👋 Hello! I am your PGLife AI Assistant. I can help you find the best PG or Hostel accommodation based on your budget, city, university, gender, or room type!
            </div>
            <div class="chat-prompt-pills">
                <button class="chat-pill" onclick="sendQuickPrompt('Find PG under 8000 in Delhi')">PG under ₹8k Delhi</button>
                <button class="chat-pill" onclick="sendQuickPrompt('Girls PG near DU')">Girls PG near DU</button>
                <button class="chat-pill" onclick="sendQuickPrompt('Compare top PGs')">Compare PGs</button>
                <button class="chat-pill" onclick="sendQuickPrompt('What is security deposit policy?')">Security Deposit FAQ</button>
            </div>
        </div>

        <div class="chatbot-input-area">
            <input type="text" id="chatbotInput" placeholder="Ask AI Assistant (e.g., 'Girls PG in Delhi under 10k')..." autocomplete="off">
            <button id="chatbotSendBtn" onclick="submitChatbotMsg()"><i class="fas fa-paper-plane"></i></button>
        </div>
    </div>

    <button id="chatbotToggleBtn" class="chatbot-fab" onclick="toggleChatbot()">
        <i class="fas fa-comments"></i>
        <span class="chatbot-fab-badge">AI</span>
    </button>
</div>

<style>
.chatbot-widget-container { position: fixed; bottom: 24px; right: 24px; z-index: 9999; font-family: 'Open Sans', sans-serif; }
.chatbot-fab {
    width: 60px; height: 60px; border-radius: 50%; background: linear-gradient(135deg, #0284c7, #0369a1);
    color: white; border: none; box-shadow: 0 10px 25px -5px rgba(2, 132, 199, 0.5); cursor: pointer;
    font-size: 24px; display: flex; align-items: center; justify-content: center; position: relative;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.chatbot-fab:hover { transform: scale(1.08); box-shadow: 0 15px 30px -5px rgba(2, 132, 199, 0.7); }
.chatbot-fab-badge {
    position: absolute; top: -2px; right: -2px; background: #ef4444; color: white;
    font-size: 10px; font-weight: 700; padding: 2px 6px; border-radius: 10px; border: 2px solid white;
}
.chatbot-window {
    position: absolute; bottom: 74px; right: 0; width: 380px; height: 520px;
    background: #ffffff; border-radius: 20px; box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.2);
    display: flex; flex-direction: column; overflow: hidden; border: 1px solid #e2e8f0;
}
.chatbot-header {
    background: linear-gradient(135deg, #0f172a, #1e293b); color: white; padding: 14px 18px;
    display: flex; align-items: center; justify-content: space-between;
}
.chatbot-bot-info { display: flex; align-items: center; gap: 12px; }
.chatbot-avatar {
    width: 38px; height: 38px; border-radius: 50%; background: #0284c7; display: flex;
    align-items: center; justify-content: center; font-size: 18px; color: white;
}
.chatbot-name { font-weight: 700; font-size: 15px; }
.chatbot-status { font-size: 11px; color: #94a3b8; display: flex; align-items: center; gap: 6px; }
.status-dot { width: 8px; height: 8px; border-radius: 50%; background: #22c55e; }
.chatbot-close-btn { background: none; border: none; color: #94a3b8; font-size: 18px; cursor: pointer; }
.chatbot-close-btn:hover { color: white; }
.chatbot-messages { flex: 1; padding: 16px; overflow-y: auto; background: #f8fafc; display: flex; flex-direction: column; gap: 12px; }
.chat-msg { max-width: 82%; padding: 10px 14px; border-radius: 14px; font-size: 13px; line-height: 1.5; }
.bot-msg { background: #ffffff; color: #334155; border-bottom-left-radius: 2px; box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04); border: 1px solid #e2e8f0; }
.user-msg { background: #0284c7; color: white; border-bottom-right-radius: 2px; align-self: flex-end; }
.chat-prompt-pills { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 6px; }
.chat-pill {
    background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; padding: 5px 10px;
    border-radius: 16px; font-size: 11px; font-weight: 600; cursor: pointer; transition: all 0.2s;
}
.chat-pill:hover { background: #0284c7; color: white; }
.chatbot-input-area { padding: 12px; background: #ffffff; border-top: 1px solid #e2e8f0; display: flex; gap: 8px; }
#chatbotInput { flex: 1; border: 1px solid #cbd5e1; border-radius: 20px; padding: 8px 14px; font-size: 13px; outline: none; }
#chatbotInput:focus { border-color: #0284c7; }
#chatbotSendBtn { width: 36px; height: 36px; border-radius: 50%; background: #0284c7; color: white; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; }
.chat-prop-card {
    background: white; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px; margin-top: 8px;
    font-size: 12px; color: #1e293b; display: flex; flex-direction: column; gap: 4px;
}
.chat-prop-card-title { font-weight: 700; color: #0284c7; }
.chat-prop-card-rent { font-weight: 700; color: #16a34a; }
.chat-prop-card-link {
    display: inline-block; background: #0284c7; color: white; padding: 4px 8px;
    border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 11px; margin-top: 4px; text-align: center;
}
</style>
