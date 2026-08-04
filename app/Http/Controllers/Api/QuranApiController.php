<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\Api\QuranAttendanceResource;
use App\Http\Resources\Api\QuranClassResource;
use App\Models\QuranAttendance;
use App\Models\QuranClass;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuranApiController extends BaseApiController
{
    // ── Classes ───────────────────────────────────────────────────

    /**
     * GET /api/v1/quran/classes
     */
    public function classes(Request $request): JsonResponse
    {
        $this->authorize('quran.class.view');

        $classes = QuranClass::query()
            ->with(['branch', 'teacher'])
            ->withCount('activeMembers')
            ->when($request->search, fn ($q, $v) => $q->where('class_name', 'like', "%{$v}%")
                ->orWhere('class_code', 'like', "%{$v}%"))
            ->when($request->status !== null && $request->status !== '', fn ($q) => $q->where('status', $request->status))
            ->orderBy('class_name')
            ->paginate($request->per_page ?? 25);

        return $this->successResponse(
            QuranClassResource::collection($classes)->response()->getData(true)
        );
    }

    /**
     * GET /api/v1/quran/classes/{id}
     */
    public function showClass(int $id): JsonResponse
    {
        $this->authorize('quran.class.view');

        $class = QuranClass::with(['branch', 'teacher'])->withCount('activeMembers')->findOrFail($id);

        return $this->successResponse(new QuranClassResource($class));
    }

    // ── Attendance ────────────────────────────────────────────────

    /**
     * GET /api/v1/quran/attendance
     */
    public function attendance(Request $request): JsonResponse
    {
        $this->authorize('quran.attendance.view');

        $attendance = QuranAttendance::query()
            ->with(['quranClass', 'employee', 'attendanceReason'])
            ->when($request->class_id, fn ($q, $v) => $q->where('class_id', $v))
            ->when($request->date, fn ($q, $v) => $q->where('attendance_date', $v))
            ->when($request->date_from, fn ($q, $v) => $q->where('attendance_date', '>=', $v))
            ->when($request->date_to, fn ($q, $v) => $q->where('attendance_date', '<=', $v))
            ->latest('attendance_date')
            ->paginate($request->per_page ?? 50);

        return $this->successResponse(
            QuranAttendanceResource::collection($attendance)->response()->getData(true)
        );
    }
}
