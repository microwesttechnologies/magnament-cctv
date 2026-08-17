@props([
    'tabs' => [],
    'active' => '',
    'param' => 'tab',
])

<div {{ $attributes->only('class') }} x-data="{ activeTab: '{{ $active }}' }">
    <div class="ui-tab-list" role="tablist">
        @foreach ($tabs as $key => $tab)
            <button
                type="button"
                role="tab"
                :aria-selected="activeTab === '{{ $key }}'"
                @click="activeTab = '{{ $key }}'"
                :class="activeTab === '{{ $key }}' ? 'ui-tab ui-tab-active' : 'ui-tab'"
            >
                {{ is_array($tab) ? ($tab['label'] ?? $key) : $tab }}
            </button>
        @endforeach
    </div>
    <div class="mt-6">
        {{ $slot }}
    </div>
</div>
