<?php

namespace App\Http\Requests;

class CalendarEventsRequest extends TaskFilterRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after:start'],
        ]);
    }
}
