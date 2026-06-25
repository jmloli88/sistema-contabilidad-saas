<div x-data="confirmModal" x-init="init()">
    <template x-teleport="body">
        <div x-show="open" x-cloak
             class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
            <div x-show="open"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 @click.away="cancel()"
                 class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6">
                <div class="text-center">
                    <div class="mx-auto w-12 h-12 rounded-full bg-amber-100 flex items-center justify-center mb-4">
                        <span class="material-symbols-outlined text-amber-600 text-2xl fill">warning</span>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Confirmar acción</h3>
                    <p class="text-sm text-gray-600 mb-6" x-text="message"></p>
                    <div class="flex gap-3">
                        <button @click="cancel()"
                                class="flex-1 px-4 py-2.5 text-sm font-semibold text-cyan-700 bg-white border border-cyan-200 rounded-xl hover:bg-cyan-50 transition-colors">
                            Cancelar
                        </button>
                        <button @click="confirm()"
                                class="flex-1 px-4 py-2.5 text-sm font-semibold text-white bg-cyan-500 rounded-xl hover:bg-cyan-600 transition-colors shadow-sm">
                            Confirmar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('confirmModal', () => ({
            open: false,
            message: '',
            resolvePromise: null,

            init() {
                window.__confirm = (msg) => {
                    this.message = msg;
                    this.open = true;
                    return new Promise((resolve) => {
                        this.resolvePromise = resolve;
                    });
                };

                document.addEventListener('submit', (e) => {
                    const msg = e.target.getAttribute('data-confirm');
                    if (msg) {
                        e.preventDefault();
                        this.message = msg;
                        this.open = true;
                        this.resolvePromise = (result) => {
                            if (result) {
                                e.target.removeAttribute('data-confirm');
                                e.target.submit();
                            }
                        };
                    }
                });
            },

            confirm() {
                this.open = false;
                if (this.resolvePromise) this.resolvePromise(true);
            },

            cancel() {
                this.open = false;
                if (this.resolvePromise) this.resolvePromise(false);
            },
        }));
    });
</script>
@endpush
