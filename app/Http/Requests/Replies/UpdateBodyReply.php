<?php

namespace App\Http\Requests\Replies;

use Auth;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class UpdateBodyReply
 * @package App\Http\Requests
 */
class UpdateBodyReply extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return Auth::user()->can('reply.update');
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
