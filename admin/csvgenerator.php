<?php defined('_JEXEC') or die;
/**
 * @package     Joomla.Administrator
 * @subpackage  com_csvgenerator
 */

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;

$controller = BaseController::getInstance('csvgenerator');
$controller->execute(Factory::getApplication()->input->get('task'));
$controller->redirect();
