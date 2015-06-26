<?php
namespace Xima\CoreBundle\Model;

class GeoCode 
{
    protected $latitude;
    
    protected $longitude;
    
    protected $formattedAddress;
    
    /**
     * Returns whether the geo code has latitude and longitude.
     * 
     * @return bool
     */
    public function isValid()
    {
        return ($this->latitude && $this->longitude);
    }

    public function getLatitude() {

        return $this->latitude;
    }

    public function setLatitude($latitude) {

        $this->latitude = $latitude;
        return $this;
    }

    public function getLongitude() {

        return $this->longitude;
    }

    public function setLongitude($longitude) {

        $this->longitude = $longitude;
        return $this;
    }

    public function getFormattedAddress() {

        return $this->formattedAddress;
    }

    public function setFormattedAddress($formattedAddress) {

        $this->formattedAddress = $formattedAddress;
        return $this;
    }
 
}