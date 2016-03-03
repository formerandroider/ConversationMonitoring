<?php

class LiamW_ConversationMonitoring_Extend_Model_Conversation extends XFCP_LiamW_ConversationMonitoring_Extend_Model_Conversation
{
	public function getAllConversations(array $conditions = array(), array $fetchOptions = array())
	{
		$whereClause = $this->prepareConversationConditions($conditions, $fetchOptions);
		$sqlClauses = $this->prepareConversationFetchOptions($fetchOptions);

		$limitOptions = $this->prepareLimitFetchOptions($fetchOptions);

		$sql = $this->limitQueryResults(
			'
				SELECT conversation_master.*,
					conversation_user.*,
					conversation_starter.*,
					conversation_master.username AS username,
					conversation_recipient.recipient_state, conversation_recipient.last_read_date
					' . $sqlClauses['selectFields'] . '
				FROM xf_conversation_user AS conversation_user
				INNER JOIN xf_conversation_master AS conversation_master ON
					(conversation_user.conversation_id = conversation_master.conversation_id)
				INNER JOIN xf_conversation_recipient AS conversation_recipient ON
					(conversation_user.conversation_id = conversation_recipient.conversation_id
					AND conversation_user.owner_user_id = conversation_recipient.user_id)
				LEFT JOIN xf_user AS conversation_starter ON
					(conversation_starter.user_id = conversation_master.user_id)
					' . $sqlClauses['joinTables'] . '
					WHERE ' . $whereClause . '
				ORDER BY conversation_user.last_message_date DESC
			', $limitOptions['limit'], $limitOptions['offset']
		);

		return $this->fetchAllKeyed($sql, 'conversation_id');
	}

	public function getConversationForOnlooker($conversationId, array $fetchOptions = array())
	{
		$joinOptions = $this->prepareConversationFetchOptions($fetchOptions);

		return $this->_getDb()->fetchRow('
			SELECT conversation_master.*,
				conversation_user.*,
				conversation_starter.*,
				conversation_master.username AS username,
				conversation_recipient.recipient_state, conversation_recipient.last_read_date
				' . $joinOptions['selectFields'] . '
			FROM xf_conversation_user AS conversation_user
			INNER JOIN xf_conversation_master AS conversation_master ON
				(conversation_user.conversation_id = conversation_master.conversation_id)
			INNER JOIN xf_conversation_recipient AS conversation_recipient ON
				(conversation_user.conversation_id = conversation_recipient.conversation_id
				AND conversation_user.owner_user_id = conversation_recipient.user_id)
			LEFT JOIN xf_user AS conversation_starter ON
				(conversation_starter.user_id = conversation_master.user_id)
				' . $joinOptions['joinTables'] . '
			WHERE conversation_user.conversation_id = ?
		', array($conversationId));
	}

	public function countAllConversations(array $conditions = array())
	{
		$fetchOptions = array();
		$whereClause = $this->prepareConversationConditions($conditions, $fetchOptions);
		$sqlClauses = $this->prepareConversationFetchOptions($fetchOptions);

		return $this->_getDb()->fetchOne('
			SELECT COUNT(*)
			FROM xf_conversation_user AS conversation_user
			INNER JOIN xf_conversation_master AS conversation_master ON
				(conversation_user.conversation_id = conversation_master.conversation_id)
			INNER JOIN xf_conversation_recipient AS conversation_recipient ON
				(conversation_user.conversation_id = conversation_recipient.conversation_id
				AND conversation_user.owner_user_id = conversation_recipient.user_id)
				' . $sqlClauses['joinTables'] . '
				AND ' . $whereClause
		);
	}

	public function prepareConversationConditions(array $conditions, array &$fetchOptions)
	{
		$sqlConditions = array();
		$db = $this->_getDb();

		if (isset($conditions['is_staff_reviewed']) && !is_null($conditions['is_staff_reviewed']))
		{
			$sqlConditions[] = 'conversation_master.staff_reviewed = ' . ($conditions['is_staff_reviewed'] ? 1 : 0);
		}

		return parent::prepareConversationConditions($conditions,
			$fetchOptions) . ' AND ' . $this->getConditionsForClause($sqlConditions);
	}

	public function markConversationsReviewed(array $conversationIds)
	{
		$db = $this->_getDb();

		XenForo_Db::beginTransaction($db);

		$db->update('xf_conversation_master', array(
			'staff_reviewed' => 1
		), 'conversation_id IN (' . $db->quote($conversationIds) . ')');

		$this->rebuildConvoMonitoringCountCache();

		XenForo_Db::commit($db);
	}

	public function rebuildConvoMonitoringCountCache()
	{
		$toReview = $this->countAllConversations(array(
			'is_staff_reviewed' => false
		));

		$cache = array(
			'toReview' => $toReview,
			'lastModifiedDate' => XenForo_Application::$time
		);

		$this->_getDataRegistryModel()->set('convoMonitoringCounts', $cache);

		return $cache;
	}

	public function canReplyToConversation(array $conversation, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		$canReply = parent::canReplyToConversation($conversation, $errorPhraseKey, $viewingUser);

		if ($canReply && $conversation['user_id'] != $viewingUser['user_id'])
		{
			$recipientIds = array_keys($conversation['recipientNames']);

			$canReply = in_array($viewingUser['user_id'], $recipientIds);
		}

		return $canReply;
	}
}

if (false)
{
	class XFCP_LiamW_ConversationMonitoring_Extend_Model_Conversation extends XenForo_Model_Conversation
	{
	}
}