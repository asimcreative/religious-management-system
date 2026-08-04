@extends('layouts.app')

@section('title', __('masters.attendance_reasons'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">{{ __('masters.attendance_reasons') }}</h4>
    @can('create', App\Models\AttendanceReason::class)
        <a href="{{ route('masters.attendance-reasons.create') }}" class="btn btn-primary btn-sm">
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
                        <th>{{ __('masters.reason_name') }}</th>
                        <th>{{ __('masters.color') }}</th>
                        <th>{{ __('masters.counts_as_absent') }}</th>
                        <th>{{ __('masters.counts_as_leave') }}</th>
                        <th>{{ __('masters.status') }}</th>
                        <th>{{ __('masters.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reasons as $reason)
                        <tr>
                            <td>{{ $reasons->firstItem() + $loop->index }}</td>
                            <td>{{ $reason->reason_name }}</td>
                            <td>
                                @if($reason->color)
                                    <span class="badge" style="background-color: {{ $reason->color }}">{{ $reason->color }}</span>
                                @else
                                    -
                                @endif
                            </td>
                            <td>{{ $reason->counts_as_absent ? 'Yes' : 'No' }}</td>
                            <td>{{ $reason->counts_as_leave ? 'Yes' : 'No' }}</td>
                            <td>
                                <span class="badge bg-{{ $reason->status->value === 1 ? 'success' : 'secondary' }}">
                                    {{ $reason->status->value === 1 ? __('masters.active') : __('masters.inactive') }}
                                </span>
                            </td>
                            <td>
                                @can('update', $reason)
                                    <a href="{{ route('masters.attendance-reasons.edit', $reason) }}" class="btn btn-outline-primary btn-sm">{{ __('masters.edit') }}</a>
                                @endcan
                                @can('delete', $reason)
                                    <form method="POST" action="{{ route('masters.attendance-reasons.destroy', $reason) }}" class="d-inline"
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
        {{ $reasons->withQueryString()->links() }}
    </div>
</div>
@endsection
