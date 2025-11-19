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