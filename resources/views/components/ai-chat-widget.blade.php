<div x-data="aiChat" class="fixed bottom-6 right-6 z-50">
    {{-- Backdrop overlay for mobile --}}
    <div x-show="open" @click="open = false"
         x-transition:enter="transition-opacity duration-200"
         x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity duration-200"
         x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-black/40 z-40 lg:hidden" style="display: none;">
    </div>

    {{-- Chat toggle button --}}
    <button @click="open = !open"
            class="w-14 h-14 bg-cyan-500 hover:bg-cyan-600 text-white rounded-full shadow-lg flex items-center justify-center transition-transform hover:scale-110 z-50 relative"
            :class="{ 'ring-2 ring-cyan-300': open }"
            aria-label="Abrir chat asistente">
        <span class="material-symbols-outlined text-2xl" x-text="open ? 'close' : 'smart_toy'"></span>
    </button>

    {{-- Chat panel --}}
    <div x-show="open" x-transition.opacity.duration.200ms
         @keydown.escape.window="open = false"
         class="fixed inset-0 lg:absolute lg:inset-auto lg:bottom-16 lg:right-0 lg:w-96 bg-white shadow-xl border-0 lg:border lg:border-gray-200 overflow-hidden rounded-none lg:rounded-2xl z-50"
         style="display: none;">
        {{-- Header --}}
        <div class="bg-cyan-500 text-white px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined">smart_toy</span>
                <span class="font-semibold text-sm">VictCorp IA</span>
            </div>
            <div class="flex items-center gap-1">
                <button x-show="messages.length" @click="clearHistory" class="text-cyan-200 hover:text-white p-1.5 rounded-xl hover:bg-white/10" aria-label="Limpiar historial" title="Limpiar historial">
                    <span class="material-symbols-outlined text-lg">delete_sweep</span>
                </button>
                <button @click="open = false" class="text-cyan-200 hover:text-white p-1.5 rounded-xl hover:bg-white/10 lg:hidden" aria-label="Cerrar chat">
                    <span class="material-symbols-outlined text-lg">close</span>
                </button>
            </div>
        </div>

        {{-- Messages --}}
        <div class="h-[calc(100vh-8rem)] lg:h-80 overflow-y-auto p-4 space-y-3 scroll-smooth" x-ref="messages" style="-webkit-overflow-scrolling: touch;">
            <template x-for="msg in messages" :key="msg.id">
                <div :class="msg.role === 'user' ? 'flex justify-end' : 'flex justify-start'">
                    <div :class="msg.role === 'user' ? 'bg-cyan-500 text-white' : 'bg-gray-100 text-gray-900'"
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
        <div class="border-t border-gray-200 p-3 pb-[max(0.75rem,env(safe-area-inset-bottom))]">
            <form @submit.prevent="sendMessage" class="flex gap-2">
                <input x-model="question" type="text" placeholder="Preguntame algo..."
                       :disabled="loading"
                       class="flex-1 text-sm border-gray-200 rounded-xl px-3 py-2.5 lg:py-2 focus:ring-2 focus:ring-cyan-200 focus:border-cyan-400 disabled:bg-gray-50 disabled:cursor-not-allowed">
                <button type="submit" :disabled="!question.trim() || loading"
                        class="px-3 py-2.5 min-h-[44px] min-w-[44px] bg-cyan-500 text-white rounded-xl hover:bg-cyan-600 disabled:opacity-50 transition-colors flex items-center justify-center"
                        aria-label="Enviar mensaje">
                    <span class="material-symbols-outlined text-lg">send</span>
                </button>
            </form>
        </div>

        {{-- PII Disclaimer --}}
        <p class="text-[10px] text-gray-400 text-center px-3 pb-2 hidden lg:block">
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
                this.$watch('open', async (value) => {
                    // Lock body scroll when chat is open on mobile
                    if (value) {
                        document.body.style.overflow = 'hidden';
                    } else {
                        document.body.style.overflow = '';
                    }

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
                        } catch (e) { /* noop */ }
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
                                    if (currentStatus !== null) {
                                        currentStatus = null;
                                        msg.content = '';
                                    }
                                    msg.content += data.token;
                                    this.scrollToBottom();
                                }
                            } catch (e) { /* skip */ }
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
