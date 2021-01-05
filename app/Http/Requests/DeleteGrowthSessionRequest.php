<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeleteGrowthSessionRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user()->can('delete', $this->growth_session);
    }

    public function rules()
    {
        return [];
    }
}
