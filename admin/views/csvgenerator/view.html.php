<?php defined('_JEXEC') or die;
/**
 * @package     Joomla.Administrator
 * @subpackage  com_csvgenerator
 */

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;

class CSVGeneratorViewCSVGenerator extends HtmlView
{
    public function display($tpl = null)
    {
        ToolBarHelper::title(Text::_('COM_CSVGENERATOR'));
        if (ContentHelper::getActions('com_csvgenerator')->get('core.admin')) {
            ToolbarHelper::preferences('com_csvgenerator');
        } else {
            Factory::getDocument()->addStyleDeclaration('#isisJsData{height:0!important;overflow:hidden!important;}');
        }
        parent::display($tpl);
    }
}
