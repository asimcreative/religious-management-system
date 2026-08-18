<?php

namespace App\Http\Requests\Jamaat;

use App\Models\Jamaat;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateJamaatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('jamaat.update') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $companyId = $this->user()?->company_id;
        $jamaatId = $this->route('jamaat')?->id;

        return [
            'jamaat_name' => ['required', 'string', 'max:255'],
            'jamaat_number' => [
                'required', 'string', 'max:50',
                Rule::unique('jamaats')->where('company_id', $companyId)->ignore($jamaatId),
            ],
            'branch_id' => [
                'required',
                Rule::exists('branches', 'id')->where('company_id', $companyId)->whereNull('deleted_at'),
            ],
            'leader_id' => [
                'required',
                Rule::exists('employees', 'id')->where('company_id', $companyId)->whereNull('deleted_at'),
                $this->notCommittedElsewhere($jamaatId),
            ],
            'vice_leader_id' => [
                'nullable',
                Rule::exists('employees', 'id')->where('company_id', $companyId)->whereNull('deleted_at'),
                'different:leader_id',
                $this->notCommittedElsewhere($jamaatId),
            ],
            'status' => ['required', 'integer', 'in:0,1,2'],
        ];
    }

    /**
     * This jamaat's own active members and its own current leader/vice
     * leader stay selectable — only a commitment to a *different* jamaat
     * disqualifies.
     */
    private function notCommittedElsewhere(?int $jamaatId): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($jamaatId): void {
            if (! is_numeric($value)) {
                return;
            }

            $conflict = Jamaat::leadershipConflictFor((int) $value, $jamaatId);

            if ($conflict !== null) {
                $fail(__("jamaats.leadership_conflict_{$conflict['role']}", ['jamaat' => $conflict['jamaat']]));
            }
        };
    }
}
