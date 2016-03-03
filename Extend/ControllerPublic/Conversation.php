<?php

class LiamW_ConversationMonitoring_Extend_ControllerPublic_Conversation extends XFCP_LiamW_ConversationMonitoring_Extend_ControllerPublic_Conversation
{
	public function actionMonitoring()
	{
		$this->_assertCanMonitorConversations();

		$includeReviewed = $this->_input->filterSingle('include_reviewed', XenForo_Input::BOOLEAN);
		$extraConditions = array();
		$extraConditions['is_staff_reviewed'] = $includeReviewed ? null : 0;

		if (!($userId = $this->_input->filterSingle('user_id', XenForo_Input::UINT)))
		{
			$userId = null;
		}
		else
		{
			$extraConditions['is_staff_reviewed'] = null;
		}

		$viewParams = $this->_getConversationMonitoringListData($userId, $extraConditions);

		$this->canonicalizePageNumber(
			$viewParams['page'], $viewParams['conversationsPerPage'], $viewParams['totalConversations'],
			'conversations'
		);

		return $this->responseView('XenForo_ViewPublic_Conversation_List', 'conversation_list', $viewParams);
	}

	public function actionMarkReviewed()
	{
		$this->_assertCanMonitorConversations();

		$conversationId = $this->_input->filterSingle('conversation_id', XenForo_Input::UINT);
		$conversation = $this->_getConversationOrError($conversationId);

		$dw = XenForo_DataWriter::create('XenForo_DataWriter_ConversationMaster');
		$dw->setExistingData($conversation);
		$dw->set('staff_reviewed', 1);
		$dw->save();

		return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildPublicLink('conversations/monitoring'));
	}

	protected function _getConversationMonitoringListData($userId = null, array $extraConditions = array())
	{
		/** @var LiamW_ConversationMonitoring_Extend_Model_Conversation $conversationModel */
		$conversationModel = $this->_getConversationModel();

		$conditions = $this->_getListConditions();
		$originalConditions = $conditions;
		$conditions = array_merge($conditions, $extraConditions);
		$fetchOptions = $this->_getListFetchOptions();

		if ($userId)
		{
			$totalConversations = $conversationModel->countConversationsForUser($userId, $conditions);
			$conversations = $conversationModel->getConversationsForUser($userId, $conditions, $fetchOptions);
		}
		else
		{
			$totalConversations = $conversationModel->countAllConversations($conditions);
			$conversations = $conversationModel->getAllConversations($conditions, $fetchOptions);
		}
		$conversations = $conversationModel->prepareConversations($conversations);

		$page = max(1, intval($fetchOptions['page']));

		return array(
			'title' => new XenForo_Phrase('liam_conversationMonitoring_title'),
			'pageRoute' => 'conversations/monitoring',

			'userId' => $userId,

			'conversations' => $conversations,

			'page' => $fetchOptions['page'],
			'conversationsPerPage' => $fetchOptions['perPage'],
			'totalConversations' => $totalConversations,
			'startOffset' => ($page - 1) * $fetchOptions['perPage'] + 1,
			'endOffset' => ($page - 1) * $fetchOptions['perPage'] + count($conversations),

			'ignoredNames' => $this->_getIgnoredContentUserNames($conversations),

			'canStartConversation' => false,
			// no starting from the review page
			'canMarkReviewed' => true,

			'search_type' => $conditions['search_type'],
			'search_user' => $conditions['search_user'],
			'include_reviewed' => $conditions['is_staff_reviewed'] === null,

			'pageNavParams' => array(
				'search_type' => ($originalConditions['search_type'] ? $originalConditions['search_type'] : false),
				'search_user' => ($originalConditions['search_user'] ? $originalConditions['search_user'] : false),
				'include_reviewed' => ($conditions['is_staff_reviewed'] === null ? 1 : false),
				'user_id' => $userId ? $userId : false
			),
		);
	}

	protected function _getConversationOrError($conversationId, $userId = null, $fetchFirstMessage = false)
	{
		try
		{
			$conversation = parent::_getConversationOrError($conversationId, $userId,
				$fetchFirstMessage);
		} catch (XenForo_ControllerResponse_Exception $e)
		{
			$conversation = false;
		}

		if (!$conversation && $this->_canMonitorConversations())
		{
			/** @var LiamW_ConversationMonitoring_Extend_Model_Conversation $conversationModel */
			$conversationModel = $this->_getConversationModel();

			$fetchOptions = array();
			if ($fetchFirstMessage)
			{
				$fetchOptions['join'] = XenForo_Model_Conversation::FETCH_FIRST_MESSAGE;
			}
			$fetchOptions['draftUserId'] = XenForo_Visitor::getUserId();

			$conversation = $conversationModel->getConversationForOnlooker($conversationId, $fetchOptions);
			if (!$conversation)
			{
				throw $this->responseException($this->responseError(new XenForo_Phrase('requested_conversation_not_found'),
					404));
			}

			$conversation = $conversationModel->prepareConversation($conversation);
		}

		return $conversation;
	}

	public function actionView()
	{
		$response = parent::actionView();

		if ($response instanceof XenForo_ControllerResponse_View)
		{
			$response->params['canMarkReviewed'] = $this->_canMonitorConversations() && !$response->params['conversation']['staff_reviewed'];
		}

		return $response;
	}

	protected function _canMonitorConversations()
	{
		return XenForo_Visitor::getInstance()->hasPermission('conversation', 'liam_convoMonitor_allow');
	}

	protected function _assertCanMonitorConversations()
	{
		if (!$this->_canMonitorConversations())
		{
			throw $this->getNoPermissionResponseException();
		}
	}
}

if (false)
{
	class XFCP_LiamW_ConversationMonitoring_Extend_ControllerPublic_Conversation extends XenForo_ControllerPublic_Conversation
	{
	}
}
