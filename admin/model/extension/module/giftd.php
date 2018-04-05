<?php
class ModelExtensionModuleGiftd extends Model {

    public function install(){
        $this->db->query("ALTER TABLE `" . DB_PREFIX . "voucher` CHANGE `code` `code` VARCHAR( 25 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL");
    }

    public function uninstall(){
        $this->db->query("ALTER TABLE `" . DB_PREFIX . "voucher` CHANGE `code` `code` VARCHAR( 10 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL");
    }
}
?>