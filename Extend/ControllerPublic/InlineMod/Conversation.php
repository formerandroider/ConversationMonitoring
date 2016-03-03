<?php

class LiamW_ConversationMonitoring_Extend_ControllerPublic_InlineMod_Conversation extends XFCP_LiamW_ConversationMonitoring_Extend_ControllerPublic_InlineMod_Conversation
{
	public function actionMarkReviewed()
	{
		if (!XenForo_Visitor::getInstance()->hasPermission('conversation', 'liam_convoMonitor_allow'))
		{
			return $this->responseNoPermission();
		}

		if ($this->isConfirmedPost())
		{
			return $this->executeInlineModAction('markConversationsReviewed', array(), array('fromCookie' => false));
		}
		else // show confirmation dialog
		{
			$conversationIds = $this->getInlineModIds();

			$redirect = $this->getDynamicRedirect();

			if (!$conversationIds)
			{
				return $this->responseRedirect(
					XenForo_ControllerResponse_Redirect::SUCCESS,
					$redirect
				);
			}

			$viewParams = array(
				'conversationIds' => $conversationIds,
				'conversationCount' => count($conversationIds),
				'redirect' => $redirect,
			);

			return $this->responseView('XenForo_ViewPublic_InlineMod_Conversation_Leave', 'liam_conversationMonitoring_inline_mod_reviewed', $viewParams);
		}
	}
}

if (false)
{
	class XFCP_LiamW_ConversationMonitoring_Extend_ControllerPublic_InlineMod_Conversation extends XenForo_ControllerPublic_InlineMod_Conversation
	{
	}
}
