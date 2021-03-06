<?php

namespace App\Http\Requests\Incidents;

use Auth;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class StoreIncidentTag.
 *
 * @package App\Http\Requests
 */
class StoreIncidentTag extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return Auth::user()->can('tag.store',$this->incident);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'value' => 'required'
        ];
    }
}
