<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\Api\TeacherResource;
use App\Models\Teacher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeacherApiController extends BaseApiController
{
    /**
     * GET /api/v1/teachers
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('teacher.view');

        $teachers = Teacher::query()
            ->with(['employee', 'branches'])
            ->when($request->search, function ($q, $search) {
                $q->where(function ($sq) use ($search) {
                    $sq->where('teacher_code', 'like', "%{$search}%")
                        ->orWhereHas('employee', fn ($eq) => $eq->where('employee_name', 'like', "%{$search}%"));
                });
            })
            ->when($request->status !== null && $request->status !== '', fn ($q) => $q->where('status', $request->status))
            ->orderBy('teacher_code')
            ->paginate($this->perPage($request));

        return $this->successResponse(
            TeacherResource::collection($teachers)->response()->getData(true)
        );
    }

    /**
     * GET /api/v1/teachers/{id}
     */
    public function show(int $id): JsonResponse
    {
        $this->authorize('teacher.view');

        $teacher = Teacher::with(['employee', 'branches'])->findOrFail($id);

        return $this->successResponse(new TeacherResource($teacher));
    }
}
