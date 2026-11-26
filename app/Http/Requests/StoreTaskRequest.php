<?php

namespace App\Http\Requests;

class StoreTaskRequest extends TaskFormRequest
{
    // Extend the base task rules with the "save and add another" toggle.
    public function rules(): array
    {
        return parent::rules() + [
            'add_another' => ['sometimes', 'boolean'],
        ];
    }
}
