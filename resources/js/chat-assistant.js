class ChatRequestError extends Error {
    constructor(message, status, payload) {
        super(message);
        this.status = status;
        this.payload = payload;
    }
}

function initializeDoraChat(root) {
    const launcher = root.querySelector('[data-chat-launcher]');
    const panel = root.querySelector('[data-chat-panel]');
    const closeButton = root.querySelector('[data-chat-close]');
    const conversationSelect = root.querySelector('[data-chat-conversation-select]');
    const conversationList = root.querySelector('[data-chat-conversation-list]');
    const emptyConversationList = root.querySelector('[data-chat-history-empty]');
    const messageList = root.querySelector('[data-chat-message-list]');
    const emptyState = root.querySelector('[data-chat-empty]');
    const composer = root.querySelector('[data-chat-composer]');
    const input = root.querySelector('[data-chat-input]');
    const sendButton = root.querySelector('[data-chat-send]');
    const status = root.querySelector('[data-chat-status]');
    const typing = root.querySelector('[data-chat-typing]');
    const retryButton = root.querySelector('[data-chat-retry]');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    const isWidget = root.dataset.mode === 'widget';

    let activeConversationId = null;
    let lastFailedMessageId = null;
    let displayedUserMessageIds = new Set();
    let conversationHistory = [];
    let busy = false;
    let initialized = false;

    const urlFor = (template, values) => Object.entries(values).reduce(
        (url, [key, value]) => url.replace(`__${key.toUpperCase()}__`, encodeURIComponent(value)),
        template,
    );

    const request = async (url, method = 'GET', body = undefined) => {
        const headers = { Accept: 'application/json' };

        if (body !== undefined) {
            headers['Content-Type'] = 'application/json';
        }

        if (csrfToken) {
            headers['X-CSRF-TOKEN'] = csrfToken;
        }

        const response = await fetch(url, {
            method,
            credentials: 'same-origin',
            headers,
            body: body === undefined ? undefined : JSON.stringify(body),
        });

        let payload = null;

        if (response.status !== 204) {
            try {
                payload = await response.json();
            } catch (error) {
                throw new Error('DORA returned an unreadable response.');
            }
        }

        if (!response.ok) {
            throw new ChatRequestError(
                payload?.error?.message ?? payload?.message ?? 'DORA could not complete that request.',
                response.status,
                payload,
            );
        }

        return payload;
    };

    const streamRequest = async (url, body, onEvent) => {
        const headers = {
            Accept: 'text/event-stream',
            'Content-Type': 'application/json',
        };

        if (csrfToken) {
            headers['X-CSRF-TOKEN'] = csrfToken;
        }

        const response = await fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers,
            body: JSON.stringify(body),
        });

        if (!response.ok) {
            let payload = null;

            if (response.headers.get('content-type')?.includes('application/json')) {
                try {
                    payload = await response.json();
                } catch (error) {
                    payload = null;
                }
            }

            throw new ChatRequestError(
                payload?.error?.message
                    ?? payload?.message
                    ?? `DORA could not complete that request (HTTP ${response.status}).`,
                response.status,
                payload,
            );
        }

        if (!response.body) {
            throw new Error("Your browser could not read DORA's streamed response.");
        }

        const reader = response.body.getReader();
        const decoder = new TextDecoder();
        let buffer = '';
        let eventName = 'message';
        let dataLines = [];
        let completed = null;

        const dispatchEvent = () => {
            if (dataLines.length === 0) {
                return;
            }

            let data;

            try {
                data = JSON.parse(dataLines.join('\n'));
            } catch (error) {
                throw new Error('DORA returned an invalid streamed response.');
            }

            if (eventName === 'error') {
                throw new ChatRequestError(
                    data?.error?.message ?? 'DORA could not complete that response.',
                    502,
                    data,
                );
            }

            if (eventName === 'complete') {
                completed = data;
            } else {
                onEvent(eventName, data);
            }

            eventName = 'message';
            dataLines = [];
        };

        while (true) {
            const { value, done } = await reader.read();
            buffer += decoder.decode(value ?? new Uint8Array(), { stream: !done });
            const lines = buffer.split(/\r?\n/);
            buffer = lines.pop() ?? '';

            for (const line of lines) {
                if (line === '') {
                    dispatchEvent();
                } else if (line.startsWith('event:')) {
                    eventName = line.slice(6).trim();
                } else if (line.startsWith('data:')) {
                    dataLines.push(line.slice(5).trimStart());
                }
            }

            if (done) {
                if (buffer !== '') {
                    if (buffer.startsWith('data:')) {
                        dataLines.push(buffer.slice(5).trimStart());
                    }

                    dispatchEvent();
                }

                break;
            }
        }

        if (!completed) {
            throw new Error('DORA’s response stream ended unexpectedly.');
        }

        return completed;
    };

    const setStatus = (message = '', retryable = false) => {
        status.textContent = message;
        status.hidden = message === '';
        retryButton.hidden = !retryable;
    };

    const setBusy = (value) => {
        busy = value;
        root.setAttribute('aria-busy', String(value));
        input.disabled = value;
        sendButton.disabled = value || input.value.trim() === '';
        typing.hidden = !value;
        conversationSelect.disabled = value;
        root.querySelector('[data-chat-new]').disabled = value;
        root.querySelector('[data-chat-clear]').disabled = value;
        retryButton.disabled = value;
        conversationList?.querySelectorAll('button').forEach((button) => {
            button.disabled = value;
        });
        root.querySelectorAll('[data-chat-suggestion]').forEach((suggestion) => {
            suggestion.disabled = value;
        });
    };

    const appendFormattedText = (element, text) => {
        const parts = text.split(/(\*\*[^*]+\*\*|`[^`]+`)/g);

        parts.forEach((part) => {
            if (part.startsWith('**') && part.endsWith('**')) {
                const strong = document.createElement('strong');
                strong.textContent = part.slice(2, -2);
                element.append(strong);
            } else if (part.startsWith('`') && part.endsWith('`')) {
                const code = document.createElement('code');
                code.className = 'rounded bg-paper px-1 py-0.5 font-mono text-[0.9em]';
                code.textContent = part.slice(1, -1);
                element.append(code);
            } else {
                element.append(document.createTextNode(part));
            }
        });
    };

    const renderReadableContent = (container, text) => {
        container.replaceChildren();

        let paragraphLines = [];
        let list = null;

        const flushParagraph = () => {
            if (paragraphLines.length === 0) {
                return;
            }

            const paragraph = document.createElement('p');
            paragraph.className = 'whitespace-pre-wrap break-words text-sm leading-7';
            appendFormattedText(paragraph, paragraphLines.join('\n'));
            container.append(paragraph);
            paragraphLines = [];
        };

        const flushList = () => {
            if (list !== null) {
                container.append(list);
                list = null;
            }
        };

        text.split(/\r?\n/).forEach((line) => {
            const heading = line.match(/^#{1,3}\s+(.+)$/);
            const unorderedItem = line.match(/^\s*[-*]\s+(.+)$/);
            const orderedItem = line.match(/^\s*\d+[.)]\s+(.+)$/);

            if (heading) {
                flushParagraph();
                flushList();
                const headingElement = document.createElement('h4');
                headingElement.className = 'font-semibold leading-6 text-ink';
                appendFormattedText(headingElement, heading[1]);
                container.append(headingElement);
                return;
            }

            if (unorderedItem || orderedItem) {
                flushParagraph();
                const nextListType = unorderedItem ? 'ul' : 'ol';

                if (list?.tagName.toLowerCase() !== nextListType) {
                    flushList();
                    list = document.createElement(nextListType);
                    list.className = `${nextListType === 'ul' ? 'list-disc' : 'list-decimal'} space-y-1 pl-5 text-sm leading-7`;
                }

                const item = document.createElement('li');
                appendFormattedText(item, (unorderedItem ?? orderedItem)[1]);
                list.append(item);
                return;
            }

            if (line.trim() === '') {
                flushParagraph();
                flushList();
                return;
            }

            flushList();
            paragraphLines.push(line);
        });

        flushParagraph();
        flushList();
    };

    const addMessage = (message) => {
        const article = document.createElement('article');
        const isUser = message.role === 'user';
        article.className = `mb-5 flex ${isUser ? 'justify-end' : 'justify-start'}`;

        const bubble = document.createElement('div');
        bubble.className = `max-w-[92%] rounded-2xl px-4 py-3 shadow-sm sm:max-w-[88%] ${
            isUser ? 'rounded-br-md bg-ink text-paper' : 'rounded-bl-md border border-paper2 bg-white text-ink'
        }`;

        const role = document.createElement('p');
        role.className = `mb-1 text-[10px] font-semibold uppercase tracking-[0.12em] ${
            isUser ? 'text-paper/60' : 'text-tealDeep'
        }`;
        role.textContent = isUser ? 'You' : 'DORA';

        const content = document.createElement(isUser ? 'p' : 'div');
        if (isUser) {
            content.className = 'whitespace-pre-wrap break-words text-sm leading-relaxed';
            content.textContent = message.content;
        } else {
            content.className = 'flex flex-col gap-2 break-words text-ink';
            renderReadableContent(content, message.content);
        }
        bubble.append(role, content);

        if (Array.isArray(message.source_references) && message.source_references.length > 0) {
            const sources = document.createElement('div');
            sources.className = 'mt-3 flex flex-col gap-2 border-t border-paper2 pt-2';

            [
                { type: 'knowledge', label: 'Approved knowledge', className: 'text-tealDeep' },
                { type: 'system_data', label: 'Live system data', className: 'text-copperDeep' },
            ].forEach((sourceGroup) => {
                const references = message.source_references.filter((reference) => (
                    sourceGroup.type === 'system_data'
                        ? reference.type === 'system_data'
                        : reference.type !== 'system_data'
                ));

                if (references.length === 0) {
                    return;
                }

                const group = document.createElement('section');
                const label = document.createElement('p');
                label.className = `text-[10px] font-semibold uppercase tracking-wide ${sourceGroup.className}`;
                label.textContent = sourceGroup.label;

                const list = document.createElement('ul');
                list.className = 'flex flex-wrap gap-x-3 gap-y-1 text-xs text-inkSoft';
                references.forEach((reference) => {
                    const item = document.createElement('li');
                    item.textContent = reference.updated_at
                        ? `${reference.source} · updated ${reference.updated_at}`
                        : reference.source;
                    list.append(item);
                });

                group.append(label, list);
                sources.append(group);
            });

            bubble.append(sources);
        }

        const timestamp = document.createElement('time');
        timestamp.className = `mt-2 block text-[10px] ${isUser ? 'text-paper/55' : 'text-inkSoft/70'}`;

        if (message.created_at) {
            const date = new Date(message.created_at);

            if (!Number.isNaN(date.getTime())) {
                timestamp.dateTime = date.toISOString();
                timestamp.textContent = new Intl.DateTimeFormat(undefined, {
                    dateStyle: 'short',
                    timeStyle: 'short',
                }).format(date);
            }
        }

        bubble.append(timestamp);
        article.append(bubble);
        messageList.append(article);
        return { article, content };
    };

    const renderMessages = (messages) => {
        messageList.querySelectorAll('article').forEach((message) => message.remove());
        emptyState.hidden = messages.length > 0;
        messages.forEach(addMessage);
        messageList.scrollTop = messageList.scrollHeight;
    };

    const updateConversationOptions = (conversations) => {
        conversationHistory = conversations;
        conversationSelect.replaceChildren();
        conversationList?.replaceChildren();

        conversations.forEach((conversation) => {
            const option = document.createElement('option');
            option.value = conversation.id;
            option.textContent = conversation.title || 'New conversation';
            conversationSelect.append(option);

            if (conversationList) {
                const item = document.createElement('li');
                const button = document.createElement('button');
                const title = document.createElement('span');

                button.type = 'button';
                button.dataset.chatConversation = conversation.id;
                button.className = 'flex w-full min-w-0 flex-col gap-1 rounded-xl border border-transparent px-3 py-2.5 text-left transition hover:border-paper2 hover:bg-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal aria-pressed:bg-teal/10 aria-pressed:text-tealDeep';
                button.setAttribute('aria-pressed', String(String(conversation.id) === String(activeConversationId)));

                title.className = 'w-full truncate text-sm font-medium text-ink';
                title.textContent = conversation.title || 'New conversation';
                button.append(title);

                if (conversation.updated_at) {
                    const updatedAt = new Date(conversation.updated_at);

                    if (!Number.isNaN(updatedAt.getTime())) {
                        const timestamp = document.createElement('span');
                        timestamp.className = 'text-[11px] text-inkSoft';
                        timestamp.textContent = new Intl.DateTimeFormat(undefined, {
                            dateStyle: 'medium',
                        }).format(updatedAt);
                        button.append(timestamp);
                    }
                }

                item.append(button);
                conversationList.append(item);
            }
        });

        conversationSelect.hidden = !isWidget || conversations.length < 2;
        conversationSelect.value = activeConversationId ?? '';

        if (emptyConversationList) {
            emptyConversationList.hidden = conversations.length > 0;
        }
    };

    const loadConversation = async (conversationId) => {
        const url = urlFor(root.dataset.showUrl, { conversation: conversationId });
        const response = await request(url);
        activeConversationId = response.data.id;
        updateConversationOptions(conversationHistory);
        conversationSelect.value = String(activeConversationId);
        const messages = response.data.messages ?? [];
        displayedUserMessageIds = new Set(
            messages.filter((message) => message.role === 'user').map((message) => String(message.id)),
        );
        renderMessages(messages);
        return response.data;
    };

    const refreshConversations = async () => {
        const response = await request(root.dataset.conversationsUrl);
        updateConversationOptions(response.data);
        return response.data;
    };

    const createConversation = async () => {
        const response = await request(root.dataset.createUrl, 'POST', {});
        activeConversationId = response.data.id;
        await refreshConversations();
        renderMessages([]);
        setStatus();
        return response.data;
    };

    const initialize = async () => {
        if (initialized) {
            return;
        }

        initialized = true;
        setBusy(true);

        try {
            const conversations = await refreshConversations();

            if (conversations.length > 0) {
                await loadConversation(conversations[0].id);
            } else {
                await createConversation();
            }
        } catch (error) {
            setStatus(error.message);
        } finally {
            setBusy(false);
        }
    };

    const showFailure = async (error, attemptedMessage = null, previousUserMessageIds = displayedUserMessageIds) => {
        const userMessage = error.payload?.user_message;

        if (userMessage) {
            lastFailedMessageId = userMessage.id;
        }

        if (userMessage || attemptedMessage !== null || lastFailedMessageId !== null) {
            try {
                const conversation = await loadConversation(activeConversationId);

                if (!userMessage && attemptedMessage !== null) {
                    const savedMessage = [...(conversation.messages ?? [])]
                        .reverse()
                        .find((message) => message.role === 'user'
                            && message.content === attemptedMessage
                            && !previousUserMessageIds.has(String(message.id)));

                    lastFailedMessageId = savedMessage?.id ?? null;
                }
            } catch (loadError) {
                setStatus(`${error.message} ${loadError.message}`, Boolean(lastFailedMessageId));
                return;
            }
        }

        if (attemptedMessage !== null && lastFailedMessageId === null) {
            input.value = attemptedMessage;
        }

        setStatus(error.message, Boolean(lastFailedMessageId));
    };

    const showStreamedText = (messageElement, text) => {
        const visibleText = text.replace(/\[\[source:[A-Za-z0-9._:-]*\]\]/gi, '');
        const markerStart = visibleText.toLowerCase().lastIndexOf('[[source:');
        const incompleteMarker = markerStart >= 0 && visibleText.indexOf(']]', markerStart) === -1;

        messageElement.textContent = incompleteMarker
            ? visibleText.slice(0, markerStart)
            : visibleText;
        messageList.scrollTop = messageList.scrollHeight;
    };

    const sendStreamedMessage = async (url, body) => {
        emptyState.hidden = true;
        let partialMessage = null;
        let streamedText = '';

        return streamRequest(url, body, (event, data) => {
            if (event === 'delta' && typeof data.text === 'string') {
                partialMessage ??= addMessage({ role: 'assistant', content: '' });
                streamedText += data.text;
                showStreamedText(partialMessage.content, streamedText);
            } else if (event === 'reset') {
                streamedText = '';
                partialMessage?.article.remove();
                partialMessage = null;
            }
        });
    };

    const sendMessage = async (message) => {
        if (!activeConversationId || busy || message.trim() === '') {
            return;
        }

        lastFailedMessageId = null;
        setStatus();
        setBusy(true);
        input.value = '';
        addMessage({ role: 'user', content: message });
        emptyState.hidden = true;
        messageList.scrollTop = messageList.scrollHeight;
        const previousUserMessageIds = new Set(displayedUserMessageIds);

        try {
            const url = urlFor(root.dataset.messageUrl, { conversation: activeConversationId });
            await sendStreamedMessage(url, { message });
            sendButton.disabled = true;
            await loadConversation(activeConversationId);
            await refreshConversations();
        } catch (error) {
            await showFailure(error, message, previousUserMessageIds);
        } finally {
            setBusy(false);
        }
    };

    const retryLastMessage = async () => {
        if (!lastFailedMessageId || !activeConversationId || busy) {
            return;
        }

        const url = urlFor(root.dataset.retryUrl, {
            conversation: activeConversationId,
            message: lastFailedMessageId,
        });
        setStatus();
        setBusy(true);

        try {
            await sendStreamedMessage(url, {});
            lastFailedMessageId = null;
            await loadConversation(activeConversationId);
            await refreshConversations();
        } catch (error) {
            await showFailure(error);
        } finally {
            setBusy(false);
        }
    };

    launcher?.addEventListener('click', async () => {
        const isOpen = !panel.hidden;
        panel.hidden = isOpen;
        panel.classList.toggle('hidden', isOpen);
        launcher.setAttribute('aria-expanded', String(!isOpen));

        if (!isOpen) {
            await initialize();
            input.focus();
        }
    });

    closeButton?.addEventListener('click', () => {
        panel.hidden = true;
        panel.classList.add('hidden');
        launcher?.setAttribute('aria-expanded', 'false');
        launcher?.focus();
    });

    root.querySelector('[data-chat-new]').addEventListener('click', async () => {
        setBusy(true);

        try {
            await createConversation();
            input.focus();
        } catch (error) {
            setStatus(error.message);
        } finally {
            setBusy(false);
        }
    });

    root.querySelector('[data-chat-clear]').addEventListener('click', async () => {
        if (!activeConversationId || !window.confirm('Clear this conversation and delete its saved messages?')) {
            return;
        }

        setBusy(true);

        try {
            const url = urlFor(root.dataset.deleteUrl, { conversation: activeConversationId });
            await request(url, 'DELETE');
            lastFailedMessageId = null;
            await createConversation();
        } catch (error) {
            setStatus(error.message);
        } finally {
            setBusy(false);
        }
    });

    conversationSelect.addEventListener('change', async () => {
        if (!conversationSelect.value) {
            return;
        }

        setBusy(true);

        try {
            lastFailedMessageId = null;
            setStatus();
            await loadConversation(conversationSelect.value);
        } catch (error) {
            setStatus(error.message);
        } finally {
            setBusy(false);
        }
    });

    conversationList?.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-chat-conversation]');

        if (!button || busy || String(button.dataset.chatConversation) === String(activeConversationId)) {
            return;
        }

        setBusy(true);

        try {
            lastFailedMessageId = null;
            setStatus();
            await loadConversation(button.dataset.chatConversation);
        } catch (error) {
            setStatus(error.message);
        } finally {
            setBusy(false);
        }
    });

    composer.addEventListener('submit', (event) => {
        event.preventDefault();
        sendMessage(input.value);
    });

    input.addEventListener('input', () => {
        sendButton.disabled = busy || input.value.trim() === '';
    });

    input.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            composer.requestSubmit();
        }
    });

    retryButton.addEventListener('click', retryLastMessage);

    root.querySelectorAll('[data-chat-suggestion]').forEach((suggestion) => {
        suggestion.addEventListener('click', () => {
            input.value = suggestion.dataset.chatSuggestion;
            sendButton.disabled = busy || input.value.trim() === '';
            input.focus();
        });
    });

    if (!isWidget) {
        initialize();
    }

    document.addEventListener('keydown', (event) => {
        if (isWidget && event.key === 'Escape' && !panel.hidden) {
            closeButton.click();
        }
    });
}

document.querySelectorAll('[data-dora-chat]').forEach(initializeDoraChat);
