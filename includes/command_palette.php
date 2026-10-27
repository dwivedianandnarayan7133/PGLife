<!-- Site Command / Quick Search Palette Modal -->
<div id="commandPaletteModal" class="cmd-modal-backdrop" style="display:none;">
    <div class="cmd-modal-container">
        <div class="cmd-header">
            <i class="fas fa-search cmd-search-icon"></i>
            <input type="text" id="cmdSearchInput" placeholder="Type a command or search (e.g. 'under 8000', 'girls PG near DU', 'AC hostels')..." autocomplete="off">
            <span class="cmd-kbd-badge">ESC</span>
        </div>
        <div class="cmd-quick-pills">
            <button class="cmd-pill" onclick="executeCmdQuery('under 8000')"><i class="fas fa-tag"></i> Under ₹8,000</button>
            <button class="cmd-pill" onclick="executeCmdQuery('girls PG near University')"><i class="fas fa-venus"></i> Girls PG</button>
            <button class="cmd-pill" onclick="executeCmdQuery('AC')"><i class="fas fa-snowflake"></i> AC Hostels</button>
            <button class="cmd-pill" onclick="executeCmdQuery('Wifi')"><i class="fas fa-wifi"></i> WiFi</button>
        </div>
        <div id="cmdResultsList" class="cmd-results">
            <!-- Populated dynamically via JS -->
        </div>
        <div class="cmd-footer">
            <span><i class="fas fa-arrow-up"></i> <i class="fas fa-arrow-down"></i> Navigate</span>
            <span><i class="fas fa-level-down-alt"></i> Select</span>
            <span>Press <strong>Ctrl + K</strong> anywhere to open command search</span>
        </div>
    </div>
</div>

<style>
.cmd-modal-backdrop {
    position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
    background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(6px);
    z-index: 10000; display: flex; justify-content: center; align-items: flex-start; padding-top: 100px;
}
.cmd-modal-container {
    background: #ffffff; width: 90%; max-width: 650px; border-radius: 16px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); overflow: hidden; border: 1px solid #e2e8f0;
}
.cmd-header {
    display: flex; align-items: center; padding: 16px 20px; border-bottom: 1px solid #f1f5f9; background: #f8fafc;
}
.cmd-search-icon { color: #64748b; font-size: 18px; margin-right: 12px; }
#cmdSearchInput {
    flex: 1; border: none; background: transparent; outline: none; font-size: 16px; color: #1e293b;
}
.cmd-kbd-badge {
    background: #e2e8f0; color: #475569; font-size: 11px; padding: 4px 8px; border-radius: 6px; font-weight: 600;
}
.cmd-quick-pills {
    display: flex; gap: 8px; padding: 12px 20px; background: #ffffff; border-bottom: 1px solid #f1f5f9; flex-wrap: wrap;
}
.cmd-pill {
    background: #f1f5f9; border: none; padding: 6px 12px; border-radius: 20px; font-size: 12px; color: #334155;
    cursor: pointer; transition: all 0.2s ease; display: flex; align-items: center; gap: 6px;
}
.cmd-pill:hover { background: #0284c7; color: #ffffff; }
.cmd-results { max-height: 350px; overflow-y: auto; padding: 8px 0; }
.cmd-item {
    display: flex; align-items: center; padding: 12px 20px; cursor: pointer; transition: background 0.15s ease;
    text-decoration: none; color: #1e293b; border-bottom: 1px solid #f8fafc;
}
.cmd-item:hover, .cmd-item.selected { background: #f0f9ff; color: #0284c7; }
.cmd-item-icon {
    width: 36px; height: 36px; border-radius: 10px; background: #e0f2fe; color: #0284c7;
    display: flex; align-items: center; justify-content: center; font-size: 16px; margin-right: 14px;
}
.cmd-item-info { flex: 1; }
.cmd-item-title { font-weight: 600; font-size: 14px; margin-bottom: 2px; }
.cmd-item-sub { font-size: 12px; color: #64748b; }
.cmd-footer {
    display: flex; justify-content: space-between; padding: 10px 20px; background: #f8fafc;
    border-top: 1px solid #f1f5f9; font-size: 11px; color: #64748b;
}
</style>
