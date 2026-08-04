@extends('layouts.app')

@section('title', __('masters.branches'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">{{ __('masters.branches') }}</h4>
    @can('create', App\Models\Branch::class)
        <a href="{{ route('masters.branches.create') }}" class="btn btn-primary btn-sm">
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
                        <th>{{ __('masters.branch_name') }}</th>
                        <th>{{ __('masters.address') }}</th>
                        <th>{{ __('masters.phone') }}</th>
                        <th>{{ __('masters.status') }}</th>
                        <th>{{ __('masters.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($branches as $branch)
                        <tr>
                            <td>{{ $branches->firstItem() + $loop->index }}</td>
                            <td>{{ $branch->branch_name }}</td>
                            <td>{{ $branch->address ?? '-' }}</td>
                            <td>{{ $branch->phone ?? '-' }}</td>
                            <td>
                                <span class="badge bg-{{ $branch->status->value === 1 ? 'success' : 'secondary' }}">
                                    {{ $branch->status->value === 1 ? __('masters.active') : __('masters.inactive') }}
                                </span>
                            </td>
                            <td>
                                @can('update', $branch)
                                    <a href="{{ route('masters.branches.edit', $branch) }}" class="btn btn-outline-primary btn-sm">
                                        {{ __('masters.edit') }}
                                    </a>
                                @endcan
                                @can('delete', $branch)
                                    <form method="POST" action="{{ route('masters.branches.destroy', $branch) }}" class="d-inline"
                                          onsubmit="return confirm('{{ __('masters.confirm_delete') }}')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm">{{ __('masters.delete') }}</button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">{{ __('masters.no_records') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $branches->withQueryString()->links() }}
    </div>
</div>
@endsection
