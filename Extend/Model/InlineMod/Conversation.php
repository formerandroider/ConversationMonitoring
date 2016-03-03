<?php

class LiamW_ConversationMonitoring_Extend_Model_InlineMod_Conversation extends XFCP_LiamW_ConversationMonitoring_Extend_Model_InlineMod_Conversation
{
	public function markConversationsReviewed(array $conversationIds, array $options = array(), &$errorKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		if (!XenForo_Permission::hasPermission($viewingUser['permissions'], 'conversation', 'liam_convoMonitor_allow'))
		{
			return false;
		}

		$this->_getConversationModel()->markConversationsReviewed($conversationIds);

		return true;
	}
}

if (false)
{
	class XFCP_LiamW_ConversationMonitoring_Extend_Model_InlineMod_Conversation extends XenForo_Model_InlineMod_Conversation
	{
	}
}
