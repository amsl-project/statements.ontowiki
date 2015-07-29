<?php

require_once realpath(dirname(__FILE__)) . '/classes/ArticleIndexHelper.php';

/**
 * This file is part of the {@link http://amsl.technology amsl} project.
 *
 * @author Sebastian Nuck
 * @copyright Copyright (c) 2015, {@link http://ub.uni-leipzig.de Leipzig University Library}
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */

/**
 * Statments component controller.
 *
 * @category   OntoWiki
 * @package    Extensions_Statements
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
        $this->view->membership = (isset($membership) && $membership !== "") ? $membership : "The current user has no membership";

    }

    /**
     * Retrieves the metadata sources from the virtuoso (is called via javascript).
     * @return response
     */
    public function metadatasourcesAction() {
        // tells the OntoWiki to not apply the template to this action
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $dicoveryIndex = $this->_privateConfig->statements->discoveryIndex;
        $articleIndex = new ArticleIndexHelper($dicoveryIndex);
        $models = $articleIndex->getMetadataSources();

        $this->_response->setBody(json_encode($models));
    }

    /**
     * Saves one collection after the checkbox has been checked while it was unchecked before.
     */
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

        $discoveryIndexUri = $this->_privateConfig->statements->discoveryIndex;
        $articleIndex = new ArticleIndexHelper($discoveryIndexUri);

        if(isset($membership)) {
            if ($_owApp->erfurt->getAc()->isModelAllowed('edit', $discoveryIndexUri) === true) {
                $return = $articleIndex->saveStatement($collection, $membership);
                $this->_response->setBody(json_encode($return));
                return;
            } else {
                $msg = 'StatementsController:deletestatementAction: not allowed to edit ';
                $msg.=  $discoveryIndexUri . '. Statement not saved.';
                $logger->debug($msg);
            }
        }
        $this->_response->setHeader('HTTP/1.1 403 Forbidden');
    }

    /**
     * Saves one collection after the checkbox has been unchecked while it was checked before.
     */
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

        $discoveryIndexUri = $this->_privateConfig->statements->discoveryIndex;
        $articleIndex = new ArticleIndexHelper($discoveryIndexUri);

        if(isset($membership)) {
            if ($_owApp->erfurt->getAc()->isModelAllowed('edit', $discoveryIndexUri) === true) {
                $return = $articleIndex->deleteStatement($collection, $membership);
                $this->_response->setBody(json_encode($return));
                return;
            } else {
                $msg = 'StatementsController:deletestatementAction: not allowed to edit ';
                $msg.=  $discoveryIndexUri . '. Statement not deleted.';
                $logger->debug($msg);
            }
        }
        $this->_response->setHeader('HTTP/1.1 403 Forbidden');
    }


}
