<?php

class LiamW_ConversationMonitoring_Installer
{
	protected static $_tables = array();

	protected static $_coreAlters = array(
		'xf_conversation_master' => array(
			'staff_reviewed' => 'ALTER TABLE xf_conversation_master ADD staff_reviewed TINYINT(1) UNSIGNED NOT NULL DEFAULT 0'
		)
	);

	protected static function _canBeInstalled(&$error)
	{
		if (XenForo_Application::$versionId < 1020070)
		{
			$error = 'XenForo 1.2.0+ is Required!';

			return false;
		}

		$hashErrors = XenForo_Helper_Hash::compareHashes(LiamW_ConversationMonitoring_FileSums::getHashes());

		if ($hashErrors)
		{
			$error = "The following files could not be found or contain unexpected content: <ul>";

			foreach ($hashErrors AS $file => $fileError)
			{
				$error .= "<li>$file - " . ($fileError == 'mismatch' ? 'File contains unexpected content' : 'File not found') . "</li>";
			}

			$error .= "</ul>";

			return false;
		}

		return true;
	}

	public static function install($installedAddon)
	{
		if (!self::_canBeInstalled($error))
		{
			throw new XenForo_Exception($error, true);
		}

		self::_installTables();
		self::_installCoreAlters();
	}

	public static function uninstall()
	{
		self::_uninstallTables();
		self::_uninstallCoreAlters();
	}

	protected static function _installTables(\Zend_Db_Adapter_Abstract $db = null)
	{
		foreach (self::$_tables AS $tableName => $installSql)
		{
			self::_runQuery($installSql, $db);
		}
	}

	protected static function _uninstallTables(\Zend_Db_Adapter_Abstract $db = null)
	{
		foreach (self::$_tables AS $tableName => $installSql)
		{
			self::_runQuery("DROP TABLE $tableName", $db);
		}
	}

	protected static function _installCoreAlters(\Zend_Db_Adapter_Abstract $db = null)
	{
		foreach (self::$_coreAlters AS $tableName => $coreAlters)
		{
			foreach ($coreAlters AS $columnName => $installSql)
			{
				self::_runQuery($installSql, $db);
			}
		}
	}

	protected static function _uninstallCoreAlters(\Zend_Db_Adapter_Abstract $db = null)
	{
		foreach (self::$_coreAlters AS $tableName => $coreAlters)
		{
			foreach ($coreAlters AS $columnName => $installSql)
			{
				self::_runQuery("ALTER TABLE $tableName DROP $columnName", $db);
			}
		}
	}

	protected static function _runQuery($sql, \Zend_Db_Adapter_Abstract $db = null)
	{
		if ($db == null)
		{
			$db = XenForo_Application::getDb();
		}

		try
		{
			$db->query($sql);
		} catch (\Zend_Db_Exception $e)
		{

		}
	}
}