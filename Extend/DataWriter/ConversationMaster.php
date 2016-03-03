<?php

class LiamW_ConversationMonitoring_Extend_DataWriter_ConversationMaster extends XFCP_LiamW_ConversationMonitoring_Extend_DataWriter_ConversationMaster
{
	public function _getFields()
	{
		$newFields = array(
			'xf_conversation_master' => array(
				'staff_reviewed' => array(
					'type' => self::TYPE_BOOLEAN,
					'default' => 0
				)
			)
		);

		return XenForo_Application::mapMerge(parent::_getFields(), $newFields);
	}

	public function addRecipientUserNames(array $usernames)
	{
		parent::addRecipientUserNames($usernames);

		$permUser = $this->getExtraData(self::DATA_ACTION_USER);
		if (!$permUser || $permUser == XenForo_Visitor::getUserId())
		{
			$permUser = null;
			$permUserId = XenForo_Visitor::getUserId();
		}
		else
		{
			$permUserId = $permUser['user_id'];
		}

		$permUserId = null;

		$users = $this->_getUserModel()->getUsersByNames(
			$usernames,
			array(
				'join' => XenForo_Model_User::FETCH_USER_PRIVACY | XenForo_Model_User::FETCH_USER_OPTION
					| XenForo_Model_User::FETCH_USER_PERMISSIONS,
				'followingUserId' => $permUserId
			),
			$notFound
		);

		if ($notFound)
		{
			$this->error(new XenForo_Phrase('the_following_recipients_could_not_be_found_x',
				array('names' => implode(', ', $notFound))), 'recipients');
		}
		else
		{
			$conversationModel = $this->_getConversationModel();
			$noStart = array();
			foreach ($users AS $key => $user)
			{
				$visitor = XenForo_Visitor::getInstance();

				if (($permUserId == $user['user_id'] && $visitor->getUserId() != $permUserId && !$visitor->hasPermission('conversation',
							'liam_convoMonitor_allow')) || in_array($user['user_id'], $this->_newRecipients)
				)
				{
					// skip trying to add self
					unset($users[$key]);
					continue;
				}

				if (($visitor->getUserId() != $permUserId && !$visitor->hasPermission('conversation',
							'liam_convoMonitor_allow')) && !$conversationModel->canStartConversationWithUser($user, $null, $permUser))
				{
					$noStart[] = $user['username'];
				}
			}

			if (!$users)
			{
				return;
			}

			if ($noStart)
			{
				$this->error(new XenForo_Phrase('you_may_not_start_a_conversation_with_the_following_recipients_x',
					array('names' => implode(', ', $noStart))), 'recipients');
			}
			else
			{
				$this->_newRecipients = array_merge($this->_newRecipients, array_keys($users));

				$remaining = $conversationModel->allowedAdditionalConversationRecipients($this->getMergedExistingData(),
					$permUser);
				if ($remaining > -1 && count($this->_newRecipients) > $remaining)
				{
					$this->error(new XenForo_Phrase('you_may_only_invite_x_members_to_join_this_conversation',
						array('count' => $remaining)), 'recipients');
				}
			}
		}
	}

	protected function _postSaveAfterTransaction()
	{
		parent::_postSaveAfterTransaction();

		$this->_getConversationModel()->rebuildConvoMonitoringCountCache();
	}

	protected function _postDelete()
	{
		parent::_postDelete();

		$this->_getConversationModel()->rebuildConvoMonitoringCountCache();
	}
}

if (false)
{
	class XFCP_LiamW_ConversationMonitoring_Extend_DataWriter_ConversationMaster extends XenForo_DataWriter_ConversationMaster
	{
	}
}