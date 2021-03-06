<?php

namespace App\Http\Requests\Incidents;

use Auth;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class DestroyIncidentTag.
 *
 * @package App\Http\Requests
 */
class DestroyIncidentTag extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return Auth::user()->can('tagged.incident.destroy');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [];
    }
}
