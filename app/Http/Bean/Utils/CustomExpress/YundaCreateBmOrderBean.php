<?php


namespace App\Http\Bean\Utils\CustomExpress;


use App\Http\Bean\BaseBean;

class YundaCreateBmOrderBean extends BaseBean
{
    /**
     * 订单编号
     */
    private $orderNumber;

    /**
     * 发件人姓名
     */
    private $sendName;

    /**
     * 发件人省份
     */
    private $sendProvince;

    /**
     * 发件人城市
     */
    private $sendCity;

    /**
     * 发件人区/县
     */
    private $sendCountry;

    /**
     * 发件人详细地址
     */
    private $sendAddress;

    /**
     * 收件人手机号码
     */
    private $receivePhone;

    /**
     * 收件人姓名
     */
    private $receiveName;

    /**
     *  收件人省份
     */
    private $receiveProvince;

    /**
     * 收件人城市
     */
    private $receiveCity;

    /**
     * 收件人区/县
     */
    private $receiveCountry;

    /**
     * 收件人详细地址
     */
    private $receiveAddress;

    /**
     * @return mixed
     */
    public function getOrderNumber()
    {
        return $this->orderNumber;
    }

    /**
     * @param mixed $orderNumber
     */
    public function setOrderNumber($orderNumber): void
    {
        $this->orderNumber = $orderNumber;
    }

    /**
     * @return mixed
     */
    public function getSendName()
    {
        return $this->sendName;
    }

    /**
     * @param mixed $sendName
     */
    public function setSendName($sendName): void
    {
        $this->sendName = $sendName;
    }

    /**
     * @return mixed
     */
    public function getSendProvince()
    {
        return $this->sendProvince;
    }

    /**
     * @param mixed $sendProvince
     */
    public function setSendProvince($sendProvince): void
    {
        $this->sendProvince = $sendProvince;
    }

    /**
     * @return mixed
     */
    public function getSendCity()
    {
        return $this->sendCity;
    }

    /**
     * @param mixed $sendCity
     */
    public function setSendCity($sendCity): void
    {
        $this->sendCity = $sendCity;
    }

    /**
     * @return mixed
     */
    public function getSendCountry()
    {
        return $this->sendCountry;
    }

    /**
     * @param mixed $sendCountry
     */
    public function setSendCountry($sendCountry): void
    {
        $this->sendCountry = $sendCountry;
    }

    /**
     * @return mixed
     */
    public function getSendAddress()
    {
        return $this->sendAddress;
    }

    /**
     * @param mixed $sendAddress
     */
    public function setSendAddress($sendAddress): void
    {
        $this->sendAddress = $sendAddress;
    }

    /**
     * @return mixed
     */
    public function getReceivePhone()
    {
        return $this->receivePhone;
    }

    /**
     * @param mixed $receivePhone
     */
    public function setReceivePhone($receivePhone): void
    {
        $this->receivePhone = $receivePhone;
    }

    /**
     * @return mixed
     */
    public function getReceiveName()
    {
        return $this->receiveName;
    }

    /**
     * @param mixed $receiveName
     */
    public function setReceiveName($receiveName): void
    {
        $this->receiveName = $receiveName;
    }

    /**
     * @return mixed
     */
    public function getReceiveProvince()
    {
        return $this->receiveProvince;
    }

    /**
     * @param mixed $receiveProvince
     */
    public function setReceiveProvince($receiveProvince): void
    {
        $this->receiveProvince = $receiveProvince;
    }

    /**
     * @return mixed
     */
    public function getReceiveCity()
    {
        return $this->receiveCity;
    }

    /**
     * @param mixed $receiveCity
     */
    public function setReceiveCity($receiveCity): void
    {
        $this->receiveCity = $receiveCity;
    }

    /**
     * @return mixed
     */
    public function getReceiveCountry()
    {
        return $this->receiveCountry;
    }

    /**
     * @param mixed $receiveCountry
     */
    public function setReceiveCountry($receiveCountry): void
    {
        $this->receiveCountry = $receiveCountry;
    }

    /**
     * @return mixed
     */
    public function getReceiveAddress()
    {
        return $this->receiveAddress;
    }

    /**
     * @param mixed $receiveAddress
     */
    public function setReceiveAddress($receiveAddress): void
    {
        $this->receiveAddress = $receiveAddress;
    }
}