<?php

namespace App\Http\Requests\Incidents;

use App\Models\Incident;
use Auth;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class UpdateIncidentReplies
 * @package App\Http\Requests\Incidents
 */
class UpdateIncidentReplies extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return Auth::user()->hasRole('IncidentsManager') && optional($this->reply->incident)->is($this->incident);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'body' => 'required'
        ];
    }
}
