@props(['icon' => 'inbox', 'title' => 'No hay datos', 'description' => '', 'action' => null, 'actionLabel' => ''])
<div class="text-center py-12">
    <span class="material-symbols-outlined text-5xl text-gray-300 mb-4 block">{{ $icon }}</span>
    <h3 class="text-lg font-medium text-gray-900 mb-1">{{ $title }}</h3>
    @if($description)
        <p class="text-sm text-gray-500 mb-4">{{ $description }}</p>
    @endif
    @if($action)
        <a href="{{ $action }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-xl hover:bg-indigo-700 transition-colors">
            {{ $actionLabel }}
        </a>
    @endif
</div>
