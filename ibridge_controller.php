<?php

/**
 * Ibridge module class
 *
 * @package munkireport
 * @author tuxudo
 **/
class Ibridge_controller extends Module_controller
{
	/*** Protect methods with auth! ****/
	function __construct()
	{
        // Store module path
        $this->module_path = dirname(__FILE__);
	}

	/**
	 * Default method
	 * @author tuxudo
	 *
	 **/
    function index()
    {
        echo "You've loaded the ibridge module!";
    }

     /**
     * Get data for scroll widget
     *
     * @return void
     * @author tuxudo
     **/
    public function get_scroll_widget($column)
    {
        $sql = "SELECT COUNT(CASE WHEN ".$column." <> '' AND ".$column." IS NOT NULL THEN 1 END) AS count, ".$column." 
                FROM ibridge
                LEFT JOIN reportdata USING (serial_number)
                ".get_machine_group_filter()."
                AND ".$column." <> '' AND ".$column." IS NOT NULL 
                GROUP BY ".$column."
                ORDER BY count DESC";

        $queryobj = new Ibridge_model;
        jsonView($queryobj->query($sql));
    }

	/**
     * Retrieve data in json format
     *
     **/
    public function get_data($serial_number = '')
    {
        $sql = "SELECT model_name, model_identifier, ibridge_serial_number, ibridge_version, build, os_version, boot_uuid, marketing_name, hardware_model, device_color, model_number, region_info
                    FROM ibridge 
                    WHERE serial_number = '$serial_number'";

        $queryobj = new Ibridge_model();
        jsonView($queryobj->query($sql));
    }
} // END class Ibridge_controller
