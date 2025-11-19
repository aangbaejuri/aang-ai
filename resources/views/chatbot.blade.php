@extends('layouts.app')

@section('title', 'Aang AI - Chat')

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/atom-one-dark.min.css">

<style>
    .markdown-body {
        font-size: 1rem;
        line-height: 1.7;
        color: #e5e7eb;
    }
    .markdown-body p { margin-bottom: 1em; }
    .markdown-body h1, .markdown-body h2, .markdown-body h3 { 
        font-weight: 700; color: #fff; margin-top: 1.5em; margin-bottom: 0.5em; 
    }
    .markdown-body h1 { font-size: 1.5em; }
    .markdown-body h2 { font-size: 1.25em; }
    .markdown-body ul, .markdown-body ol { 
        margin-bottom: 1em; padding-left: 1.5em; 
    }
    .markdown-body ul { list-style-type: disc; }
    .markdown-body ol { list-style-type: decimal; }
    .markdown-body code {
        background-color: #282c34;
        padding: 0.2em 0.4em;
        border-radius: 4px;
        font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, Courier, monospace;
        font-size: 0.9em;
    }
    .markdown-body pre {
        background-color: #282c34;
        padding: 1em;
        border-radius: 8px;
        overflow-x: auto;
        margin-bottom: 1em;
        border: 1px solid #374151;
        padding-top: 2.5rem;
    }
    .markdown-body pre code {
        background-color: transparent;
        padding: 0;
        border-radius: 0;
        color: inherit;
        font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, Courier, monospace;
    }
    .markdown-body strong { color: #fff; font-weight: 600; }
    .markdown-body table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 1em;
    }
    .markdown-body th, .markdown-body td {
        border: 1px solid #374151;
        padding: 0.5em;
        text-align: left;
    }
    .markdown-body th { background-color: #1f2937; color: #fff; }
</style>
@endpush

@section('main_content')

    <header class="bg-[#1A1A1B] px-4 sm:px-6 py-4 shadow-sm z-10 flex items-center justify-between border-b border-gray-800 flex-shrink-0">
        <div class="flex items-center space-x-3">
            <button class="p-2 rounded-lg text-gray-400 hover:bg-gray-800 sm:hidden js-toggle-sidebar">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
            <div class="flex items-center space-x-3">
                <h1 class="text-lg font-bold text-white tracking-wide">Aang AI</h1>
                <span class="text-xs px-2 py-0.5 rounded-full bg-blue-900/30 text-blue-400 border border-blue-800">Beta</span>
            </div>
        </div>
        
        <div class="flex items-center">
            <div class="relative">
                <select id="model-selector" name="model" class="appearance-none bg-[#28292C] border border-gray-700 text-white text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full pl-3 pr-8 py-2.5">
                    @foreach($models as $modelKey => $modelName)
                        <option value="{{ $modelKey }}" {{ $currentModel == $modelKey ? 'selected' : '' }}>
                            {{ $modelName }}
                        </option>
                    @endforeach
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-400">
                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/></svg>
                </div>
            </div>
        </div>
    </header>

    <div id="chat-window" class="flex-1 p-4 sm:p-8 overflow-y-auto space-y-8 scroll-smooth">
        
        @if(empty($history) && !session('current_chat_id'))
            <div class="flex flex-col items-center justify-center h-full text-center space-y-6 opacity-80">
                <div class="h-20 w-20 rounded-2xl bg-gradient-to-br from-blue-600 to-indigo-600 flex items-center justify-center shadow-2xl shadow-blue-900/20">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-white mb-2">Halo, gua Aang AI</h2>
                    <p class="text-gray-400 max-w-md mx-auto">Gua siap bantu jawab pertanyaan lu, nulisin kode, atau ngasih ide kreatif.</p>
                </div>
            </div>
        @else
            @foreach($history as $message)
                @if($message['role'] == 'user')
                    <div class="flex justify-end items-start space-x-4 max-w-4xl mx-auto group">
                        <div class="max-w-[85%] sm:max-w-[75%]">
                            <div class="bg-[#28292C] text-gray-100 px-5 py-3.5 rounded-2xl rounded-tr-none shadow-sm border border-gray-700/50 text-base leading-relaxed whitespace-pre-wrap">{{ $message['parts'][0]['text'] }}</div>
                        </div>
                        <div class="flex-shrink-0 h-8 w-8 rounded-full bg-gradient-to-br from-gray-600 to-gray-700 border border-gray-500 flex items-center justify-center font-bold text-xs text-white shadow-md mt-1">ME</div>
                    </div>
                @else
                    <div class="flex justify-start items-start space-x-4 max-w-4xl mx-auto">
                        <div class="flex-shrink-0 h-8 w-8 rounded-full bg-gradient-to-br from-blue-600 to-indigo-600 flex items-center justify-center font-bold text-xs text-white shadow-lg shadow-blue-900/20 mt-1">AI</div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-bold text-gray-300 mb-1">
                                Aang AI 
                                <span class="text-xs font-medium text-gray-400">({{ $models[$currentModel] ?? 'Default' }})</span>
                            </p>
                            <div class="ai-content-raw hidden">{{ $message['parts'][0]['text'] }}</div>
                            <div class="markdown-body"></div> 
                        </div>
                    </div>
                @endif
            @endforeach
        @endif
        
    </div>

    <footer class="p-4 sm:p-6 flex-shrink-0 bg-[#0f0f10]">
        <div class="max-w-4xl mx-auto">
            <form id="chat-form" class="relative group">
                <div class="relative flex items-end bg-[#1E1E1F] border border-gray-700 rounded-3xl focus-within:ring-2 focus-within:ring-blue-600/50 focus-within:border-blue-600 transition-all shadow-lg">
                    
                    <textarea 
                        id="message-input"
                        placeholder="Ketik pesan untuk Aang AI..."
                        class="w-full max-h-32 min-h-[56px] py-4 pl-6 pr-14 bg-transparent text-gray-200 text-base focus:outline-none resize-none overflow-y-auto scrollbar-hide"
                        rows="1"
                        style="scrollbar-width: none;"
                        oninput="this.style.height = '1px'; this.style.height = this.scrollHeight + 'px'"
                    ></textarea>

                    <button 
                        type="submit" 
                        id="send-button"
                        class="absolute right-2 bottom-2 p-2.5 rounded-full bg-blue-600 hover:bg-blue-500 text-white shadow-lg disabled:opacity-50 disabled:cursor-not-allowed transition-all active:scale-95"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 transform rotate-90" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 19V5m-7 7l7-7 7 7" />
                        </svg>
                    </button>
                </div>
                <p class="text-center text-xs text-gray-500 mt-2">
                    Aang AI dapat membuat kesalahan. Budayakan ATM. <br>
                    Amati - Tiru - Modifikasi
                </p>
            </form>
        </div>
    </footer>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/dompurify/3.0.8/purify.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        
        const chatWindow = document.getElementById('chat-window');
        const chatForm = document.getElementById('chat-form');
        const messageInput = document.getElementById('message-input');
        const sendButton = document.getElementById('send-button');
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const modelSelector = document.getElementById('model-selector');
        
        let loadingIndicator = null;

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
        
        function scrollToBottom() {
            chatWindow.scrollTop = chatWindow.scrollHeight;
        }
        
        scrollToBottom();
        
        function toggleForm(isLoading) {
            messageInput.disabled = isLoading;
            sendButton.disabled = isLoading;

            if (isLoading) {
                sendButton.classList.add('opacity-50', 'cursor-not-allowed');
            } else {
                sendButton.classList.remove('opacity-50', 'cursor-not-allowed');
                
                if (messageInput.value.trim() === '') {
                    sendButton.disabled = true;
                    sendButton.classList.add('disabled:opacity-50', 'disabled:cursor-not-allowed');
                }
            }
        }
        
        messageInput.addEventListener('input', function() {
            if (messageInput.value.trim() === '') {
                sendButton.disabled = true;
                sendButton.classList.add('disabled:opacity-50', 'disabled:cursor-not-allowed');
            } else {
                sendButton.disabled = false;
                sendButton.classList.remove('disabled:opacity-50', 'disabled:cursor-not-allowed');
            }
        });

        messageInput.addEventListener('keydown', function(e) {
            const isMobile = window.innerWidth < 640 || /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
            
            if (isMobile) {
                return;
            }
            
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                chatForm.dispatchEvent(new Event('submit', { cancelable: true }));
            }
        });
        
        sendButton.disabled = true;
        sendButton.classList.add('disabled:opacity-50', 'disabled:cursor-not-allowed');

        chatForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const message = messageInput.value.trim();
            if (message === '' || messageInput.disabled) return;
            
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
            })
            .catch(error => {
                if (loadingIndicator) { loadingIndicator.remove(); loadingIndicator = null; }
                console.error('Error:', error);
                appendMessage(`Koneksi bermasalah: ${error.message}`, 'ai-error');
            })
            .finally(() => {
                toggleForm(false);
            });
        });

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
                avatar.textContent = 'ME';

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
                
                const selectedModelText = modelSelector.options[modelSelector.selectedIndex].text;
                name.innerHTML = `Aang AI <span class="text-xs font-medium text-gray-400">(${selectedModelText.trim()})</span>`;

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
        
        modelSelector.addEventListener('change', function() {
            const newModel = this.value;
            fetch("{{ route('chat.model.switch') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ model: newModel })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Model switched to ' + newModel);
                } else {
                    console.error('Gagal ganti model.');
                }
            })
            .catch(err => console.error('Error ganti model:', err));
        });
        
    });
</script>
@endpush