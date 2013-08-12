<?php
require_once(ROOT . 'inc/core/d.object.class.php');
require_once(ModelEngineRoot . 'inc/modules/dataaccess/d.modelengine.inc.php');

/**
 * 模型引擎系统数据层模型模型属性类。
 */
class DAttribute extends DObject implements IDAttribute {    
    private $db = null; 
    private $sql_init = null; 
    private $table = null; 
    
    /**
     * 构造函数。
     */
    function  __construct(){
        //parent::__construct();
        $this->table = 'modelengine_core_attributes'; 
        $this->sql_init = implode('/', array(ROOT, 'model-engine', 'sql', 'sqlite', 'modelengine.core')); 
    }
    
    /**
     * 构造函数。
     */
    function DAttribute(){
        $this->__construct();
    }
    
    /*- IDAttribute 接口实现 START -*/
    /*- IInjectEnable 接口实现 START -*/
    /**
     * 设置属性值。
     */
    public function __set($prop, $val){
        $this->$prop = $val;
    }
    /*- IInjectEnable 接口实现 END -*/
        
    /**
     * 获取模型属性的列表。
     * @param $code {String} 模型的编码。
     * @param $editable {Boolean} 是否只列出可编辑属性。
     * @return 模型属性的列表。
     */
    public function getList($code, $editable = null){
        $this->initialise();
        $conditions = array();
        array_push($conditions, 'model_code = \'' . $code . '\'');
        if($editable){
            array_push($conditions, 'AND');
            array_push($conditions, 'is_editable = 1');
        }
        $conditions = implode(' ', $conditions);
        $settings = $this->getListSettings($conditions);
        return $this->retrieve($this->db, $settings);
    }
    
    /**
     * 根据模型编码和属性名获取模型属性的数据实体。
     * @param $code {String} 模型的编码。
     * @param $field {String} 模型属性名。
     * @return Array 模型属性的数据实体。
     */
    public function getEntity($code, $field){
        $this->initialise();
        $conditions = array();
        array_push($conditions, 'model_code = \'' . $code . '\'');
        array_push($conditions, 'AND');
        array_push($conditions, 'attribute_name = \'' . $field . '\'');
        $conditions = implode(' ', $conditions);
        $settings = $this->getListSettings($conditions);
        return $this->retrieveLine($this->db, $settings);
    }
    
    /**
     * 获取模型扩展属性值的列表。
     * @param $modelId {Int} 模型数据的ID。
     * @param $table {String} 模型扩展属性存储的数据表。
     * @param $attributes {Array} 模型属性数据。
     * 格式：
     * array(array(
     *       'id' => '',
     *       'name' => '',
     *       'comment' => '',
     *       'type' => '',
     *       'default' => '',
     *       'ext' => '',
     *       'editable' => '',
     *       'autoupdate' => '',
     *       'primary' => '',
     *       'position' => '',
     *       'value' => ''
     * ),... ...)
     * @param $ext {Array} 数据库切割需要的扩展参数。
	 * @return {Array} 模型扩展属性值的列表。。
     */
    public function getAttributeValues($modelId, $table, $attributes, $ext = array()){
        $result = array();
        $rows = $this->getAttributeValueList($modelId, $table, $attributes, $ext);
        foreach($rows as $idx=>$row){
            $result[$row['name']] = $row['value'];
        }
        return $result;
    }
    
    /**
     * 根据模型ID，获取模型扩展属性的属性值列表。
     * @param $modelId {Int} 模型数据的ID。
     * @param $table {String} 模型扩展属性存储的数据表。
     * @param $attributes {Array} 模型属性数据。
     * 格式：
     * array(array(
     *       'id' => '',
     *       'name' => '',
     *       'comment' => '',
     *       'type' => '',
     *       'default' => '',
     *       'ext' => '',
     *       'editable' => '',
     *       'autoupdate' => '',
     *       'primary' => '',
     *       'position' => '',
     *       'value' => ''
     * ),... ...)
     * @param $ext {Array} 数据库切割需要的扩展参数。
     * @return {Array} 模型表单的属性列表。
     */
    public function getAttributeValueList($modelId, $table, $attributes, $ext = array()){
        $result = array();
        $settings = array(
            'table' => $table, 
            'alias' => 'ma',
            'fields' => array(
                'attribute' => 'attribute_id',
                'value' => 'str_vlaue'
                ),
            'conditions' => 'ma.model_id = ' . $modelId
        );
        $db = $this->getDbByTable($table, $ext);
        $rows = $this->retrieve($db, $settings);
        if(count($rows) > 0){
            $dict = array();
            foreach($attributes as $attribute){
                $dict[$attribute['id']] = $attribute;
            }
            foreach($rows as $row){
                $attribute_id = $row['attribute'];
                if(array_key_exists($attribute_id, $dict)){
                    $attr = array();
                    $attribute = $dict[$attribute_id];
                    $attr['model_id'] = $modelId;
                    $attr['attribute_id'] = $attribute_id;
                    $attr['name'] = $attribute['name'];
                    $attr['ext'] = $attribute['ext'];
                    $attr['editable'] = $attribute['editable'];
                    $attr['value'] = $row['value'];
                    array_push($result, $attr);
                }
            }
        }
        return $result;
    }
    
