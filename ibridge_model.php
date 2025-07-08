<?php

use CFPropertyList\CFPropertyList;

class Ibridge_model extends \Model {

    function __construct($serial='')
    {
        parent::__construct('id', 'ibridge'); //primary key, tablename
        $this->rs['id'] = '';
        $this->rs['serial_number'] = $serial;
        $this->rs['boot_uuid'] = NULL;
        $this->rs['build'] = NULL;
        $this->rs['model_identifier'] = NULL;
        $this->rs['model_name'] = NULL;
        $this->rs['ibridge_serial_number'] = NULL;
        $this->rs['marketing_name'] = NULL;
        $this->rs['ibridge_version'] = NULL;
        $this->rs['apple_internal'] = NULL;
        $this->rs['hardware_model'] = NULL;
        $this->rs['region_info'] = NULL;
        $this->rs['os_version'] = NULL;
        $this->rs['board_id'] = NULL;
        $this->rs['model_number'] = NULL;
        $this->rs['device_color'] = NULL;

        // if ($serial) {
            // $this->retrieve_record($serial);
        // }

        $this->serial_number = $serial;
    }

    // ------------------------------------------------------------------------

    /**
    * Process data sent by postflight
    *
    * @param plist data
    * @author tuxudo
    **/
    function process($plist)
    {
        // If plist is empty, echo out error
        if (! $plist) {
            echo ("Error Processing iBridge module: No data found");
        } else {

            // Delete previous entries
            $this->deleteWhere('serial_number=?', $this->serial_number);

            $parser = new CFPropertyList();
            $parser->parse($plist, CFPropertyList::FORMAT_XML);
            $ibridge = $parser->toArray();

            foreach ($this->rs as $key => $value) {
                $this->rs[$key] = $value;
                if(array_key_exists($key, $ibridge)) {
                    $this->rs[$key] = $ibridge[$key];
                }
            }

            // Resolve marketing name
            $model_id_array = explode(",",(string)$this->rs['model_identifier']);
            $this->rs['marketing_name'] = str_replace("iBridge", "T", $model_id_array[0]);

            // Save the data (and save London Bridge from falling down!)
            $this->save();

            // Set and save proper device icon and model description if Apple Silicon Mac and has model description
            if (str_contains($this->rs['build'], 'iBoot') && str_contains($this->rs['model_identifier'], 'Mac') && array_key_exists("machine_desc", $ibridge)) {

                // Check that we have a proper URL
                $color_url_result = $this->device_color_url($this->rs['model_identifier'], $ibridge['machine_desc'], $this->rs['device_color']);
                if ($color_url_result){
                    $sql = "UPDATE `machine` SET `machine_desc` = '".$ibridge['machine_desc']."', `img_url` = '$color_url_result' WHERE serial_number = '$this->serial_number'";
                } else {
                    $sql = "UPDATE `machine` SET `machine_desc` = '".$ibridge['machine_desc']."' WHERE serial_number = '$this->serial_number'";
                }

                $queryobj = new Ibridge_model;
                $queryobj->query($sql);

            // Set and save model description if present
            } else if (array_key_exists("machine_desc", $ibridge)) {
                $sql = "UPDATE `machine` SET `machine_desc` = '".$ibridge['machine_desc']."' WHERE serial_number = '$this->serial_number'";

                $queryobj = new Ibridge_model;
                $queryobj->query($sql);
            }
        }
    }

    // Determine device color URL
    function device_color_url($machine_model, $machine_desc_input, $device_color)
    {
        $machine_desc_array = explode("(", $machine_desc_input);
        $machine_desc = str_replace(" ", "", $machine_desc_array[0]);
        $color_url = str_replace(" ", "", strtolower($device_color));

        // 13" 2022 Macbook Air Starlight color doesn't work :(
        if ($color_url == "starlight" && $machine_model == "Mac14,2"){
            $color_url = "silver";
        }

        // Manually set some Macs because they only have one color or the iBridge doesn't properly report the color
        if ($machine_desc !== "MacPro" && $machine_desc !== "Macmini" && $machine_desc !== "MacStudio" && $machine_desc !== "iMacPro" && $machine_model !== "iMac20,1" && $machine_model !== "iMac20,2"){
            return "https://statici.icloud.com/fmipmobile/deviceImages-9.0/".urlencode($machine_desc)."/".urlencode($machine_model)."-".urlencode($color_url)."/online-infobox__2x.png";
        // } else if ($machine_desc == "MacPro" || $machine_desc == "Macmini" || $machine_desc == "MacStudio" || $machine_desc == "iMac"){
        //     // https://statici.icloud.com/fmipmobile/deviceImages-9.0/MacPro/MacPro7%2C1/online-infobox__2x.png
        //     // https://statici.icloud.com/fmipmobile/deviceImages-9.0/MacPro/Mac14%2C8/online-infobox__2x.png
        //     // https://statici.icloud.com/fmipmobile/deviceImages-9.0/Macmini/Macmini8%2C1/online-infobox__2x.png
        //     // https://statici.icloud.com/fmipmobile/deviceImages-9.0/Macmini/Macmini9%2C1/online-infobox__2x.png
        //     // https://statici.icloud.com/fmipmobile/deviceImages-9.0/Macmini/Mac14%2C12/online-infobox__2x.png
        //     // https://statici.icloud.com/fmipmobile/deviceImages-9.0/Macmini/Mac16%2C10/online-infobox__2x.png
        //     // https://statici.icloud.com/fmipmobile/deviceImages-9.0/iMac/iMac20%2C1/online-infobox__2x.png
        //     return "https://statici.icloud.com/fmipmobile/deviceImages-9.0/".urlencode($machine_desc)."/".urlencode($machine_model)."/online-infobox__2x.png";

        // } else if ($machine_desc == "iMacPro"){
        //     // https://statici.icloud.com/fmipmobile/deviceImages-9.0/iMac/iMacPro1%2C1/online-infobox__2x.png
        //     return "https://statici.icloud.com/fmipmobile/deviceImages-9.0/iMac/".urlencode($machine_model)."/online-infobox__2x.png";
        } else {
            return false;
        }
    }
}






