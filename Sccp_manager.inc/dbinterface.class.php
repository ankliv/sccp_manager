<?php

/**
 * 
 * Core Comsnd Interface 
 * 
 * 
 */

namespace FreePBX\modules\Sccp_manager;

class dbinterface {

    private $val_null = 'NONE'; /// REPLACE to null Field

    public function __construct($parent_class = null) {
	$this->paren_class = $parent_class;
    }

    public function info() {
        $Ver = '13.0.2';
        return Array('Version' => $Ver,
            'about' => 'Data access interface ver: ' . $Ver);
    }

    /*
     * Core Access Function
     */

    public function get_db_SccpTableData($dataid, $data = array()) {
        if ($dataid == '') {
            return False;
        }
        switch ($dataid) {
            case "SccpExtension":
                if (empty($data['name'])) {
                    $sql = "SELECT * FROM `sccpline` ORDER BY `name`";
                    $raw_settings = sql($sql, "getAll", DB_FETCHMODE_ASSOC);
                } else {
                    $sql = "SELECT * FROM `sccpline` WHERE `name`=" . $data['name'];
                    $raw_settings = sql($sql, "getAll", DB_FETCHMODE_ASSOC);
                }
                break;
            case "SccpDevice":
//                $sql = "SELECT * FROM `sccpdeviceconfig` ORDER BY `name`";
                $sql = "select `name`,`name` as `mac`, `type`, `button`, `addon`, `_description` as description from `sccpdeviceconfig` ORDER BY `name`";
                $raw_settings = sql($sql, "getAll", DB_FETCHMODE_ASSOC);
                break;
            case "HWDevice":
                $raw_settings = $this->getDb_model_info($get = "phones", $format_list = "model");
                break;
            case "HWextension":
                $raw_settings = $this->getDb_model_info($get = "extension", $format_list = "model");
                break;
            case "get_colums_sccpdevice":
                $sql = "DESCRIBE sccpdevice";
                $raw_settings = sql($sql, "getAll", DB_FETCHMODE_ASSOC);
                break;
            case "get_colums_sccpuser":
                $sql = "DESCRIBE sccpuser";
                $raw_settings = sql($sql, "getAll", DB_FETCHMODE_ASSOC);
                break;
            case "get_sccpdevice_byid":
                $sql = 'SELECT t1.*, types.dns,  types.buttons, types.loadimage, types.nametemplate as nametemplate, '
                        . 'addon.buttons as addon_buttons FROM sccpdevice AS t1 '
                        . 'LEFT JOIN sccpdevmodel as types ON t1.type=types.model '
                        . 'LEFT JOIN sccpdevmodel as addon ON t1.addon=addon.model WHERE name="' . $data['id'] . '";';
                $raw_settings = sql($sql, "getRow", DB_FETCHMODE_ASSOC);
                break;
            case "get_sccpuser":
                $sql = "SELECT * FROM `sccpuser` ";
                if (!empty($data['id'])) {
                    $sql .= 'WHERE name="' . $data['id'] . '" ';
                }
                $sql .= "ORDER BY `name`;";
                $raw_settings = sql($sql, "getRow", DB_FETCHMODE_ASSOC);
                break;
            case "get_sccpdevice_buttons":
                $sql = 'SELECT * FROM sccpbuttonconfig WHERE  ref="' . $data['id'] . '" ORDER BY `instance`;';
                $raw_settings = sql($sql, "getAll", DB_FETCHMODE_ASSOC);
                break;
        }

        return $raw_settings;
    }

    public function get_db_SccpSetting() {
        $sql = "SELECT `keyword`, `data`, `type`, `seq` FROM `sccpsettings` ORDER BY `type`, `seq`";
        $raw_settings = sql($sql, "getAll", DB_FETCHMODE_ASSOC);
        return $raw_settings;
    }

    /*
     *      Get Sccp Device Model information
     */

    function getDb_model_info($get = "all", $format_list = "all", $filter = array()) {
        global $db;
        switch ($format_list) {
            case "model":
                $sel_inf = "model, vendor, dns, buttons";
                break;
            case "all":
            default:
                $sel_inf = "*";
                break;
        }

        $sel_inf .= ", '0' as 'validate'";
        switch ($get) {
            case "byciscoid":
                if (!empty($filter)) {
                    if (!empty($filter['model'])) {
                        if (strpos($filter['model'], 'loadInformation')) {
                            $sql = "SELECT " . $sel_inf . " FROM sccpdevmodel WHERE (`loadinformationid` ='" . $filter['model'] . "') ORDER BY model ";
                        } else {
                            $sql = "SELECT " . $sel_inf . " FROM sccpdevmodel WHERE (`loadinformationid` ='loadInformation" . $filter['model'] . "') ORDER BY model ";
                        }
                    } else {
//                          $sql = "SELECT ".$filter['model'];
                        $sql = "SELECT " . $sel_inf . " FROM sccpdevmodel ORDER BY model ";
                    }
                    break;
                }
                break;
            case "byid":
                if (!empty($filter)) {
                    if (!empty($filter['model'])) {
                        $sql = "SELECT " . $sel_inf . " FROM sccpdevmodel WHERE (`model` ='" . $filter['model'] . "') ORDER BY model ";
                    } else {
//                          $sql = "SELECT ".$filter['model'];
                        $sql = "SELECT " . $sel_inf . " FROM sccpdevmodel ORDER BY model ";
                    }
                    break;
                }
                break;
            case "extension":
                $sql = "SELECT " . $sel_inf . " FROM sccpdevmodel WHERE (dns = 0)ORDER BY model ";
                break;
            case "enabled":
            case "phones":
                $sql = "SELECT " . $sel_inf . " FROM sccpdevmodel WHERE (dns > 0) and (enabled > 0) ORDER BY model ";
//                $sql = "SELECT " . $sel_inf . " FROM sccpdevmodel WHERE (enabled > 0) ORDER BY model ";
                break;
            case "all":
            default:
                $sql = "SELECT " . $sel_inf . " FROM sccpdevmodel ORDER BY model ";
                break;
        }
        $raw_settings = sql($sql, "getAll", DB_FETCHMODE_ASSOC);
        return $raw_settings;
    }

