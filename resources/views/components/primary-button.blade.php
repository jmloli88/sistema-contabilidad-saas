<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-5 py-2.5 bg-cyan-500 border border-transparent rounded-xl font-semibold text-sm text-white hover:bg-cyan-600 active:bg-cyan-700 focus:outline-none focus:ring-2 focus:ring-cyan-300 focus:ring-offset-2 transition-all duration-200 shadow-sm hover:shadow-md min-h-[44px] touch-manipulation']) }}>
    {{ $slot }}
</button>
