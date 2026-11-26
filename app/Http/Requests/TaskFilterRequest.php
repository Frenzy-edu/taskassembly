<?php

namespace App\Http\Requests;

use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaskFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $statusValues = [
            Task::STATUS_PENDING,
            Task::STATUS_IN_PROGRESS,
            Task::STATUS_COMPLETED,
            'incomplete',
        ];
        $statusRules = is_array($this->query('status'))
            ? ['nullable', 'array']
            : ['nullable', Rule::in($statusValues)];

        return [
            'search' => ['nullable', 'string', 'max:255'],
            'schedule' => [
                'nullable',
                Rule::in(['all', 'overdue', 'today', 'week', 'upcoming']),
            ],
            'view' => ['nullable', Rule::in(['list', 'grid'])],
            'status' => $statusRules,
            'status.*' => ['required', Rule::in($statusValues)],
            'due' => ['nullable', 'array'],
            'due.*' => ['required', Rule::in(['overdue', 'today', 'next'])],
            'next_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'filters' => ['nullable', Rule::in(['open'])],
            'format' => ['nullable', Rule::in(['csv', 'xlsx', 'pdf'])],
            'start' => ['sometimes', 'required', 'date'],
            'end' => ['sometimes', 'required', 'date', 'after:start'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
