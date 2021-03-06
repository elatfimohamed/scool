<?php

namespace App\Http\Requests;

use Auth;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class StoreUnassignedTeacherPhoto.
 *
 * @package App\Http\Requests
 */
class StoreUnassignedTeacherPhoto extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        Auth::shouldUse('api');
        return Auth::user()->can('store-teacher-photo');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'teacher_photo' => 'required|image|dimensions:width=670,height=790'
        ];
    }
}
