{{--
    Generic master-data create / edit page shell.

    Required:
      $title      string        Page title
      $singular   string        Entity name for breadcrumbs
      $plural     string        Plural entity name for breadcrumbs
      $icon       string
      $routeBase  string        e.g. 'masters.branches'
      $fieldsView string        Blade view rendering the entity's fields
      $record     ?Model        null on create

    Optional:
      $intro      string
      $aside      array<string> Bullet points explaining how the data is used
--}}
@php
    $record = $record ?? null;
    $intro = $intro ?? null;
    $aside = $aside ?? [];
@endphp

<x-page-header :title="$title" :subtitle="$intro" :icon="$icon">
    <x-slot:actions>
        <a href="{{ route($routeBase.'.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
            <span>{{ __('masters.back') }}</span>
        </a>
    </x-slot:actions>
</x-page-header>

<x-form.error-summary />

<form method="POST"
      action="{{ $record ? route($routeBase.'.update', $record) : route($routeBase.'.store') }}"
      novalidate data-guard>
    @csrf
    @if ($record)
        @method('PUT')
    @endif

    <div class="row g-4">
        <div class="col-12 col-xl-8">
            <x-card :title="$singular" :icon="$icon">
                <div class="row">
                    @include($fieldsView, ['record' => $record])
                </div>
            </x-card>
        </div>

        @if (! empty($aside))
            <div class="col-12 col-xl-4">
                <x-card :title="__('masters.how_used')" icon="bi-info-circle">
                    <ul class="stack-sm list-unstyled mb-0 fs-md text-soft">
                        @foreach ($aside as $point)
                            <li><i class="bi bi-check2 text-success me-2" aria-hidden="true"></i>{{ $point }}</li>
                        @endforeach
                    </ul>
                </x-card>
            </div>
        @endif
    </div>

    <x-form.actions :submit="__('masters.save')" :cancel-url="route($routeBase.'.index')" />
</form>
