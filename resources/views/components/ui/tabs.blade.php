@props([
    'tabs' => [],
    'active' => '',
    'param' => 'tab',
])

@php
    $tabKeys = array_keys($tabs);
    $queryTab = request()->query($param);
    if ($queryTab === 'cctv') {
        $queryTab = 'plano';
    }
    $initialTab = in_array($queryTab, $tabKeys, true) ? $queryTab : $active;
@endphp

<div
    {{ $attributes->only('class') }}
    data-initial-tab="{{ $initialTab }}"
    x-data="{
        activeTab: {{ \Illuminate\Support\Js::from($initialTab) }},
        param: {{ \Illuminate\Support\Js::from($param) }},
        setTab(tab) {
            this.activeTab = tab;
            const url = new URL(window.location.href);
            url.searchParams.set(this.param, tab);
            history.replaceState({}, '', url);
        }
    }"
>
    <div class="ui-tab-list" role="tablist">
        @foreach ($tabs as $key => $tab)
            <button
                type="button"
                role="tab"
                :aria-selected="activeTab === '{{ $key }}'"
                @click="setTab('{{ $key }}')"
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
