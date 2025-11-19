document.addEventListener('DOMContentLoaded', function () {
        
    const chatWindow = document.getElementById('chat-window');

    function addCopyButtons(container) {
        container.querySelectorAll('pre').forEach(pre => {
            if (pre.querySelector('.copy-code-btn')) return;
            const button = document.createElement('button');
            button.className = 'copy-code-btn';
            button.textContent = 'Copy';
            pre.appendChild(button);
        });
    }

    function renderMarkdown(element, text) {
        element.innerHTML = DOMPurify.sanitize(marked.parse(text));
        addCopyButtons(element);
        element.querySelectorAll('pre code').forEach((block) => {
            hljs.highlightElement(block);
        });
    }

    document.querySelectorAll('.ai-content-raw').forEach(el => {
        const rawText = el.textContent;
        const previewEl = el.nextElementSibling;
        renderMarkdown(previewEl, rawText);
        el.remove();
    });
    
    const chatForm = document.getElementById('chat-form');
    const messageInput = document.getElementById('message-input');
    const sendButton = document.getElementById('send-button');
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    let loadingIndicator = null;

    function scrollToBottom() {
        chatWindow.scrollTop = chatWindow.scrollHeight;
    }
    
    scrollToBottom();
    
    chatForm.addEventListener('submit', function (e) {
        e.preventDefault();

        const message = messageInput.value.trim();
        if (message === '') return;
        
        messageInput.style.height = '56px';
        const welcomeScreen = chatWindow.querySelector('.flex-col.items-center');
        if(welcomeScreen) welcomeScreen.remove();
        
        appendMessage(message, 'user');
        messageInput.value = '';
        toggleForm(true);
        
        loadingIndicator = appendMessage('...', 'ai');
        
        fetch("{{ route('chat.send') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ message: message })
        })
        .then(response => {
            if (!response.ok) return response.json().then(err => { throw new Error(err.error || response.statusText); });
            return response.json();
        })
        .then(data => {
            if (loadingIndicator) { loadingIndicator.remove(); loadingIndicator = null; }

            if(data.reply) {
                appendMessage(data.reply, 'ai');
                if(data.newChatInfo) window.location.reload();
            } else if(data.error) {
                appendMessage(`Error: ${data.error}`, 'ai-error');
            }
            toggleForm(false);
        })
        .catch(error => {
            if (loadingIndicator) { loadingIndicator.remove(); loadingIndicator = null; }
            console.error('Error:', error);
            appendMessage(`Koneksi bermasalah: ${error.message}`, 'ai-error');
            toggleForm(false);
        });
    });
    
    function toggleForm(isLoading) {
        messageInput.disabled = isLoading;
        sendButton.disabled = isLoading;
        if(isLoading) {
            sendButton.classList.add('opacity-50', 'cursor-not-allowed');
        } else {
            sendButton.classList.remove('opacity-50', 'cursor-not-allowed');
        }
    }

    function appendMessage(message, sender) {
        const wrapper = document.createElement('div');
        wrapper.classList.add('flex', 'items-start', 'space-x-4', 'max-w-4xl', 'mx-auto', 'mb-6');
        
        const avatar = document.createElement('div');
        avatar.classList.add('flex-shrink-0', 'h-8', 'w-8', 'rounded-full', 'flex', 'items-center', 'justify-center', 'font-bold', 'text-xs', 'text-white', 'mt-1', 'shadow-md');

        const contentContainer = document.createElement('div');
        
        if (sender === 'user') {
            wrapper.classList.add('justify-end', 'group');
            contentContainer.classList.add('max-w-[85%]', 'sm:max-w-[75%]');
            
            const bubble = document.createElement('div');
            bubble.classList.add('bg-[#28292C]', 'text-gray-100', 'px-5', 'py-3.5', 'rounded-2xl', 'rounded-tr-none', 'shadow-sm', 'border', 'border-gray-700/50', 'text-base', 'leading-relaxed', 'whitespace-pre-wrap');
            bubble.textContent = message;
            
            avatar.classList.add('bg-gradient-to-br', 'from-gray-600', 'to-gray-700', 'border', 'border-gray-500');
            avatar.textContent = 'U';

            contentContainer.appendChild(bubble);
            wrapper.appendChild(contentContainer);
            wrapper.appendChild(avatar);

        } else {
            wrapper.classList.add('justify-start');
            contentContainer.classList.add('flex-1', 'min-w-0');

            avatar.classList.add('bg-gradient-to-br', 'from-blue-600', 'to-indigo-600', 'shadow-lg', 'shadow-blue-900/20');
            avatar.textContent = 'AI';

            const name = document.createElement('p');
            name.classList.add('text-sm', 'font-bold', 'text-gray-300', 'mb-1');
            name.textContent = 'Aang AI';

            const messageDiv = document.createElement('div');
            
            if (sender === 'ai-error') {
                avatar.classList.remove('from-blue-600', 'to-indigo-600');
                avatar.classList.add('bg-red-600');
                messageDiv.classList.add('text-red-400', 'bg-red-900/10', 'p-3', 'rounded-lg', 'border', 'border-red-800/50');
                messageDiv.textContent = message;
            } else if (message === '...') {
                messageDiv.innerHTML = `<div class="loader-dots mt-2"><div></div><div></div><div></div></div>`;
            } else {
                messageDiv.classList.add('markdown-body');
                renderMarkdown(messageDiv, message); 
            }
            
            contentContainer.appendChild(name);
            contentContainer.appendChild(messageDiv);
            wrapper.appendChild(avatar);
            wrapper.appendChild(contentContainer);
        }
        
        chatWindow.appendChild(wrapper);
        scrollToBottom();
        
        return wrapper;
    }

    chatWindow.addEventListener('click', function(e) {
        if (e.target.classList.contains('copy-code-btn')) {
            const pre = e.target.closest('pre');
            const code = pre.querySelector('code');
            const btn = e.target;

            navigator.clipboard.writeText(code.textContent).then(() => {
                btn.textContent = 'Copied!';
                btn.classList.add('copied');
                setTimeout(() => {
                    btn.textContent = 'Copy';
                    btn.classList.remove('copied');
                }, 2000);
            }).catch(err => {
                btn.textContent = 'Gagal';
                console.error('Gagal menyalin: ', err);
            });
        }
    });
    
});