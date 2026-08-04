@extends('layouts.app')

@section('title', __('masters.quran_departments'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">{{ __('masters.quran_departments') }}</h4>
    @can('create', App\Models\QuranDepartment::class)
        <a href="{{ route('masters.quran-departments.create') }}" class="btn btn-primary btn-sm">
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
                        <th>{{ __('masters.department_name') }}</th>
                        <th>{{ __('masters.description') }}</th>
                        <th>{{ __('masters.display_order') }}</th>
                        <th>{{ __('masters.status') }}</th>
                        <th>{{ __('masters.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($departments as $department)
                        <tr>
                            <td>{{ $departments->firstItem() + $loop->index }}</td>
                            <td>{{ $department->department_name }}</td>
                            <td>{{ Str::limit($department->description, 50) ?? '-' }}</td>
                            <td>{{ $department->display_order }}</td>
                            <td>
                                <span class="badge bg-{{ $department->status->value === 1 ? 'success' : 'secondary' }}">
                                    {{ $department->status->value === 1 ? __('masters.active') : __('masters.inactive') }}
                                </span>
                            </td>
                            <td>
                                @can('update', $department)
                                    <a href="{{ route('masters.quran-departments.edit', $department) }}" class="btn btn-outline-primary btn-sm">{{ __('masters.edit') }}</a>
                                @endcan
                                @can('delete', $department)
                                    <form method="POST" action="{{ route('masters.quran-departments.destroy', $department) }}" class="d-inline"
                                          onsubmit="return confirm('{{ __('masters.confirm_delete') }}')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm">{{ __('masters.delete') }}</button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted">{{ __('masters.no_records') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $departments->withQueryString()->links() }}
    </div>
</div>
@endsection
