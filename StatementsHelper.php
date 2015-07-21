<?php

/**
 * This file is part of the {@link http://amsl.technology amsl} project.
 *
 * @author Sebastian Nuck
 * @copyright Copyright (c) 2015, {@link http://ub.uni-leipzig.de Leipzig University Library}
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */

/**
 * Helper class for the Statements component.
 *
 * @category OntoWiki
 * @package Extensions_Fulltextsearch
 * @copyright Copyright (c) 2012, {@link http://aksw.org AKSW}
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */
class StatementsHelper extends OntoWiki_Component_Helper
{
    
    /**
     * The module view
     *
     * @var Zend_View_Interface
     */
    public $view = null;
    
    public function init() {
        
        $owApp = OntoWiki::getInstance();
        
        // init view
        if (null === $this->view) {
            $viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');
            if (null === $viewRenderer->view) {
                $viewRenderer->initView();
            }
            $this->view = clone $viewRenderer->view;
            $this->view->clearVars();
        }
        
        if ($owApp->erfurt->isActionAllowed('Debug')) {
            $extrasMenu = OntoWiki_Menu_Registry::getInstance()->getMenu('application')->getSubMenu('Extras');
            $extrasMenu->setEntry('Select collections', $owApp->config->urlBase . 'statements/collectiontolibrary');
        }
        
        $this->view->headScript()->appendFile($this->_config->urlBase . 'extensions/statements/templates/statements/js/statements.js');
        $this->view->headScript()->appendFile($this->_config->urlBase . 'extensions/statements/templates/statements/js/handlebars.js');
        $this->view->headScript()->appendFile($this->_config->urlBase . 'extensions/statements/templates/statements/js/jquery.fancytree-all.min.js');

        $this->view->headLink()->appendStylesheet($this->_config->urlBase . 'extensions/statements/templates/statements/css/statements.css');
        $this->view->headLink()->appendStylesheet($this->_config->urlBase . 'extensions/statements/templates/statements/css/ui.fancytree.css');
    }
}

