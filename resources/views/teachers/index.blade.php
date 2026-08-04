@extends('layouts.app')

@section('title', __('teachers.teachers'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">{{ __('teachers.teachers') }}</h4>
    @can('create', App\Models\Teacher::class)
        <a href="{{ route('teachers.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg"></i> {{ __('teachers.add_new') }}
        </a>
    @endcan
</div>

<div class="card">
    <div class="card-body">
        <form method="GET" class="row g-2 mb-3">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="{{ __('teachers.search_placeholder') }}" value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <select name="branch_id" class="form-select form-select-sm">
                    <option value="">{{ __('teachers.all_branches') }}</option>
                    @foreach($branches as $id => $name)
                        <option value="{{ $id }}" {{ request('branch_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">{{ __('teachers.all_statuses') }}</option>
                    @foreach(App\Enums\Status::cases() as $status)
                        <option value="{{ $status->value }}" {{ request('status') === (string) $status->value ? 'selected' : '' }}>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-search"></i> {{ __('teachers.filter') }}
                </button>
                <a href="{{ route('teachers.index') }}" class="btn btn-outline-light btn-sm text-dark">
                    <i class="bi bi-x-lg"></i> {{ __('teachers.reset') }}
                </a>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-sm table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('teachers.teacher_code') }}</th>
                        <th>{{ __('teachers.teacher_name') }}</th>
                        <th>{{ __('teachers.branches') }}</th>
                        <th>{{ __('teachers.status') }}</th>
                        <th>{{ __('teachers.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($teachers as $teacher)
                        <tr>
                            <td>{{ $teachers->firstItem() + $loop->index }}</td>
                            <td>{{ $teacher->teacher_code }}</td>
                            <td>
                                <a href="{{ route('teachers.show', $teacher) }}">
                                    {{ $teacher->employee?->employee_name ?? '-' }}
                                </a>
                            </td>
                            <td>
                                @foreach($teacher->branches as $branch)
                                    <span class="badge bg-light text-dark">{{ $branch->branch_name }}</span>
                                @endforeach
                            </td>
                            <td>
                                <span class="badge {{ $teacher->status->badgeClass() }}">
                                    {{ $teacher->status->label() }}
                                </span>
                            </td>
                            <td>
                                @can('view', $teacher)
                                    <a href="{{ route('teachers.show', $teacher) }}" class="btn btn-outline-info btn-sm" title="{{ __('teachers.view') }}">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                @endcan
                                @can('update', $teacher)
                                    <a href="{{ route('teachers.edit', $teacher) }}" class="btn btn-outline-primary btn-sm" title="{{ __('teachers.edit') }}">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                @endcan
                                @can('delete', $teacher)
                                    <form method="POST" action="{{ route('teachers.destroy', $teacher) }}" class="d-inline"
                                          onsubmit="return confirm('{{ __('teachers.confirm_delete') }}')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm" title="{{ __('teachers.delete') }}">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">{{ __('teachers.no_records') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $teachers->withQueryString()->links() }}
    </div>
</div>
@endsection
