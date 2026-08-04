<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\Api\EmployeeResource;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeApiController extends BaseApiController
{
    /**
     * GET /api/v1/employees
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('employee.view');

        $employees = Employee::query()
            ->with(['branch', 'department', 'designation'])
            ->when($request->search, function ($q, $search) {
                $q->where(function ($sq) use ($search) {
                    $sq->where('employee_name', 'like', "%{$search}%")
                        ->orWhere('employee_code', 'like', "%{$search}%");
                });
            })
            ->when($request->branch_id, fn ($q, $v) => $q->where('branch_id', $v))
            ->when($request->status !== null && $request->status !== '', fn ($q) => $q->where('employment_status', $request->status))
            ->orderBy('employee_name')
            ->paginate($request->per_page ?? 25);

        return $this->successResponse(
            EmployeeResource::collection($employees)->response()->getData(true)
        );
    }

    /**
     * GET /api/v1/employees/{id}
     */
    public function show(int $id): JsonResponse
    {
        $this->authorize('employee.view');

        $employee = Employee::with(['branch', 'department', 'designation'])->findOrFail($id);

        return $this->successResponse(new EmployeeResource($employee));
    }
}
