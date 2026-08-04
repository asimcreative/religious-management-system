@extends('layouts.app')

@section('title', __('quran_classes.quran_classes'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">{{ __('quran_classes.quran_classes') }}</h4>
    @can('create', App\Models\QuranClass::class)
        <a href="{{ route('quran-classes.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg"></i> {{ __('quran_classes.add_new') }}
        </a>
    @endcan
</div>

<div class="card">
    <div class="card-body">
        <form method="GET" class="row g-2 mb-3">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="{{ __('quran_classes.search_placeholder') }}" value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <select name="branch_id" class="form-select form-select-sm">
                    <option value="">{{ __('quran_classes.all_branches') }}</option>
                    @foreach($branches as $id => $name)
                        <option value="{{ $id }}" {{ request('branch_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="teacher_id" class="form-select form-select-sm">
                    <option value="">{{ __('quran_classes.all_teachers') }}</option>
                    @foreach($teachers as $id => $name)
                        <option value="{{ $id }}" {{ request('teacher_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">{{ __('quran_classes.all_statuses') }}</option>
                    @foreach(App\Enums\Status::cases() as $status)
                        <option value="{{ $status->value }}" {{ request('status') === (string) $status->value ? 'selected' : '' }}>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-search"></i> {{ __('quran_classes.filter') }}
                </button>
                <a href="{{ route('quran-classes.index') }}" class="btn btn-outline-light btn-sm text-dark">
                    <i class="bi bi-x-lg"></i> {{ __('quran_classes.reset') }}
                </a>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-sm table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('quran_classes.class_code') }}</th>
                        <th>{{ __('quran_classes.class_name') }}</th>
                        <th>{{ __('quran_classes.teacher') }}</th>
                        <th>{{ __('quran_classes.branch') }}</th>
                        <th>{{ __('quran_classes.strength') }}</th>
                        <th>{{ __('quran_classes.status') }}</th>
                        <th>{{ __('quran_classes.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($classes as $class)
                        <tr>
                            <td>{{ $classes->firstItem() + $loop->index }}</td>
                            <td>{{ $class->class_code }}</td>
                            <td>
                                <a href="{{ route('quran-classes.show', $class) }}">
                                    {{ $class->class_name }}
                                </a>
                            </td>
                            <td>{{ $class->teacher?->employee?->employee_name ?? '-' }}</td>
                            <td>{{ $class->branch?->branch_name ?? '-' }}</td>
                            <td>
                                <span class="{{ $class->active_members_count >= $class->max_strength ? 'text-danger fw-bold' : '' }}">
                                    {{ $class->active_members_count }}/{{ $class->max_strength }}
                                </span>
                            </td>
                            <td>
                                <span class="badge {{ $class->status->badgeClass() }}">
                                    {{ $class->status->label() }}
                                </span>
                            </td>
                            <td>
                                @can('view', $class)
                                    <a href="{{ route('quran-classes.show', $class) }}" class="btn btn-outline-info btn-sm" title="{{ __('quran_classes.view') }}">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                @endcan
                                @can('update', $class)
                                    <a href="{{ route('quran-classes.edit', $class) }}" class="btn btn-outline-primary btn-sm" title="{{ __('quran_classes.edit') }}">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                @endcan
                                @can('delete', $class)
                                    <form method="POST" action="{{ route('quran-classes.destroy', $class) }}" class="d-inline"
                                          onsubmit="return confirm('{{ __('quran_classes.confirm_delete') }}')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm" title="{{ __('quran_classes.delete') }}">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted">{{ __('quran_classes.no_records') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $classes->withQueryString()->links() }}
    </div>
</div>
@endsection
