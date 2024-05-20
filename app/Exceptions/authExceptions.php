<?php

namespace App\Exceptions;

use Exception;

class authExceptions extends Exception
{
    /**
     * Report the exception.
     *
     * @return void
     */
    public $message;
    public $statusCode;

    public function __construct($message,$statusCode)
    {
        $this->message=$message;
        $this->statusCode=$statusCode;

    }
    public function report()
    {
        //
    }

    /**
     * Render the exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function render($request)
    {
        return response()->json(['message'=>$this->message,'status code'=>$this->statusCode]);
    }
}
