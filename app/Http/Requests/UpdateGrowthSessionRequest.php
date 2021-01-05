<?php

namespace App\Http\Requests;

use App\GrowthSession;
use Illuminate\Foundation\Http\FormRequest;

class UpdateGrowthSessionRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user()->can('update', $this->growth_session);
    }

    protected function prepareForValidation()
    {
        if(empty($this->attendee_limit)) {
            $this->merge([
                'attendee_limit' => GrowthSession::NO_LIMIT
            ]);
        }
    }

    public function rules()
    {
        $minimumAttendees = 4;
        $currentAttendees = $this->growth_session->attendees()->count();

        return [
            'title' => 'sometimes|required|string|max:45',
            'topic' => 'sometimes|required|string',
            'location' => 'sometimes|required|string',
            'start_time' => 'sometimes|required|date_format:h:i a',
            'end_time' => 'sometimes|required|after:start_time|date_format:h:i a',
            'date' => 'sometimes|required|date|after_or_equal:today',
            'attendee_limit' => 'sometimes|integer|min:'.max($minimumAttendees, $currentAttendees),
        ];
    }
}
