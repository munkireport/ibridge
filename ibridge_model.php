<?php

use CFPropertyList\CFPropertyList;

class Ibridge_model extends \Model {

	function __construct($serial='')
	{
		parent::__construct('id', 'ibridge'); //primary key, tablename
		$this->rs['id'] = '';
		$this->rs['serial_number'] = $serial;
		$this->rs['boot_uuid'] = '';
		$this->rs['build'] = '';
		$this->rs['model_identifier'] = '';
		$this->rs['model_name'] = '';
		$this->rs['ibridge_serial_number'] = '';
		$this->rs['marketing_name'] = '';
		$this->rs['ibridge_version'] = '';
		$this->rs['apple_internal'] = '';
		$this->rs['hardware_model'] = '';
		$this->rs['region_info'] = '';
		$this->rs['os_version'] = '';
		$this->rs['board_id'] = '';
		$this->rs['device_color'] = '';
		$this->rs['model_number'] = '';
		
		if ($serial) {
			$this->retrieve_record($serial);
		}
        
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

            $parser = new CFPropertyList();
            $parser->parse($plist, CFPropertyList::FORMAT_XML);
            $ibridge = $parser->toArray();

            foreach ($this->rs as $key => $value) {
                $this->rs[$key] = $value;
                if(array_key_exists($key, $ibridge))
                {
                    $this->rs[$key] = $ibridge[$key];
                }
            }

            // Resolve marketing name
            $model_id_array = explode(",",$this->rs['model_identifier']);
            $this->rs['marketing_name'] = str_replace("iBridge", "T", $model_id_array[0]);

            // Save the data (and save London Bridge from falling down!)
            $this->save();
		}
	}
}