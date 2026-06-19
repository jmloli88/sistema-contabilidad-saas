@props(['id' => 'confirm-modal', 'title' => '¿Estás seguro?', 'message' => '', 'action' => '', 'method' => 'POST'])
<div x-data="{ open: false }" @keydown.escape.window="open = false">
    <button type="button" @click="open = true" {{ $attributes->merge(['class' => '']) }}>
        {{ $trigger ?? 'Confirmar' }}
    </button>
    <div x-show="open" class="fixed inset-0 z-50 flex items-center justify-center" style="display:none;">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="open = false"></div>
        <div class="relative bg-white rounded-2xl shadow-xl p-6 w-full max-w-sm mx-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ $title }}</h3>
            <p class="text-sm text-gray-500 mb-6">{{ $message }}</p>
            <div class="flex justify-end gap-3">
                <button @click="open = false" class="px-4 py-2 text-sm text-gray-700 bg-gray-100 rounded-xl hover:bg-gray-200">Cancelar</button>
                <form method="POST" action="{{ $action }}">
                    @csrf @method($method)
                    <button type="submit" class="px-4 py-2 text-sm text-white bg-red-600 rounded-xl hover:bg-red-700">Confirmar</button>
                </form>
            </div>
        </div>
    </div>
</div>
