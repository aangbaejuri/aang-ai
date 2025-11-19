<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#131314">
    <title>@yield('title', 'Aang AI Chatbot')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="shortcut icon" href="{{ asset('aang_ai.png') }}" type="image/x-icon">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; background-color: #131314; }
        
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: rgba(0, 0, 0, 0.1); }
        ::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.2); border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(255, 255, 255, 0.3); }

        .history-item:hover .delete-btn { display: block; }
        
        .loader-dots div {
            width: 8px; height: 8px;
            background-color: #9CA3AF;
            border-radius: 100%;
            display: inline-block;
            animation: pulse 1.4s infinite;
        }
        .loader-dots div:nth-child(2) { animation-delay: 0.2s; }
        .loader-dots div:nth-child(3) { animation-delay: 0.4s; }
        @keyframes pulse {
            0%, 80%, 100% { transform: scale(0); }
            40% { transform: scale(1.0); }
        }

        pre { position: relative; }
        .copy-code-btn {
            position: absolute;
            top: 0.75rem;
            right: 0.75rem;
            background-color: #374151;
            color: #d1d5db;
            border: none;
            padding: 0.25rem 0.5rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            opacity: 1;
            transition: opacity 0.2s ease-in-out, background-color 0.2s;
        }
        .copy-code-btn:hover { background-color: #4b5563; }
        .copy-code-btn.copied {
            background-color: #059669;
            color: white;
        }
    </style>
    @stack('styles')
</head>
<body class="text-gray-200 overflow-hidden">

    <div id="sidebar-overlay" class="fixed inset-0 bg-black/80 z-30 hidden sm:hidden transition-opacity opacity-0 duration-300"></div>

    <div class="flex h-screen w-full">
        
        <nav class="hidden sm:flex w-16 sm:w-20 h-full bg-[#1A1A1B] p-2 sm:p-4 flex-col items-center gap-6 flex-shrink-0 z-50 border-r border-gray-800">
            <div class="p-2 bg-blue-600 rounded-lg mt-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
            </div>
            
            <button class="p-3 rounded-lg text-gray-400 hover:bg-gray-700/50 transition-colors js-toggle-sidebar" title="Toggle Riwayat">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>

            <a href="{{ route('chat.show') }}" class="p-3 rounded-lg {{ request()->routeIs('chat.show') ? 'bg-gray-700/50 text-white' : 'text-gray-500 hover:bg-gray-700/50' }} transition-colors" title="Chat">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" /></svg>
            </a>
        </nav>
        
        <aside id="history-sidebar" class="fixed sm:relative top-0 left-0 sm:left-0 h-full bg-[#131314] border-r border-gray-800 z-40 sm:z-auto transition-all duration-300 ease-in-out w-72 p-5 -translate-x-full sm:translate-x-0 sm:w-72 sm:p-5 overflow-hidden">
            <div class="w-full h-full flex flex-col">
                <div class="flex justify-between items-center mb-6 flex-shrink-0">
                    <h2 class="text-xl font-bold text-white tracking-wide">Riwayat</h2>
                    <button onclick="openModal('{{ route('chat.clear') }}', 'Hapus Semua Riwayat', 'Anda yakin? Semua data chat akan hilang.')" class="text-xs text-gray-400 hover:text-red-400 transition-colors" title="Hapus Semua">
                        Bersihkan
                    </button>
                </div>
                
                <a href="{{ route('chat.new') }}" class="w-full mb-4 px-4 py-3 rounded-xl text-left text-white bg-blue-600 hover:bg-blue-700 transition-all shadow-lg hover:shadow-blue-600/20 font-medium text-sm flex items-center space-x-2 group flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                    <span>Chat Baru</span>
                </a>
                
                <div class="flex-1 overflow-y-auto space-y-1 pr-2">
                    @php
                        $sessions = session('chat_sessions', []);
                        $current_id = session('current_chat_id');
                    @endphp
                    
                    @forelse($sessions as $session)
                        <div class="relative group history-item">
                            <a href="{{ route('chat.switch', $session['id']) }}" 
                               class="block w-full text-left px-3 py-2.5 rounded-lg text-sm truncate transition-colors {{ $session['id'] == $current_id ? 'bg-[#28292C] text-white' : 'text-gray-400 hover:bg-[#1A1A1B] hover:text-gray-200' }}">
                                {{ $session['title'] }}
                            </a>
                            <button onclick="openModal('{{ route('chat.delete', $session['id']) }}', 'Hapus Chat', 'Hapus percakapan ini?')" 
                               class="delete-btn absolute right-2 top-1/2 -translate-y-1/2 p-1.5 text-gray-500 hover:text-red-400 bg-[#131314] rounded-md shadow-sm" 
                               title="Hapus">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                  <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    @empty
                        <div class="text-center mt-10">
                            <p class="text-xs text-gray-600">Belum ada riwayat</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </aside>

        <main id="main-content" class="flex-1 flex flex-col h-screen relative w-full min-w-0 bg-[#0f0f10]">
            @yield('main_content')
        </main>
    </div>

    <div id="confirmation-modal" class="fixed inset-0 bg-black/70 z-[60] flex items-center justify-center p-4 hidden backdrop-blur-sm">
        <div class="bg-[#1E1E1F] rounded-xl shadow-2xl w-full max-w-sm p-6 border border-gray-700 transform transition-all scale-95 opacity-0" id="modal-panel">
            <h3 id="modal-title" class="text-lg font-bold text-white mb-2">Konfirmasi</h3>
            <p id="modal-body" class="text-gray-400 mb-6 text-sm">Apakah Anda yakin?</p>
            <div class="flex justify-end space-x-3">
                <button id="modal-cancel-btn" class="px-4 py-2 rounded-lg bg-gray-700 hover:bg-gray-600 text-white text-sm font-medium transition-colors">
                    Batal
                </button>
                <button id="modal-confirm-btn" class="px-4 py-2 rounded-lg bg-red-600 hover:bg-red-700 text-white text-sm font-medium transition-colors">
                    Hapus
                </button>
            </div>
        </div>
    </div>

    <script type="text/javascript">
        const sidebar = document.getElementById('history-sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        
        function toggleSidebar() {
            const isMobile = window.innerWidth < 640;

            if (isMobile) {
                sidebar.classList.toggle('-translate-x-full');
                
                if (!sidebar.classList.contains('-translate-x-full')) {
                    overlay.classList.remove('hidden');
                    setTimeout(() => overlay.classList.add('opacity-100'), 10);
                } else {
                    overlay.classList.remove('opacity-100');
                    setTimeout(() => overlay.classList.add('hidden'), 300);
                }
            } else {
                sidebar.classList.toggle('sm:w-72');
                sidebar.classList.toggle('sm:w-0');
                sidebar.classList.toggle('sm:p-5');
                sidebar.classList.toggle('sm:p-0');
                sidebar.classList.toggle('sm:translate-x-0');
            }
        }

        document.querySelectorAll('.js-toggle-sidebar').forEach(btn => {
            btn.addEventListener('click', toggleSidebar);
        });
        
        if (overlay) {
            overlay.addEventListener('click', toggleSidebar);
        }

        const modal = document.getElementById('confirmation-modal');
        const modalPanel = document.getElementById('modal-panel');
        const modalTitle = document.getElementById('modal-title');
        const modalBody = document.getElementById('modal-body');
        const modalCancelBtn = document.getElementById('modal-cancel-btn');
        const modalConfirmBtn = document.getElementById('modal-confirm-btn');
        let deleteUrl = '';

        function openModal(url, title, body) {
            deleteUrl = url;
            modalTitle.textContent = title;
            modalBody.textContent = body;
            modal.classList.remove('hidden');
            setTimeout(() => {
                modalPanel.classList.remove('scale-95', 'opacity-0');
                modalPanel.classList.add('scale-100', 'opacity-100');
            }, 10);
        }

        function closeModal() {
            modalPanel.classList.remove('scale-100', 'opacity-100');
            modalPanel.classList.add('scale-95', 'opacity-0');
            setTimeout(() => {
                modal.classList.add('hidden');
                deleteUrl = '';
            }, 200);
        }

        modalCancelBtn.addEventListener('click', closeModal);
        modalConfirmBtn.addEventListener('click', () => {
            if (deleteUrl) window.location.href = deleteUrl;
        });
    </script>
    
    @stack('scripts')
</body>
</html>