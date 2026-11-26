<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReorderTasksRequest extends FormRequest
{
    // No per-user auth is enforced; this is a single-user task list.
    public function authorize(): bool
    {
        return true;
    }

    // Require a full, valid set of existing task ids plus the page being reordered.
    public function rules(): array
    {
        return [
            'task_ids' => ['required', 'array', 'min:1'],
            'task_ids.*' => ['required', 'integer', 'distinct', 'exists:tasks,id'],
            'page' => ['required', 'integer', 'min:1'],
        ];
    }
}
