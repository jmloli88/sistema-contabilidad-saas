<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-5 py-2.5 bg-red-500 border border-transparent rounded-xl font-semibold text-sm text-white hover:bg-red-600 active:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-300 focus:ring-offset-2 transition-all duration-200 shadow-sm hover:shadow-md']) }}>
    {{ $slot }}
</button>