    /**
     * 创建模型的扩展属性。
     * @param $code {String} 模型的编码。
     * @param $modelId {Int} 模型数据的ID。
     * @param $table {String} 模型扩展属性存储的数据表。
     * @param $attributes {Array} 模型扩展属性数据。
     * 格式：
     * array(array(
     *       'id' => '',
     *       'name' => '',
     *       'comment' => '',
     *       'type' => '',
     *       'default' => '',
     *       'ext' => '',
     *       'editable' => '',
     *       'autoupdate' => '',
     *       'primary' => '',
     *       'position' => '',
     *       'value' => ''
     * ),... ...)
     * @param $ext {Array} 数据库切割需要的扩展参数。
     */
    public function create($code, $modelId, $table, $attributes, $ext = array()){
        $result = true;
        foreach($attributes as $attribute){
            if((strlen($code) > 0) && ($modelId > 0) 
                && array_key_exists('id', $attribute) && (strlen($attribute['id']) > 0)
                && array_key_exists('value', $attribute) && (strlen($attribute['value']) > 0)){
                $check = $this->attribute_insert($code, $modelId, $table, $attribute, $ext);
                $result = $result && $check;
            }
        }
        return $result;
    }
    
    /**
     * 修改一个模型的扩展属性值。
     * @param $code {String} 模型的编码。
     * @param $modelId {Int} 模型数据的ID。
     * @param $table {String} 模型扩展属性存储的数据表。
     * @param $attributes {Array} 模型扩展属性数据。
     * 格式：
     * array(array(
     *       'id' => '',
     *       'name' => '',
     *       'comment' => '',
     *       'type' => '',
     *       'default' => '',
     *       'ext' => '',
     *       'editable' => '',
     *       'autoupdate' => '',
     *       'primary' => '',
     *       'position' => '',
     *       'value' => ''
     * ),... ...)
     * @param $ext {Array} 数据库切割需要的扩展参数。
	 * @return Boolean 是否操作成功。
     */
    public function save($code, $modelId, $table, $attributes, $ext = array()){
        $result = true;
        foreach($attributes as $attribute){
            if((strlen($code) > 0) && ($modelId > 0) 
                && array_key_exists('id', $attribute) && (strlen($attribute['id']) > 0)
                && array_key_exists('value', $attribute)){
                $exists = $this->attribute_exists($code, $modelId, $table, $attribute, $ext);
                $check = false;
                if($exists){
                    $check = $this->attribute_update($code, $modelId, $table, $attribute, $ext);
                }else{
                    $check = $this->attribute_insert($code, $modelId, $table, $attribute, $ext);
                }
                $result = $result && $check;
            }
        }
        return $result;
    }
    
    /**
     * 删除一个模型的扩展属性值。
     * @param $code {String} 模型的编码。
     * @param $modelId {Int} 模型数据的ID。
     * @param $table {String} 模型扩展属性存储的数据表。
     * @param $attribute {Int} 模型属性的ID。
     * @param $ext {Array} 数据库切割需要的扩展参数。
	 * @return Boolean 是否操作成功。
     */
    public function removeValue($code, $modelId, $table, $attribute = -1, $ext = array()){
        $conditions = array();
        array_push($conditions, '(model_code=\'' . $code . '\')');
        array_push($conditions, 'AND');
        array_push($conditions, '(model_id=' . $modelId . ')');
        if($attribute > -1){
            array_push($conditions, 'AND');
            array_push($conditions, '(attribute_id=' .$attribute . ')');
        }
        $conditions = implode(' ', $conditions);
        $settings = array(
            'table' => $table, 
            'conditions' => $conditions
        );
        $db = $this->getDbByTable($table, $ext);
        $this->delete($db, $settings);
        return true;
    }
    /*- IDAttribute 接口实现 END -*/
    
    /*- 私有方法 START -*/
    /**
     * 初始化数据库。
     */
    private function initialise(){
        $this->db = $this->getDbByTable($this->table);
        if(!is_file($this->db)){
            $this->initialize($this->db, $this->sql_init);
        }
    }
    
