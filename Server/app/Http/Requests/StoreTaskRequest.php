<?php

namespace App\Http\Requests;

class StoreTaskRequest extends TaskFormRequest
{
    public function rules(): array
    {
        return parent::rules() + [
            'add_another' => ['sometimes', 'boolean'],
        ];
    }
}
