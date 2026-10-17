@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-gray-300 focus:border-blue-500 focus:ring-blue-500/20 focus:ring-2 rounded-lg shadow-xs']) }}>
