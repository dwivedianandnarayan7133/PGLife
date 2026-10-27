document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('commandPaletteModal');
    const input = document.getElementById('cmdSearchInput');
    const resultsContainer = document.getElementById('cmdResultsList');

    if (!modal || !input) return;

    // Toggle Modal on Ctrl+K
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            toggleCmdModal();
        }
        if (e.key === 'Escape' && modal.style.display === 'flex') {
            closeCmdModal();
        }
    });

    modal.addEventListener('click', function(e) {
        if (e.target === modal) closeCmdModal();
    });

    let debounceTimer;
    input.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            fetchCmdResults(input.value.trim());
        }, 200);
    });

    window.toggleCmdModal = function() {
        if (modal.style.display === 'none' || !modal.style.display) {
            modal.style.display = 'flex';
            input.value = '';
            input.focus();
            fetchCmdResults('');
        } else {
            closeCmdModal();
        }
    };

    window.closeCmdModal = function() {
        modal.style.display = 'none';
    };

    window.executeCmdQuery = function(query) {
        input.value = query;
        fetchCmdResults(query);
    };

    function fetchCmdResults(q) {
        fetch('api/command_search.php?q=' + encodeURIComponent(q))
            .then(res => res.json())
            .then(data => {
                renderResults(data.results || []);
            })
            .catch(err => {
                console.error("Command palette error:", err);
            });
    }

    function renderResults(list) {
        resultsContainer.innerHTML = '';
        if (list.length === 0) {
            resultsContainer.innerHTML = '<div style="padding: 20px; text-align: center; color: #64748b;">No matching commands or properties found.</div>';
            return;
        }

        list.forEach(item => {
            const el = document.createElement('a');
            el.className = 'cmd-item';
            el.href = item.url;
            el.innerHTML = `
                <div class="cmd-item-icon"><i class="fas ${item.icon || 'fa-arrow-right'}"></i></div>
                <div class="cmd-item-info">
                    <div class="cmd-item-title">${item.title}</div>
                    ${item.subtitle ? `<div class="cmd-item-sub">${item.subtitle}</div>` : ''}
                </div>
                <i class="fas fa-chevron-right" style="color: #cbd5e1; font-size: 12px;"></i>
            `;
            resultsContainer.appendChild(el);
        });
    }
});
