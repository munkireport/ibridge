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