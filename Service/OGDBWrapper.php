<?php
namespace Xima\CoreBundle\Service;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

class OGDBWrapper implements ContainerAwareInterface
{
    const OGDB_REMOTE_DATA_FILE = "http://fa-technik.adfc.de/code/opengeodb/PLZ.tab";
    const OGDB_LOCAL_DATA_FILE_NAME = "zipCodes.tab";

    /**
     * @var ContainerInterface
     */
    private $container;
    
    /**
     *
     * @param int $origin
     * @param int $distance
     * @param bool $getName
     * @param bool $getDist
     */
    public function getZipCodes($origin, $distance, $getName = false, $getDist = false) {

        $filename = $this->container->getParameter('kernel.cache_dir') . '/' . OGDBWrapper::OGDB_LOCAL_DATA_FILE_NAME;
        
        if (!is_readable($filename)) 
        {
            $fileData = file_get_contents(OGDBWrapper::OGDB_REMOTE_DATA_FILE);
            if ($fileData == FALSE) 
            {
                die("ABBRUCH: konnte Daten nicht laden (" . OGDBWrapper::OGDB_REMOTE_DATA_FILE . ")\n");
            }
            if (!file_put_contents($filename, $fileData)) 
            {
                die("ABBRUCH: konnte Daten nicht speichern (" . $filename . ")\n");
            }
        }

        $fileData = file_get_contents($filename);
        if ($fileData == FALSE) 
        {
            die("ABBRUCH: konnte Daten nicht laden (" . $filename . ")\n");
        }
        error_reporting(1);
        $distance = intval($distance);
        $fileData = explode("\n", $fileData);
        
        /*
         * STEP 1: Loop through the data, search for PLZ,
         * transform coordinates to RAD
         */
        for($i = 1; $i < count($fileData); $i ++) {
            $fileRow = explode("\t", $fileData [$i]);
            if ($origin == $fileRow [1]) {
                $origin_lon = deg2rad($fileRow [2]);
                $origin_lat = deg2rad($fileRow [3]);
            }
        }
        ;
        /*
         * STEP 2: Loop through the data again, calculate the distance from origin for each item
         * and store matching items into array
         */
        $offset = 0;
        $returnvalue = array();
        for($i = 1; $i < count($fileData); $i ++) {
            $fileRow = explode("\t", $fileData [$i]);
            $destination_lon = deg2rad($fileRow [2]);
            $destination_lat = deg2rad($fileRow [3]);
            
            // distance between origin and destination
            $distance_org_dest = acos(sin($destination_lat) * sin($origin_lat) + cos($destination_lat) * cos($origin_lat) * cos($destination_lon - $origin_lon)) * 6375;
            $distance_org_dest = round($distance_org_dest);
            
            if ($distance_org_dest <= $distance) {
                if ($getName or $getDist) {
                    $returnvalue [$offset] ['zip'] = $fileRow [1];
                    if ($getName) {
                        $returnvalue [$offset] ['city'] = $fileRow [4];
                    }
                    ;
                    if ($getDist) {
                        $returnvalue [$offset] ['dist'] = $distance_org_dest;
                    }
                    ;
                } else {
                    $returnvalue [$offset] = $fileRow [1];
                }
                
                $offset ++;
            }
        }
        
        return $returnvalue;
    }
    
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }
}