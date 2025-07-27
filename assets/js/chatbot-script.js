jQuery(document).ready(function($) {
    const chatWindow = $('#lcc-chat-window');
    const chatToggle = $('#lcc-chat-toggle');
    const chatBody = $('.lcc-chat-body');
    const optionsContainer = $('#lcc-options-container');
    const leadForm = $('#lcc-lead-form');
    const formInput = $('#lcc-input-field');
    const conversation = chatbot_params.conversation;
    const finalContent = chatbot_params.final_content;

    let leadData = {};
    let currentStepKey = 'start';

    function toggleChat(open) {
        if (open) {
            if (chatWindow.is(':visible')) return;
            chatToggle.addClass('hidden');
            chatWindow.fadeIn();
            if (chatBody.children().length === 0) renderStep('start');
        } else {
            chatWindow.fadeOut(() => {
                chatToggle.removeClass('hidden');
            });
        }
    }
    
    $('#lcc-chat-toggle').on('click', () => toggleChat(true));
    $('#lcc-close-chat').on('click', () => toggleChat(false));

    function addMessage(text, type) {
        const botClass = 'bg-gray-200 text-gray-800 self-start';
        const userClass = 'lcc-user-message text-white self-end';
        const messageEl = $('<div></div>')
            .addClass(`p-3 rounded-lg max-w-[85%] ${type === 'bot' ? botClass : userClass}`)
            .html(text);
        chatBody.append(messageEl);
        chatBody.scrollTop(chatBody[0].scrollHeight);
    }
    
    // --- FUNGSI VALIDASI BARU ---
    function validateInput(value, type) {
        if (type === 'email') {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(value);
        }
        if (type === 'phone') {
            // Memeriksa apakah hanya berisi angka dan panjangnya antara 8-15 digit
            const phoneRegex = /^[0-9]{8,15}$/;
            return phoneRegex.test(value.replace(/[\s-()]/g, '')); // Menghapus spasi, strip, kurung
        }
        return true; // Untuk nama, cukup periksa tidak kosong (sudah dilakukan sebelumnya)
    }

    function renderStep(stepKey) {
        currentStepKey = stepKey;
        const step = conversation[stepKey];
        if (!step) return;
        let botMessage = step.bot_message.replace('{name}', leadData.name || '');
        addMessage(botMessage, 'bot');
        optionsContainer.empty().hide();
        leadForm.hide();
        if (step.show_input) {
            let placeholder = '...'; let inputType = 'text';
            if (step.show_input === 'name') placeholder = 'Nama lengkap...';
            if (step.show_input === 'email') { placeholder = 'Alamat email...'; inputType = 'email'; }
            if (step.show_input === 'phone') { placeholder = 'Nomor WhatsApp...'; inputType = 'tel'; }
            formInput.attr('type', inputType).attr('placeholder', placeholder).val('');
            leadForm.show();
            formInput.focus();
            return;
        }
        if (step.options && step.options.length > 0) {
            step.options.forEach(option => {
                const button = $('<button></button>')
                    .addClass('lcc-option-button w-full bg-white rounded-full py-2 px-4 transition-colors')
                    .text(option.text)
                    .on('click', function() {
                        addMessage($(this).text(), 'user');
                        optionsContainer.hide();
                        setTimeout(() => renderStep(option.next_step), 300);
                    });
                optionsContainer.append(button);
            });
            optionsContainer.show();
        }
    }

    // --- LOGIKA SUBMIT FORM DIMODIFIKASI DENGAN VALIDASI ---
    leadForm.on('submit', function(e) {
        e.preventDefault();
        const userInput = formInput.val().trim();
        if (userInput === '') return;

        const currentInputType = conversation[currentStepKey].show_input;

        // Validasi input sebelum lanjut
        if (!validateInput(userInput, currentInputType)) {
            addMessage('Format yang Anda masukkan sepertinya kurang tepat. Mohon coba lagi.', 'bot');
            return;
        }

        addMessage(userInput, 'user');
        leadForm.hide();
        leadData[currentInputType] = userInput;

        if (currentInputType === 'name') renderStep('ask_email');
        else if (currentInputType === 'email') renderStep('ask_phone');
        else if (currentInputType === 'phone') {
            const submitButton = $(this).find('button').prop('disabled', true);
            $.post(chatbot_params.ajax_url, {
                action: 'save_chatbot_lead', nonce: chatbot_params.nonce,
                name: leadData.name, email: leadData.email, phone: leadData.phone
            }, function(response) {
                if (response.success) {
                    let finalMessage = finalContent.message.replace('{name}', leadData.name || '');
                    addMessage(finalMessage, 'bot');
                    if (finalContent.link_text && finalContent.link_url) {
                        const linkButton = $('<a></a>').addClass('inline-block bg-green-500 text-white no-underline py-3 px-5 rounded-lg mt-2 font-bold text-center self-start hover:bg-green-600').attr('href', finalContent.link_url).attr('target', '_blank').text(finalContent.link_text);
                        chatBody.append(linkButton);
                        chatBody.scrollTop(chatBody[0].scrollHeight);
                    }
                } else {
                    addMessage('Maaf, terjadi kesalahan.', 'bot');
                }
            }).always(function() { submitButton.prop('disabled', false); });
        }
    });
});