    function sccp_save_db($db_name = "", $save_value = array(), $mode = 'update', $key_fld = "", $hwid = "") {
        // mode clear  - Empty tabele before update 
        // mode update - update / replace record
        global $db;
//        global $amp_conf;
        $result = "Error";

        switch ($db_name) {
            case 'sccpsettings':
                if ($mode == 'clear') {
                    $sql = 'truncate `sccpsettings`';
                    $stmt = $db->prepare($sql);
                    $stmt->execute();
                    $stmt = $db->prepare('INSERT INTO `sccpsettings` (`keyword`, `data`, `seq`, `type`) VALUES (?,?,?,?)');
                    $result = $db->executeMultiple($stmt, $save_value);
                } else {
                    $stmt = $db->prepare('REPLACE INTO `sccpsettings` (`keyword`, `data`, `seq`, `type`) VALUES (?,?,?,?)');
                    $result = $db->executeMultiple($stmt, $save_value);
                }
                break;
            case 'sccpdevmodel':
            case 'sccpdevice':
            case 'sccpuser':
                $sql_db = $db_name;
                $sql_key = "";
                $sql_var = "";
                foreach ($save_value as $key_v => $data) {
                    if (!empty($sql_var)) {
                        $sql_var .= ', ';
                    }
                    if ($data === $this->val_null) {
                        $sql_var .= '`' . $key_v . '`=NULL';
                    } else {
                        $sql_var .= '`' . $key_v . '`="' . $data . '"';
                    }
                    if ($key_fld == $key_v) {
                        $sql_key = '`' . $key_v . '`="' . $data . '"';
                    }
                }
                if (!empty($sql_var)) {
                    if ($mode == 'delete') {
                        $req = 'DELETE FROM `' . $sql_db . '` WHERE ' . $sql_key . ';';
                    } else {
                        if ($mode == 'update') {
                            $req = 'UPDATE `' . $sql_db . '` SET ' . $sql_var . ' WHERE ' . $sql_key . ';';
                        } else {
                            $req = 'REPLACE INTO `' . $sql_db . '` SET ' . $sql_var . ';';
                        }
                    }
                }
                $stmt = $db->prepare($req);
                $result = $stmt->execute();
                break;
            case 'sccpbuttons':
                if (($mode == 'clear') || ($mode == 'delete')) {
                    $sql = 'DELETE FROM `sccpbuttonconfig` WHERE ref="' . $hwid . '";';
                    $stmt = $db->prepare($sql);
                    $stmt->execute();
                }
                if ($mode == 'delete') {
                    break;
                }
                if (!empty($save_value)) {
                    $sql = 'INSERT INTO `sccpbuttonconfig` (`ref`, `reftype`,`instance`, `buttontype`, `name`, `options`) VALUES (?,?,?,?,?,?);';
//                    die(print_r($save_value,1));
                    $stmt = $db->prepare($sql);
                    $res = $db->executeMultiple($stmt, $save_value);
                }

                break;
        }
        return $result;
    }

    /*
     *  My be Replace by SccpTables ??!
     * 
     */
    public function dump_sccp_tables($data_path,  $database, $user, $pass ) {
        $filename = $data_path.'/sccp_backup_'.date('G_a_m_d_y').'.sql';
        $result = exec('mysqldump '.$database.' --password='.$pass.' --user='.$user.' --single-transaction >'.$filename ,$output);
        return $filename;
    }
    
/*
 *  Check Table structure 
 */
    public function validate() {
        global $db;
        $check_fields = array('430' => array('_hwlang' => "varchar(12)"), '431' => array('private'=> "enum('on','off')"), '433' => array('directed_pickup'=>'') );
        $sql = "DESCRIBE `sccpdevice`;";
        $raw_result = sql($sql, "getAll", DB_FETCHMODE_ASSOC);
        $result = 0;
        foreach ($raw_result as $value) {
           $id_result[$value['Field']] = $value['Type'];
        }
        foreach ($check_fields as $key => $value) {
            $sub_result = true;                    
            foreach($value as $skey => $svalue) {
              if (!empty($svalue) ) {
                if (empty($id_result[$skey])) {
                    $sub_result = false;
                } else {
                    if (strtolower($id_result[$skey]) != strtolower($svalue)) {
                        $sub_result = false;
                    }
                }                
              } else {
                if (!empty($id_result[$skey])) {
                    $sub_result = false;
                }
              }
            }
            if ($sub_result) {
                $result = $key;
            } else {
                break;
            }
        }
        
        return $result;
    }

}
