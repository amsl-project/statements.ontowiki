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
        $this->view->placeholder('main.window.title')->set($translate->_('Select Metadata Collections'));
        $this->addModuleContext('main.window.fulltextsearch.info');
        $_owApp->getNavigation()->disableNavigation();

        $dicoveryIndex = $this->_privateConfig->statements->discoveryIndex;
        $articleIndex = new ArticleIndexHelper($dicoveryIndex);

        $membership = $_owApp->getUser()->getIsMemberOf();
        $this->view->label = $articleIndex->getOrganisationLabel($membership);
        $this->view->membership = (isset($membership) && $membership !== "") ? $membership : $translate->_("The current user has no membership");

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

    public function checkallAction(){
        $source = $this->_request->getParam("source");
        $discoveryIndex = $this->_privateConfig->statements->discoveryIndex;
        $articleIndex = new ArticleIndexHelper($discoveryIndex);
        $metadataCollections = $articleIndex->queryMetadataCollections($source);
        $_owApp = OntoWiki::getInstance();
        $membership = $_owApp->getUser()->getIsMemberOf();

        $stmt = array();
        foreach($metadataCollections as $collection){
            if(($collection['usedBy'] == null && $collection['isRestricted'] == 'http://vocab.ub.uni-leipzig.de/amsl/No') || ($collection['isRestricted'] == 'http://vocab.ub.uni-leipzig.de/amsl/Yes' && $collection['permittedForLibrary'] == $membership)) {
                $stmt[$collection['collection']]['http://vocab.ub.uni-leipzig.de/amsl/metadataUsedByLibrary'][] = array('value' => $membership, 'type' => 'uri');
            }
        }
        $store = $this->_erfurt->getStore();
        $store->addMultipleStatements($discoveryIndex, $stmt);

        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();
    }

    public function uncheckallAction(){
        $source = $this->_request->getParam("source");
        $discoveryIndex = $this->_privateConfig->statements->discoveryIndex;
        $articleIndex = new ArticleIndexHelper($discoveryIndex);
        $metadataCollections = $articleIndex->queryMetadataCollections($source);
        $_owApp = OntoWiki::getInstance();
        $membership = $_owApp->getUser()->getIsMemberOf();

        $stmt = array();
        foreach($metadataCollections as $collection){
            if(($collection['usedBy'] != null && $collection['isRestricted'] == 'http://vocab.ub.uni-leipzig.de/amsl/No') || ($collection['isRestricted'] == 'http://vocab.ub.uni-leipzig.de/amsl/Yes' && $collection['permittedForLibrary'] == $membership)) {
                $stmt[$collection['collection']]['http://vocab.ub.uni-leipzig.de/amsl/metadataUsedByLibrary'][] = array('value' => $membership, 'type' => 'uri');
            }
        }
        $store = $this->_erfurt->getStore();
        $store->deleteMultipleStatements($discoveryIndex, $stmt);

        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();
    }

    public function evaluateallAction(){
        $source = $this->_request->getParam("source");
        $discoveryIndex = $this->_privateConfig->statements->discoveryIndex;
        $articleIndex = new ArticleIndexHelper($discoveryIndex);
        $metadataCollections = $articleIndex->queryMetadataCollections($source);
        $_owApp = OntoWiki::getInstance();
        $membership = $_owApp->getUser()->getIsMemberOf();

        $stmt = array();
        foreach($metadataCollections as $collection){
            if(($collection['isRestricted'] == 'http://vocab.ub.uni-leipzig.de/amsl/No') || ($collection['isRestricted'] == 'http://vocab.ub.uni-leipzig.de/amsl/Yes' && $collection['permittedForLibrary'] == $membership)){
                $stmt[$collection['collection']]['http://vocab.ub.uni-leipzig.de/amsl/evaluateHoldingsFileFor'][] = array('value' => $membership, 'type' => 'uri');
            }
        }
        $store = $this->_erfurt->getStore();
        $store->addMultipleStatements($discoveryIndex, $stmt);

        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();
    }

    public function evaluetenoneAction(){
        $source = $this->_request->getParam("source");
        $discoveryIndex = $this->_privateConfig->statements->discoveryIndex;
        $articleIndex = new ArticleIndexHelper($discoveryIndex);
        $metadataCollections = $articleIndex->queryMetadataCollections($source);
        $_owApp = OntoWiki::getInstance();
        $membership = $_owApp->getUser()->getIsMemberOf();

        $stmt = array();
        foreach($metadataCollections as $collection) {
            if (($collection['isRestricted'] == 'http://vocab.ub.uni-leipzig.de/amsl/No') || ($collection['isRestricted'] == 'http://vocab.ub.uni-leipzig.de/amsl/Yes' && $collection['permittedForLibrary'] == $membership)) {
                $stmt[$collection['collection']]['http://vocab.ub.uni-leipzig.de/amsl/evaluateHoldingsFileFor'][] = array('value' => $membership, 'type' => 'uri');
            }
        }
        $store = $this->_erfurt->getStore();
        $store->deleteMultipleStatements($discoveryIndex, $stmt);

        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();
    }

    /**
     * Saves the users decision whether or not the EZB holdings files shall be checked
     */
    public function checkholdingsfilesAction(){
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();
        $params = $this->_request->getParams();
        $collection = $params['collection'];
        $source = $params['source'];
        $checked = $params['checked'];

        $_owApp = OntoWiki::getInstance();
        $logger = $_owApp->getCustomLogger('statements');
        $logger->debug('StatementsController:deletestatementAction: ' . $collection . ' --> ' . $source);

        $membership = $_owApp->getUser()->getIsMemberOf();

        $discoveryIndexUri = $this->_privateConfig->statements->discoveryIndex;
        $articleIndex = new ArticleIndexHelper($discoveryIndexUri);

        if(isset($membership)) {
            if ($_owApp->erfurt->getAc()->isModelAllowed('edit', $discoveryIndexUri) === true) {
                if($checked == "true"){
                    $return = $articleIndex->saveCheckHoldingsFiles($collection, $membership);
                }else{
                    $return = $articleIndex->deleteCheckHoldingsFiles($collection, $membership);
                }
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
