<?php

require_once realpath(dirname(__FILE__)) . '/classes/ArticleIndexHelper.php';

/**
 * Statments component controller.
 *
 * @category   OntoWiki
 * @package    Extensions_Statements
 * @author     Sebastian Nuck
 * @copyright  Copyright (c) 2015, {@link http://ub.uni-leipzig.de UB Leipzig}
 * @license    http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */
class StatementsController extends OntoWiki_Controller_Component
{

    /**
     * Displays an information page.
     * @return [type]
     */
    public function collectiontolibraryAction()
    {
        $_owApp = OntoWiki::getInstance();
        $logger = $_owApp->getCustomLogger('statements');
        $translate = $this->_owApp->translate;
        $this->view->placeholder('main.window.title')->set($translate->_('Statements'));
        $this->addModuleContext('main.window.fulltextsearch.info');
        $_owApp->getNavigation()->disableNavigation();

        $membership = $_owApp->getUser()->getIsMemberOf();
        $this->view->membership = isset($membership) ? $membership : "The current user has no membership";

    }

    public function metadatasourcesAction() {
        // tells the OntoWiki to not apply the template to this action
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $articleIndex = new ArticleIndexHelper('http://localhost/OntoWiki/Discovery/');
        $models = $articleIndex->getMetadataSources();

        $this->_response->setBody(json_encode($models));
    }

    public function savearticleindexstatementAction() {
        // tells the OntoWiki to not apply the template to this action
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $params = $this->_request->getParams();
        $collection = $params['collection'];
        $source = $params['source'];

        $_owApp = OntoWiki::getInstance();
        $logger = $_owApp->getCustomLogger('statements');
        $logger->debug('StatementsController:savestatementAction: ' . $collection . ' --> ' . $source);


        $membership = $_owApp->getUser()->getIsMemberOf();

        $articleIndex = new ArticleIndexHelper('http://localhost/OntoWiki/Discovery/');

        $return = null;
        if(isset($membership)) {
            $return = $articleIndex->saveStatement($collection, $membership);
        }

        $this->_response->setBody(json_encode($return));
    }

    public function deletearticleindexstatementAction() {
        // tells the OntoWiki to not apply the template to this action
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $params = $this->_request->getParams();
        $collection = $params['collection'];
        $source = $params['source'];

        $_owApp = OntoWiki::getInstance();
        $logger = $_owApp->getCustomLogger('statements');
        $logger->debug('StatementsController:deletestatementAction: ' . $collection . ' --> ' . $source);

        $membership = $_owApp->getUser()->getIsMemberOf();

        $return = null;
        $articleIndex = new ArticleIndexHelper('http://localhost/OntoWiki/Discovery/');
        if(isset($membership)) {
            $return = $articleIndex->deleteStatement($collection, $membership);
        }

        $this->_response->setBody(json_encode($return));
    }


}