    /**
     * 检测一个模型的扩展属性是否存在。
     * @param $code {String} 模型的编码。
     * @param $modelId {Int} 模型数据的ID。
     * @param $table {String} 模型扩展属性存储的数据表。
     * @param $attribute {Array} 模型扩展属性数据。
     * 格式：
     * array(
     *       'id' => '',
     *       'name' => '',
     *       'comment' => '',
     *       'type' => '',
     *       'default' => '',
     *       'ext' => '',
     *       'editable' => '',
     *       'autoupdate' => '',
     *       'primary' => '',
     *       'position' => '',
     *       'value' => ''
     * )
     * @param $ext {Array} 数据库切割需要的扩展参数。
	 * @return Boolean 是否存在。
     */
    private function attribute_exists($code, $modelId, $table, $attribute, $ext = array()){
        $conditions = array();
        array_push($conditions, '(model_code=\'' . $code . '\')');
        array_push($conditions, 'AND');
        array_push($conditions, '(model_id=' . $modelId . ')');
        array_push($conditions, 'AND');
        array_push($conditions, '(attribute_id=' .$attribute['id'] . ')');
        $conditions = implode(' ', $conditions);
        
        $settings = array(
            'table' => $table, 
            'fields' => array(
                'counts' => 'count(0)'
                ),
            'conditions' => $conditions
        );
        $db = $this->getDbByTable($table, $ext);
        return !!$this->getVar($db, $settings);
    }
    
    
    /**
     * 插入模型的一个扩展属性。
     * @param $code {String} 模型的编码。
     * @param $modelId {Int} 模型数据的ID。
     * @param $table {String} 模型扩展属性存储的数据表。
     * @param $attribute {Array} 模型扩展属性数据。
     * 格式：
     * array(
     *       'id' => '',
     *       'name' => '',
     *       'comment' => '',
     *       'type' => '',
     *       'default' => '',
     *       'ext' => '',
     *       'editable' => '',
     *       'autoupdate' => '',
     *       'primary' => '',
     *       'position' => '',
     *       'value' => ''
     * )
     * @param $ext {Array} 数据库切割需要的扩展参数。
	 * @return Int 新增的ID。
     */
    private function attribute_insert($code, $modelId, $table, $attribute, $ext = array()){
        $settings = array(
            'table' => $table, 
            'fields' => array(
                'model_code' => array('value' => $code, 'usequot' => true),
                'model_id' => array('value' => $modelId, 'usequot' => false),
                'attribute_id' => array('value' => $attribute['id'], 'usequot' => false),
                'str_vlaue' => array('value' => $attribute['value'], 'usequot' => true),
                'update_time' => array('value' => time(), 'usequot' => false),
                'create_time' => array('value' => time(), 'usequot' => false)
                )
            );
        $db = $this->getDbByTable($table, $ext);
        return $this->insert($db, $settings);
    }
    
    
    /**
     * 修改模型的一个扩展属性。
     * @param $code {String} 模型的编码。
     * @param $modelId {Int} 模型数据的ID。
     * @param $table {String} 模型扩展属性存储的数据表。
     * @param $attribute {Array} 模型扩展属性数据。
     * 格式：
     * array(
     *       'id' => '',
     *       'name' => '',
     *       'comment' => '',
     *       'type' => '',
     *       'default' => '',
     *       'ext' => '',
     *       'editable' => '',
     *       'autoupdate' => '',
     *       'primary' => '',
     *       'position' => '',
     *       'value' => ''
     * )
     * @param $ext {Array} 数据库切割需要的扩展参数。
	 * @return Boolean 是否操作成功。
     */
    private function attribute_update($code, $modelId, $table, $attribute, $ext = array()){
        $conditions = array();
        array_push($conditions, '(model_code=\'' . $code . '\')');
        array_push($conditions, 'AND');
        array_push($conditions, '(model_id=' . $modelId . ')');
        array_push($conditions, 'AND');
        array_push($conditions, '(attribute_id=' .$attribute['id'] . ')');
        $conditions = implode(' ', $conditions);
        $settings = array(
            'table' => $table, 
            'fields' => array(
                'str_vlaue' => array('value' => $attribute['value'], 'usequot' => true),
                'update_time' => array('value' => time(), 'usequot' => false)
            ),
            'conditions' => $conditions
        );
        $db = $this->getDbByTable($table, $ext);
        $this->update($db, $settings);
        return true;
    }
    
    /**
     * 获取检索列表的检索配置数据。
     * @param $conditions {String} 检索条件。
     * @return {mixed} 检索列表的检索配置数据。
     */
    private function getListSettings($conditions){
        $settings = array(
            'table' => $this->table, 
            'fields' => array(
                'id' => 'attribute_id',
                'name' => 'attribute_name',
                'comment' => 'attribute_comment',
                'type' => 'value_type',
                'default' => 'default_value',
                'list' => 'list_id',
                'ext' => 'is_ext',
                'editable' => 'is_editable',
                'autoupdate' => 'is_autoupdate',
                'primary' => 'is_primary',
                'position' => 'position_order'
                ),
            'conditions' => $conditions
        );
        return $settings;
    }
    /*- 私有方法 END -*/
}
?>