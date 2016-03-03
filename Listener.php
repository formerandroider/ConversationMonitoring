<?php

class LiamW_ConversationMonitoring_Listener
{
	protected static $_conversationModel = null;

	public static function initDependencies(XenForo_Dependencies_Abstract $dependencies, array $data)
	{
		if (!($dependencies instanceof XenForo_Dependencies_Public))
		{
			return;
		}

		$registryModel = new XenForo_Model_DataRegistry(); // Deliberate
		$convoCounts = $registryModel->get('convoMonitoringCounts');

		if ($convoCounts)
		{
			XenForo_Application::set('convoMonitoringCounts', $convoCounts);
		}
	}

	public static function controllerPreDispatch(XenForo_Controller $controller, $action, $controllerName)
	{
		if (!($controller instanceof XenForo_ControllerPublic_Abstract))
		{
			return;
		}

		self::_rebuildConversationMonitoringCounts();
	}

	public static function extendConversationController($class, array &$extend)
	{
		$extend[] = 'LiamW_ConversationMonitoring_Extend_ControllerPublic_Conversation';
	}

	public static function extendConversationInlineModController($class, array &$extend)
	{
		$extend[] = 'LiamW_ConversationMonitoring_Extend_ControllerPublic_InlineMod_Conversation';
	}

	public static function extendConversationMasterDataWriter($class, array &$extend)
	{
		$extend[] = 'LiamW_ConversationMonitoring_Extend_DataWriter_ConversationMaster';
	}

	public static function extendConversationModel($class, array &$extend)
	{
		$extend[] = 'LiamW_ConversationMonitoring_Extend_Model_Conversation';
	}

	public static function extendConversationInlineModModel($class, array &$extend)
	{
		$extend[] = 'LiamW_ConversationMonitoring_Extend_Model_InlineMod_Conversation';
	}

	protected static function _rebuildConversationMonitoringCounts()
	{
		if (XenForo_Application::isRegistered('convoMonitoringCounts'))
		{
			$convoCounts = XenForo_Application::get('convoMonitoringCounts');
		}
		else
		{
			$convoCounts = self::_getConversationModel()->rebuildConvoMonitoringCountCache();
		}

		$session = XenForo_Application::getSession();
		$sessionConvoCounts = $session->get('convoMonitoringCounts');

		if (!is_array($sessionConvoCounts) || $sessionConvoCounts['lastBuildDate'] < $convoCounts['lastModifiedDate'])
		{
			$sessionConvoCounts = array(
				'toReview' => $convoCounts['toReview']
			);

			$sessionConvoCounts['lastBuildDate'] = XenForo_Application::$time;
			$session->set('convoMonitoringCounts', $sessionConvoCounts);
		}
	}

	/**
	 * @return LiamW_ConversationMonitoring_Extend_Model_Conversation
	 */
	protected static function _getConversationModel()
	{
		if (!self::$_conversationModel)
		{
			self::$_conversationModel = XenForo_Model::create('XenForo_Model_Conversation');
		}

		return self::$_conversationModel;
	}
}