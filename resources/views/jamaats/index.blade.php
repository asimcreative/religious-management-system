@extends('layouts.app')

@section('title', __('jamaats.jamaats'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">{{ __('jamaats.jamaats') }}</h4>
    @can('create', App\Models\Jamaat::class)
        <a href="{{ route('jamaats.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg"></i> {{ __('jamaats.add_new') }}
        </a>
    @endcan
</div>

<div class="card">
    <div class="card-body">
        <form method="GET" class="row g-2 mb-3">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="{{ __('jamaats.search_placeholder') }}" value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <select name="branch_id" class="form-select form-select-sm">
                    <option value="">{{ __('jamaats.all_branches') }}</option>
                    @foreach($branches as $id => $name)
                        <option value="{{ $id }}" {{ request('branch_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">{{ __('jamaats.all_statuses') }}</option>
                    @foreach(App\Enums\Status::cases() as $status)
                        <option value="{{ $status->value }}" {{ request('status') === (string) $status->value ? 'selected' : '' }}>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-search"></i> {{ __('jamaats.filter') }}
                </button>
                <a href="{{ route('jamaats.index') }}" class="btn btn-outline-light btn-sm text-dark">
                    <i class="bi bi-x-lg"></i> {{ __('jamaats.reset') }}
                </a>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-sm table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('jamaats.jamaat_number') }}</th>
                        <th>{{ __('jamaats.jamaat_name') }}</th>
                        <th>{{ __('jamaats.leader') }}</th>
                        <th>{{ __('jamaats.branch') }}</th>
                        <th>{{ __('jamaats.members_count') }}</th>
                        <th>{{ __('jamaats.status') }}</th>
                        <th>{{ __('jamaats.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($jamaats as $jamaat)
                        <tr>
                            <td>{{ $jamaats->firstItem() + $loop->index }}</td>
                            <td>{{ $jamaat->jamaat_number }}</td>
                            <td>
                                <a href="{{ route('jamaats.show', $jamaat) }}">
                                    {{ $jamaat->jamaat_name }}
                                </a>
                            </td>
                            <td>{{ $jamaat->leader?->employee_name ?? '-' }}</td>
                            <td>{{ $jamaat->branch?->branch_name ?? '-' }}</td>
                            <td>{{ $jamaat->active_members_count }}</td>
                            <td>
                                <span class="badge {{ $jamaat->status->badgeClass() }}">
                                    {{ $jamaat->status->label() }}
                                </span>
                            </td>
                            <td>
                                @can('view', $jamaat)
                                    <a href="{{ route('jamaats.show', $jamaat) }}" class="btn btn-outline-info btn-sm" title="{{ __('jamaats.view') }}">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                @endcan
                                @can('update', $jamaat)
                                    <a href="{{ route('jamaats.edit', $jamaat) }}" class="btn btn-outline-primary btn-sm" title="{{ __('jamaats.edit') }}">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                @endcan
                                @can('delete', $jamaat)
                                    <form method="POST" action="{{ route('jamaats.destroy', $jamaat) }}" class="d-inline"
                                          onsubmit="return confirm('{{ __('jamaats.confirm_delete') }}')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm" title="{{ __('jamaats.delete') }}">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted">{{ __('jamaats.no_records') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $jamaats->withQueryString()->links() }}
    </div>
</div>
@endsection
