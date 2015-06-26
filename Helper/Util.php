<?php
namespace Xima\CoreBundle\Helper;

use Xima\CoreBundle\Model\GeoCode;

class Util {
    
    /**
     * Get ALL traits including those used by parent classes and the "parent" traits
     * 
     * @param $class
     * @param boolean $autoload
     * @return array
     */
    public static function classUsesDeep($class, $autoload = true)
    {
        $traits = [];
    
        // Get traits of all parent classes
        do {
            $traits = array_merge(class_uses($class, $autoload), $traits);
        } while ($class = get_parent_class($class));
    
        // Get traits of all parent traits
        $traitsToSearch = $traits;
        while (!empty($traitsToSearch)) {
            $newTraits = class_uses(array_pop($traitsToSearch), $autoload);
            $traits = array_merge($newTraits, $traits);
            $traitsToSearch = array_merge($newTraits, $traitsToSearch);
        };
    
        foreach ($traits as $trait) {
            $traits = array_merge(class_uses($trait, $autoload), $traits);
        }
    
        return array_unique($traits);
    }
    
    /**
     * Get a classes name without namespace
     * @param string $class The class as object or name with namespace
     * 
     * @return string
     */
    public static function getClassShort ($class) {
        
        $reflect = new \ReflectionClass($class);
        return $reflect->getShortName();
    }
    
    /**
     * Get a GeoCode by an adress string
     * 
     * @param string $address
     * @return \Xima\CoreBundle\Model\GeoCode|bool
     */
    public static function getGeoCode($addressString) {
    
        $geoCode = new GeoCode();
    
        // url encode the address
        $addressString = urlencode($addressString);
    
        // google map geocode api url
        $url = "http://maps.google.com/maps/api/geocode/json?sensor=false&address={$addressString}";
        //echo $url . "\n";

        // get the json response
        $responseJson = file_get_contents($url);
    
        // decode the json
        $response = json_decode($responseJson, true);
    
        // response status will be 'OK', if able to geocode given address
        if ('OK' === $response ['status'])
        {
            
            $result = array_shift($response ['results']);

            // get the important data
            $geoCode->setLatitude($result['geometry'] ['location'] ['lat']);
            $geoCode->setLongitude($result['geometry'] ['location'] ['lng']);
            $geoCode->setFormattedAddress($result['formatted_address']);
        }
    
        return $geoCode;
    }
}