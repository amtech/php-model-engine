<?php
require_once(ROOT . 'inc/core/security/principal/principal.inc.php');
require_once(ROOT . 'inc/core/security/principal/simpleidentity.class.php');

/**
* 简易的上下文用户对象。
*/
class SimplePrincipal implements IPrincipal{
	/**
	* 获取当前用户的权限列表。
	*/
	public function getPermissions(){
		return array();
	}
	
	/**
	* 获取当前用户的标识。
	*/
	public function getIdentity(){
		return new SimpleIdentity();
	}

	/**
	* 确定当前用户是否属于指定的角色。
	* @param $role {String} 指定的角度。
	* @return {Boolean} 当前用户是否属于指定的角色。
	*/
	public function isInRole($role){
		return false;
	}
}
?>