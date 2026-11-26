<?php

namespace App\Http\Requests;

use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskStatusRequest extends FormRequest
{
    // No per-user auth is enforced; this is a single-user task list.
    public function authorize(): bool
    {
        return true;
    }

    // Require the new status to be one of the known task statuses.
    public function rules(): array
    {
        return [
            'status' => [
                'required',
                Rule::in([Task::STATUS_PENDING, Task::STATUS_IN_PROGRESS, Task::STATUS_COMPLETED]),
            ],
        ];
    }
}
