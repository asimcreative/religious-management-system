@extends('layouts.app')

@section('title', __('masters.languages'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">{{ __('masters.languages') }}</h4>
    @can('create', App\Models\Language::class)
        <a href="{{ route('masters.languages.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg"></i> {{ __('masters.add_new') }}
        </a>
    @endcan
</div>

<div class="card">
    <div class="card-body">
        <form method="GET" class="row g-2 mb-3">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="{{ __('masters.search') }}" value="{{ request('search') }}">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-outline-secondary btn-sm">{{ __('masters.search') }}</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-sm table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('masters.language_name') }}</th>
                        <th>{{ __('masters.native_name') }}</th>
                        <th>{{ __('masters.locale') }}</th>
                        <th>{{ __('masters.direction') }}</th>
                        <th>{{ __('masters.status') }}</th>
                        <th>{{ __('masters.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($languages as $language)
                        <tr>
                            <td>{{ $languages->firstItem() + $loop->index }}</td>
                            <td>{{ $language->language_name }}</td>
                            <td>{{ $language->native_name ?? '-' }}</td>
                            <td><code>{{ $language->locale }}</code></td>
                            <td>{{ $language->direction === 'rtl' ? __('masters.rtl') : __('masters.ltr') }}</td>
                            <td>
                                <span class="badge bg-{{ $language->status->value === 1 ? 'success' : 'secondary' }}">
                                    {{ $language->status->value === 1 ? __('masters.active') : __('masters.inactive') }}
                                </span>
                            </td>
                            <td>
                                @can('update', $language)
                                    <a href="{{ route('masters.languages.edit', $language) }}" class="btn btn-outline-primary btn-sm">{{ __('masters.edit') }}</a>
                                @endcan
                                @can('delete', $language)
                                    <form method="POST" action="{{ route('masters.languages.destroy', $language) }}" class="d-inline"
                                          onsubmit="return confirm('{{ __('masters.confirm_delete') }}')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm">{{ __('masters.delete') }}</button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted">{{ __('masters.no_records') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $languages->withQueryString()->links() }}
    </div>
</div>
@endsection
