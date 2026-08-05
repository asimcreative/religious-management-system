<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\Api\JamaatResource;
use App\Http\Resources\Api\SalahAttendanceResource;
use App\Models\Jamaat;
use App\Models\SalahAttendance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SalahApiController extends BaseApiController
{
    // ── Jamaats ───────────────────────────────────────────────────

    /**
     * GET /api/v1/salah/jamaats
     */
    public function jamaats(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Jamaat::class);

        $jamaats = Jamaat::query()
            ->with(['branch', 'leader'])
            ->withCount('activeMembers')
            ->when($request->search, fn ($q, $v) => $q->where('jamaat_name', 'like', "%{$v}%"))
            ->when($request->status !== null && $request->status !== '', fn ($q) => $q->where('status', $request->status))
            ->orderBy('jamaat_name')
            ->paginate($this->perPage($request));

        return $this->successResponse(
            JamaatResource::collection($jamaats)->response()->getData(true)
        );
    }

    /**
     * GET /api/v1/salah/jamaats/{id}
     */
    public function showJamaat(int $id): JsonResponse
    {
        $jamaat = Jamaat::with(['branch', 'leader'])->withCount('activeMembers')->findOrFail($id);
        $this->authorize('view', $jamaat);

        return $this->successResponse(new JamaatResource($jamaat));
    }

    // ── Attendance ────────────────────────────────────────────────

    /**
     * GET /api/v1/salah/attendance
     */
    public function attendance(Request $request): JsonResponse
    {
        $this->authorize('viewAny', SalahAttendance::class);

        $attendance = SalahAttendance::query()
            ->with(['prayer', 'jamaat', 'employee', 'attendanceReason'])
            ->when($request->jamaat_id, fn ($q, $v) => $q->where('jamaat_id', $v))
            ->when($request->prayer_id, fn ($q, $v) => $q->where('prayer_id', $v))
            ->when($request->date, fn ($q, $v) => $q->where('attendance_date', $v))
            ->when($request->date_from, fn ($q, $v) => $q->where('attendance_date', '>=', $v))
            ->when($request->date_to, fn ($q, $v) => $q->where('attendance_date', '<=', $v))
            ->latest('attendance_date')
            ->paginate($this->perPage($request, 50));

        return $this->successResponse(
            SalahAttendanceResource::collection($attendance)->response()->getData(true)
        );
    }
}
