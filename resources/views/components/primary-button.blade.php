<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center px-4 py-2.5 bg-blue-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest shadow-sm hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-800 focus:outline-hidden focus:ring-2 focus:ring-blue-500/40 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
