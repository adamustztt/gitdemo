<?php


namespace App\Http\Validate;


class BaseValidate
{
    protected $message = [

    ];

    //错误信息
    private $error;

    /**
     * @return mixed
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @param mixed $error
     */
    public function setError($error): void
    {
        $this->error = $error;
    }
}