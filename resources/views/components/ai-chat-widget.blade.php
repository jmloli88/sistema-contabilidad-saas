<div x-data="aiChat" class="fixed bottom-6 right-6 z-50">
    {{-- Chat toggle button --}}
    <button @click="open = !open"
            class="w-14 h-14 bg-indigo-600 hover:bg-indigo-700 text-white rounded-full shadow-lg flex items-center justify-center transition-transform hover:scale-110"
            :class="{ 'ring-2 ring-indigo-300': open }"
            aria-label="Abrir chat asistente">
        <span class="material-symbols-outlined text-2xl" x-text="open ? 'close' : 'smart_toy'"></span>
    </button>

    {{-- Chat panel --}}
    <div x-show="open" x-transition.opacity.duration.200ms
         @keydown.escape.window="open = false"
         class="absolute bottom-16 right-0 w-80 sm:w-96 bg-white rounded-2xl shadow-xl border border-gray-200 overflow-hidden"
         style="display: none;">
        {{-- Header --}}
        <div class="bg-indigo-600 text-white px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined">smart_toy</span>
                <span class="font-semibold text-sm">VictCorp IA</span>
            </div>
            <button x-show="messages.length" @click="clearHistory" class="text-indigo-200 hover:text-white" aria-label="Limpiar historial" title="Limpiar historial">
                <span class="material-symbols-outlined text-lg">delete_sweep</span>
            </button>
            <button @click="open = false" class="text-indigo-200 hover:text-white" aria-label="Cerrar chat">
                <span class="material-symbols-outlined text-lg">close</span>
            </button>
        </div>

        {{-- Messages --}}
        <div class="h-80 overflow-y-auto p-4 space-y-3" x-ref="messages">
            <template x-for="msg in messages" :key="msg.id">
                <div :class="msg.role === 'user' ? 'flex justify-end' : 'flex justify-start'">
                    <div :class="msg.role === 'user' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-900'"
                         class="max-w-[80%] px-3 py-2 rounded-xl text-sm whitespace-pre-wrap" x-text="msg.content">
                    </div>
                </div>
            </template>
            <div x-show="loading" class="flex justify-start">
                <div class="bg-gray-100 px-4 py-2 rounded-xl">
                    <span class="inline-flex gap-1">
                        <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></span>
                        <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.1s"></span>
                        <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></span>
                    </span>
                </div>
            </div>
            {{-- Empty state --}}
            <div x-show="!messages.length && !loading" class="text-center text-gray-400 text-sm py-8">
                <span class="material-symbols-outlined text-4xl block mb-2">chat</span>
                Hacé una pregunta sobre tus datos financieros y operativos.
            </div>
        </div>

        {{-- Input --}}
        <div class="border-t border-gray-200 p-3">
            <form @submit.prevent="sendMessage" class="flex gap-2">
                <input x-model="question" type="text" placeholder="Preguntame algo..."
                       :disabled="loading"
                       class="flex-1 text-sm border-gray-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-indigo-200 focus:border-indigo-500 disabled:bg-gray-50 disabled:cursor-not-allowed">
                <button type="submit" :disabled="!question.trim() || loading"
                        class="px-3 py-2 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 disabled:opacity-50 transition-colors"
                        aria-label="Enviar mensaje">
                    <span class="material-symbols-outlined text-lg">send</span>
                </button>
            </form>
        </div>

        {{-- PII Disclaimer --}}
        <p class="text-[10px] text-gray-400 text-center px-3 pb-2">
            Las respuestas son generadas por IA. No compartas datos personales de pacientes.
        </p>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('aiChat', () => ({
            open: false,
            question: '',
            messages: [],
            loading: false,
            historyLoaded: false,

            init() {
                // Load chat history from the database when the widget is first opened.
                // After that the conversation lives in-memory until the page is refreshed.
                this.$watch('open', async (value) => {
                    if (value && !this.historyLoaded) {
                        this.historyLoaded = true;
                        try {
                            const res = await fetch('/api/chat/history', {
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
                                    'Accept': 'application/json',
                                },
                            });
                            if (res.ok) {
                                const data = await res.json();
                                this.messages = (data.messages || []).map(m => ({
                                    id: m.id,
                                    role: m.role,
                                    content: m.content,
                                }));
                                await this.$nextTick();
                                this.scrollToBottom();
                            }
                        } catch (e) { /* noop — start fresh if history endpoint is unreachable */ }
                    }
                });
            },

            async sendMessage() {
                if (!this.question.trim()) return;

                const q = this.question.trim();
                this.messages.push({ id: Date.now(), role: 'user', content: q });
                this.question = '';
                this.loading = true;

                await this.$nextTick();
                this.scrollToBottom();

                // Create an empty assistant message that we'll fill token-by-token.
                const assistantId = Date.now() + 1;
                this.messages.push({ id: assistantId, role: 'assistant', content: '' });

                try {
                    const res = await fetch('/api/chat/stream', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
                        },
                        body: JSON.stringify({ question: q }),
                    });

                    if (!res.ok) {
                        const msg = this.messages.find(m => m.id === assistantId);
                        if (res.status === 429) {
                            msg.content = 'Alcanzaste el límite de consultas. Esperá un momento antes de preguntar de nuevo.';
                        } else if (res.status === 422) {
                            msg.content = 'Por favor, escribí una pregunta válida.';
                        } else {
                            const data = await res.json().catch(() => ({}));
                            msg.content = data.answer || 'Ocurrió un error al procesar tu consulta. Intentá de nuevo.';
                        }
                        return;
                    }

                    const reader = res.body.getReader();
                    const decoder = new TextDecoder();
                    let buffer = '';
                    let currentStatus = null;

                    const statusLabels = {
                        thinking: 'Pensando...',
                        querying: 'Consultando la base de datos...',
                        responding: '',
                    };

                    while (true) {
                        const { done, value } = await reader.read();
                        if (done) break;

                        buffer += decoder.decode(value, { stream: true });
                        const lines = buffer.split('\n');
                        buffer = lines.pop();

                        for (const line of lines) {
                            if (!line.startsWith('data: ')) continue;
                            try {
                                const data = JSON.parse(line.slice(6));
                                const msg = this.messages.find(m => m.id === assistantId);
                                if (!msg) continue;

                                if (data.status) {
                                    currentStatus = data.status;
                                    msg.content = statusLabels[data.status] ?? data.status;
                                    this.scrollToBottom();
                                    continue;
                                }

                                if (data.token) {
                                    // Wipe the placeholder text on the first real token.
                                    if (currentStatus !== null) {
                                        currentStatus = null;
                                        msg.content = '';
                                    }
                                    msg.content += data.token;
                                    this.scrollToBottom();
                                }
                            } catch (e) { /* skip malformed chunk */ }
                        }
                    }
                } catch (e) {
                    const msg = this.messages.find(m => m.id === assistantId);
                    if (msg) msg.content = 'Error de conexión. Verificá tu internet e intentá de nuevo.';
                } finally {
                    this.loading = false;
                    await this.$nextTick();
                    this.scrollToBottom();
                }
            },

            scrollToBottom() {
                if (this.$refs.messages) {
                    this.$refs.messages.scrollTop = this.$refs.messages.scrollHeight;
                }
            },

            async clearHistory() {
                if (!this.messages.length) return;
                try {
                    await fetch('/api/chat/history', {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
                            'Accept': 'application/json',
                        },
                    });
                } catch (e) { /* noop */ }
                this.messages = [];
                this.historyLoaded = false;
            },
        }));
    });
</script>
@endpush
