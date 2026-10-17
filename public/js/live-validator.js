/**
 * Live Validation Engine for Driver Finance & Personal Accounting
 * Memberikan validasi langsung (real-time) saat user mengetik atau mengubah field,
 * mencegah error input, menampilkan pesan jelas di bawah field, dan mencegah form submit yang tidak valid.
 */

(function () {
    'use strict';

    // 1. GLOBAL FLOATING TOAST HELPER
    window.showGlobalToast = function (message, isError = true, duration = 4500) {
        let toast = document.getElementById('global-floating-toast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'global-floating-toast';
            toast.className = 'fixed top-14 left-3 right-3 sm:left-auto sm:right-6 sm:max-w-md z-50 transform transition-all duration-300 ease-out flex items-center justify-between p-3.5 rounded-2xl shadow-2xl backdrop-blur-md';
            document.body.appendChild(toast);
        }

        // Set tema warna
        if (isError) {
            toast.className = 'fixed top-14 left-3 right-3 sm:left-auto sm:right-6 sm:max-w-md z-50 transform transition-all duration-300 ease-out flex items-center justify-between p-3.5 rounded-2xl shadow-2xl backdrop-blur-md bg-rose-950/95 border border-rose-500/60 text-rose-200 animate-fade-in';
        } else {
            toast.className = 'fixed top-14 left-3 right-3 sm:left-auto sm:right-6 sm:max-w-md z-50 transform transition-all duration-300 ease-out flex items-center justify-between p-3.5 rounded-2xl shadow-2xl backdrop-blur-md bg-slate-900/95 border border-emerald-500/60 text-emerald-300 animate-fade-in';
        }

        toast.style.opacity = '1';
        toast.style.transform = 'translateY(0)';

        toast.innerHTML = `
            <div class="flex items-center space-x-2.5">
                <span class="text-base">${isError ? '⚠️' : '✓'}</span>
                <span class="text-xs font-bold leading-snug">${message}</span>
            </div>
            <button type="button" onclick="document.getElementById('global-floating-toast')?.remove()" class="text-slate-400 hover:text-white ml-2 p-1 rounded-lg text-sm leading-none">&times;</button>
        `;

        if (window._toastTimeout) clearTimeout(window._toastTimeout);
        window._toastTimeout = setTimeout(() => {
            if (toast) {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(-10px)';
                setTimeout(() => toast.remove(), 300);
            }
        }, duration);
    };

    // 2. HELPER UI ERROR ATTACHMENT
    function showFieldError(input, message) {
        if (!input) return;

        input.classList.add('is-invalid');
        input.classList.add('border-rose-500', 'ring-1', 'ring-rose-500', 'bg-rose-950/20');
        input.classList.remove('border-slate-700', 'border-emerald-500', 'border-slate-800');

        // Cari atau buat elemen pesan error di bawah input
        let container = input.closest('.form-group') || input.parentElement;
        let errEl = container.querySelector(`.live-error-msg[data-for="${input.name || input.id}"]`);

        if (!errEl) {
            errEl = document.createElement('div');
            errEl.className = 'live-error-msg text-[11px] font-bold text-rose-400 mt-1 flex items-center space-x-1 animate-fade-in';
            errEl.setAttribute('data-for', input.name || input.id);
            // Sisipkan setelah input atau setelah wrapper terdekat
            if (input.nextSibling) {
                input.parentNode.insertBefore(errEl, input.nextSibling);
            } else {
                input.parentNode.appendChild(errEl);
            }
        }

        errEl.innerHTML = `<span>⚠️</span><span>${message}</span>`;
        errEl.style.display = 'flex';
    }

    function clearFieldError(input) {
        if (!input) return;

        input.classList.remove('is-invalid', 'border-rose-500', 'ring-1', 'ring-rose-500', 'bg-rose-950/20');
        input.classList.add('border-slate-700');

        let container = input.closest('.form-group') || input.parentElement;
        if (container) {
            let errEl = container.querySelector(`.live-error-msg[data-for="${input.name || input.id}"]`);
            if (errEl) {
                errEl.remove();
            }
        }
    }

    // 3. CORE FIELD VALIDATOR
    function validateField(input) {
        if (!input) return { valid: true };

        // Lewati input hidden atau yang berada di dalam form tersembunyi
        if (input.type === 'hidden') return { valid: true };
        if (input.disabled) return { valid: true };

        // Pastikan elemen tampak di layar (tidak di dalam accordion atau modal tertutup)
        const isVisible = input.offsetParent !== null || window.getComputedStyle(input).display !== 'none';
        if (!isVisible) {
            clearFieldError(input);
            return { valid: true };
        }

        const val = input.value !== undefined ? input.value.trim() : '';
        const name = (input.name || input.id || '').toLowerCase();
        const type = (input.type || '').toLowerCase();

        // A. Required Check
        if (input.hasAttribute('required')) {
            if (val === '' || (input.tagName === 'SELECT' && (val === '' || val === '-'))) {
                const label = input.getAttribute('data-label') || 'Kolom ini';
                const msg = `${label} wajib diisi / dipilih.`;
                showFieldError(input, msg);
                return { valid: false, message: msg };
            }
        }

        // B. Numeric Validation
        if (type === 'number' || input.inputMode === 'numeric' || name.includes('amount') || name.includes('balance') || name.includes('nominal') || name.includes('bill_') || name.includes('coin_') || name.includes('orders') || name.includes('odometer') || name.includes('price')) {
            if (val !== '') {
                const num = Number(val);
                if (isNaN(num)) {
                    const msg = 'Harus berupa angka valid.';
                    showFieldError(input, msg);
                    return { valid: false, message: msg };
                }

                // Minimum Check
                const minAttr = input.getAttribute('min');
                if (minAttr !== null) {
                    const minVal = Number(minAttr);
                    if (num < minVal) {
                        const msg = minVal === 0 
                            ? 'Nilai tidak boleh negatif (minimal 0).' 
                            : `Nilai minimal ${minVal.toLocaleString('id-ID')}.`;
                        showFieldError(input, msg);
                        return { valid: false, message: msg };
                    }
                } else if (!input.hasAttribute('allow-negative') && num < 0) {
                    const msg = 'Nilai tidak boleh negatif / kurang dari 0.';
                    showFieldError(input, msg);
                    return { valid: false, message: msg };
                }

                // Maximum Check
                const maxAttr = input.getAttribute('max');
                if (maxAttr !== null) {
                    const maxVal = Number(maxAttr);
                    if (num > maxVal) {
                        const msg = `Nilai maksimal ${maxVal.toLocaleString('id-ID')}.`;
                        showFieldError(input, msg);
                        return { valid: false, message: msg };
                    }
                }

                // Integer / Denominasi Check
                const isIntegerExpected = input.getAttribute('step') === '1' || name.startsWith('bill_') || name.startsWith('coin_') || name.includes('orders') || name.includes('odometer');
                if (isIntegerExpected && !Number.isInteger(num)) {
                    const msg = 'Harus bilangan bulat utuh (tidak boleh pecahan desimal).';
                    showFieldError(input, msg);
                    return { valid: false, message: msg };
                }

                // Batas wajar pecahan kas dompet (maksimal 100.000 lembar/koin)
                if ((name.startsWith('bill_') || name.startsWith('coin_')) && num > 100000) {
                    const msg = 'Jumlah lembar/koin melebihi batas wajar (maks. 100.000).';
                    showFieldError(input, msg);
                    return { valid: false, message: msg };
                }
            }
        }

        // C. Datetime & Jam Check
        if (type === 'datetime-local' || name.includes('recorded_at') || name.includes('transaction_time')) {
            if (val !== '') {
                const selectedTime = new Date(val);
                const now = new Date();
                // Toleransi perbedaan jam 2 menit
                const maxAllowed = new Date(now.getTime() + 2 * 60 * 1000);

                if (selectedTime > maxAllowed) {
                    const msg = 'Waktu tidak boleh di masa depan (melebihi jam sekarang).';
                    showFieldError(input, msg);
                    return { valid: false, message: msg };
                }

                // Min time check (checkpoint sebelumnya)
                const minAttr = input.getAttribute('min');
                if (minAttr) {
                    const minTime = new Date(minAttr);
                    if (selectedTime < minTime) {
                        const msg = 'Waktu harus lebih baru daripada checkpoint sebelumnya.';
                        showFieldError(input, msg);
                        return { valid: false, message: msg };
                    }
                }
            }
        }

        // D. Validasi Transfer Saldo (Rekening Asal != Rekening Tujuan & Saldo Cukup)
        const form = input.closest('form');
        if (form && form.action && form.action.includes('/operational/transfer')) {
            const srcSelect = form.querySelector('select[name="source_account_id"], select[name="from_account_id"]');
            const destSelect = form.querySelector('select[name="destination_account_id"], select[name="to_account_id"]');
            const amountInput = form.querySelector('input[name="amount"]');

            if (srcSelect && destSelect && srcSelect.value && destSelect.value && srcSelect.value === destSelect.value) {
                if (input === destSelect || input === srcSelect) {
                    const msg = 'Rekening asal dan tujuan transfer tidak boleh sama.';
                    showFieldError(destSelect, msg);
                    return { valid: false, message: msg };
                }
            } else if (destSelect) {
                clearFieldError(destSelect);
            }

            // Cek saldo rekening asal jika ada
            if (input === amountInput && srcSelect && srcSelect.selectedOptions[0]) {
                const optText = srcSelect.selectedOptions[0].innerText;
                const match = optText.match(/Saldo:\s*Rp\s*([\d\.]+)/i);
                if (match && match[1]) {
                    const availableBalance = parseInt(match[1].replace(/\./g, ''), 10);
                    const transferAmount = parseInt(val, 10) || 0;
                    if (transferAmount > availableBalance) {
                        const msg = `Nominal transfer melebihi saldo rekening asal (Maks. Rp ${availableBalance.toLocaleString('id-ID')}).`;
                        showFieldError(amountInput, msg);
                        return { valid: false, message: msg };
                    }
                }
            }
        }

        // E. Validasi Pembayaran COD di Lapangan
        if (form && form.id === 'form-complete-cod') {
            const netReceivedInput = form.querySelector('#input-final-received-amount');
            const gaveInput = form.querySelector('#calc-gave-amount');
            const billLabel = form.querySelector('#calc-bill-amount-label');

            if (billLabel && (input === gaveInput || input === netReceivedInput)) {
                const billText = billLabel.innerText.replace(/[^\d]/g, '');
                const billAmount = parseInt(billText, 10) || 0;
                const netReceived = parseInt(netReceivedInput?.value || gaveInput?.value, 10) || 0;

                if (billAmount > 0 && netReceived > 0 && netReceived < billAmount) {
                    const shortage = billAmount - netReceived;
                    const msg = `Uang diterima kurang Rp ${shortage.toLocaleString('id-ID')} dari tagihan pokok COD.`;
                    showFieldError(input, msg);
                    return { valid: false, message: msg };
                }
            }
        }

        // Jika lolos semua validasi
        clearFieldError(input);
        return { valid: true };
    }

    // 4. ATTACH LISTENERS KE SELURUH FORM & INPUT
    function initLiveValidation() {
        // Tangkap semua input, select, textarea
        document.querySelectorAll('input, select, textarea').forEach(input => {
            if (input._liveValidationAttached) return;
            input._liveValidationAttached = true;

            const handleLiveValidation = () => {
                validateField(input);
            };

            input.addEventListener('input', handleLiveValidation);
            input.addEventListener('change', handleLiveValidation);
            input.addEventListener('blur', handleLiveValidation);
        });

        // Tangkap semua form submit
        document.querySelectorAll('form').forEach(form => {
            if (form._liveSubmitAttached) return;
            form._liveSubmitAttached = true;

            form.addEventListener('submit', function (e) {
                // Jangan batalkan jika form GET (misal form filter/search)
                if ((form.method || 'GET').toUpperCase() === 'GET') return;

                const inputs = form.querySelectorAll('input, select, textarea');
                let firstInvalid = null;
                let firstErrorMessage = '';

                inputs.forEach(inp => {
                    const res = validateField(inp);
                    if (!res.valid && !firstInvalid) {
                        firstInvalid = inp;
                        firstErrorMessage = res.message;
                    }
                });

                if (firstInvalid) {
                    e.preventDefault();
                    e.stopPropagation();

                    // Scroll dan fokus ke elemen yang salah
                    firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    try { firstInvalid.focus(); } catch (err) {}

                    // Efek visual getar (shake)
                    firstInvalid.classList.remove('live-input-shake');
                    void firstInvalid.offsetWidth; // trigger reflow
                    firstInvalid.classList.add('live-input-shake');

                    // Notifikasi toast di layar
                    window.showGlobalToast(firstErrorMessage || 'Mohon periksa kembali inputan Anda sebelum menyimpan.', true);
                    return false;
                }

                // Jika valid 100%, beri feedback loading pada submit button
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn && !submitBtn.disabled) {
                    submitBtn.setAttribute('data-original-text', submitBtn.innerHTML);
                    // Tunggu sesaat agar request browser terkirim lalu disable tombol cegah double submit
                    setTimeout(() => {
                        submitBtn.disabled = true;
                        submitBtn.classList.add('opacity-75', 'cursor-not-allowed');
                        submitBtn.innerHTML = `
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>Menyimpan...</span>
                        `;
                    }, 50);
                }
            });
        });
    }

    // Jalankan saat DOM siap
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initLiveValidation);
    } else {
        initLiveValidation();
    }

    // Jalankan ulang secara dinamis saat modal dibuka atau DOM berubah
    const originalOpenModal = window.openModal;
    if (typeof originalOpenModal === 'function') {
        window.openModal = function (id) {
            originalOpenModal(id);
            setTimeout(initLiveValidation, 50);
        };
    }

    window.refreshLiveValidation = initLiveValidation;
})();
