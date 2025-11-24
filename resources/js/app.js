import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

function showConfirmDialog({ title = 'Konfirmasi', message = '', confirmText = 'Ya', cancelText = 'Batal' } = {}) {
    return new Promise((resolve) => {
        const overlay = document.createElement('div');
        overlay.className = 'fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50';
        overlay.innerHTML = `
            <div class="bg-white rounded-xl shadow-2xl max-w-md w-full mx-4 p-6">
                <div class="w-14 h-14 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 11-10 10A10 10 0 0112 2z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 text-center mb-2">${title}</h3>
                <p class="text-sm text-gray-600 text-center mb-6 whitespace-pre-line">${message}</p>
                <div class="flex items-center justify-center space-x-3">
                    <button class="px-5 py-2 text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors" data-action="cancel">
                        ${cancelText}
                    </button>
                    <button class="px-5 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors" data-action="confirm">
                        ${confirmText}
                    </button>
                </div>
            </div>
        `;

        const removeOverlay = () => {
            if (overlay && overlay.parentNode) {
                overlay.parentNode.removeChild(overlay);
            }
        };

        overlay.addEventListener('click', (event) => {
            if (event.target === overlay) {
                removeOverlay();
                resolve(false);
            }
        });

        overlay.querySelector('[data-action="cancel"]').addEventListener('click', () => {
            removeOverlay();
            resolve(false);
        });

        overlay.querySelector('[data-action="confirm"]').addEventListener('click', () => {
            removeOverlay();
            resolve(true);
        });

        document.body.appendChild(overlay);
    });
}

document.addEventListener('DOMContentLoaded', () => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const deleteButtons = document.querySelectorAll('.delete-form-btn');

    if (csrfToken && deleteButtons.length) {
        deleteButtons.forEach((button) => {
            if (button.getAttribute('data-delete-listener') === 'true') {
                return;
            }

            button.setAttribute('data-delete-listener', 'true');

            button.addEventListener('click', function () {
                const deleteUrl = this.getAttribute('data-delete-url');
                if (!deleteUrl) {
                    return;
                }

                const buttonRef = this;

                showConfirmDialog({
                    title: 'Hapus Form?',
                    message: 'Form dan seluruh data terkait akan dihapus permanen.\nTindakan ini tidak dapat dibatalkan.',
                    confirmText: 'Hapus',
                    cancelText: 'Batal',
                }).then((confirmed) => {
                    if (!confirmed) {
                        return;
                    }

                    const originalClasses = buttonRef.getAttribute('data-original-classes') || '';
                    if (!originalClasses) {
                        buttonRef.setAttribute('data-original-classes', buttonRef.className);
                    }

                    buttonRef.disabled = true;
                    buttonRef.classList.add('opacity-50', 'cursor-not-allowed');

                    fetch(deleteUrl, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                    })
                        .then((response) => {
                            if (!response.ok) {
                                throw new Error('Delete request failed');
                            }
                            return response.json();
                        })
                        .then((data) => {
                            if (data.success) {
                                window.location.reload();
                            } else {
                                throw new Error(data.message || 'Gagal menghapus form');
                            }
                        })
                        .catch((error) => {
                            console.error(error);
                            alert('Gagal menghapus form. Silakan coba lagi.');
                            buttonRef.disabled = false;
                            buttonRef.className = buttonRef.getAttribute('data-original-classes') || buttonRef.className;
                            buttonRef.classList.remove('opacity-50', 'cursor-not-allowed');
                        });
                });
            });
        });
    }

    window.initializeQuestionSortable?.();
});
