<?php

class LiamW_ConversationMonitoring_FileSums
{
	public static function addHashes(XenForo_ControllerAdmin_Abstract $controller, array &$hashes)
	{
		$hashes += self::getHashes();
	}

	/**
	 * @return array
	 */
	public static function getHashes()
	{
		return array(
			'library/LiamW/ConversationMonitoring/Extend/ControllerPublic/Conversation.php' => '63d713a0afaa7a2b5a0392f33e87bc0d',
			'library/LiamW/ConversationMonitoring/Extend/ControllerPublic/InlineMod/Conversation.php' => '56c71e7c838525e7667ea0470687925a',
			'library/LiamW/ConversationMonitoring/Extend/ControllerPublic/Member.php' => '95587d9945611d0c270188433802e9b3',
			'library/LiamW/ConversationMonitoring/Extend/DataWriter/ConversationMaster.php' => '39918e6e24dba2ee41eea4edb5703143',
			'library/LiamW/ConversationMonitoring/Extend/Model/Conversation.php' => '7a43342e829e48dbd73aad2013bafc35',
			'library/LiamW/ConversationMonitoring/Extend/Model/InlineMod/Conversation.php' => 'fdb191e0782d26283d75368f1182abb2',
			'library/LiamW/ConversationMonitoring/Installer.php' => 'addfefabeebc3a972d5b157371b4f26d',
			'library/LiamW/ConversationMonitoring/Listener.php' => '73b2e1671d86ac01476baee9632147a3',
		);
	}
}