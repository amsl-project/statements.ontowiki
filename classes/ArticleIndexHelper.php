<?php

/**
 * This file is part of the {@link http://amsl.technology amsl} project.
 *
 * @author Sebastian Nuck
 * @copyright Copyright (c) 2015, {@link http://ub.uni-leipzig.de Leipzig University Library}
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */
class ArticleIndexHelper
{
    private $_erfurt;
    private $_store;
    private $_model;
    private $_modelUri;
    private $_logger;
    private $_owApp;
    private $_translate;

    function __construct($model)
    {
        $this->_modelUri = $model;
        $this->_erfurt = Erfurt_App::getInstance();
        $this->_store = $this->_erfurt->getStore();
        $this->_model = $this->_store->getModel($model);
        $this->_owApp = OntoWiki::getInstance();
        $this->_logger = $this->_owApp->getCustomLogger('statements');
        $this->_translate = $this->_owApp->translate;
    }

    public function getMetadataSources()
    {
        $query = "SELECT ?source ?sourceID ?status WHERE { ?source a <http://vocab.ub.uni-leipzig.de/amsl/MetadataSource> . ?source <http://vocab.ub.uni-leipzig.de/amsl/sourceID> ?sourceID .
   OPTIONAL {
      ?source <http://vocab.ub.uni-leipzig.de/amsl/metadataSourceImplStatus> ?statusID .
      ?statusID <http://www.w3.org/2000/01/rdf-schema#label> ?status .
      FILTER (lang(?status) = 'en')
   }
}";
        $sources = $this->_model->sparqlQuery($query);
        return $this->buildCollectionArray($sources);
    }

    private function getIsHoldingsFilesCheckedArray($library)
    {
        $query = "SELECT ?source WHERE {?source <http://vocab.ub.uni-leipzig.de/amsl/evaluateHoldingsFileFor> <" . $library . ">}";
        $sources = $this->_model->sparqlQuery($query);
        $result = array();
        foreach($sources as $source){
            $result[] = $source['source'];
        }
        return $result;
    }

    public function getOrganisationLabel($membership){
        $_owApp = OntoWiki::getInstance();
        $lang = $_owApp->language;
        require_once 'OntoWiki/Model/TitleHelper.php';
        $titleHelper = new OntoWiki_Model_TitleHelper($this->_model);
        $title = $titleHelper->getTitle($membership, $lang);
        if($title == ''){
            $title = $this->_translate->_('No no organisation label found!');
        }
        return $title;
    }

    private function cmp($a, $b)
    {
        return strcasecmp ( $a['label'] , $b['label'] );
    }

    private function buildCollectionArray($sources)
    {
        $return = array();
        $titleHelper = new OntoWiki_Model_TitleHelper();
        $_owApp = OntoWiki::getInstance();
        $membership = $_owApp->getUser()->getIsMemberOf();
        $checkedHoldingsFiles = $this->getIsHoldingsFilesCheckedArray($membership);
        foreach ($sources as $source) {
            $checkThemAll = '<p class="asdfjka" style=" display:none;" >' . $source['source'] . '</p>';
            $status = $source['status'];
            if($status != null && $status != ''){
                $status = "<span style='color: dimgray;'> - " . $status . " </span>";
            }
            $resultArray['title'] = $source['sourceID'] . " - " . $titleHelper->getTitle($source['source'])  . $status . $checkThemAll;
            $resultArray['hideCheckbox'] = true;
            $resultArray['folder'] = true;
            $resultArray['sourceID'] = $source['sourceID'];
            $resultArray['data'] = array("sourceUri" => $source['source']);
            $collections = $this->queryMetadataCollections($source['source']);
            if (isset($collections)) {
                usort($collections, array('ArticleIndexHelper', 'cmp'));
                foreach ($collections as $collection) {
                    $selection = isset($collection['usedBy']);
                    if (!isset($collection['isRestricted']) || $collection['isRestricted'] == '') {
                        $enableCheckbox = false;
                    }else{
                        if ($collection['isRestricted'] == 'http://vocab.ub.uni-leipzig.de/amsl/Yes') {

                            if (isset($collection['permittedForLibrary']) && $collection['permittedForLibrary'] == $membership) {
                                $enableCheckbox = true;
                            }else{
                                $enableCheckbox = false;
                            }
                        } else {
                            $enableCheckbox = true;
                        }
                    }

                    $label = isset($collection['label']) ? $collection['label'] : $collection['collection'];
                    $uri = $collection['collection'];
                    $note = '';
                    if (!$enableCheckbox) {
                        $note .=  '<br> <div id="statements-small-hint"> -> ' . $this->_translate->_('This collection is restricted. Please contact team finc for further information.') . ' team@finc.info</div>';

                    }else{
                        if(in_array($uri, $checkedHoldingsFiles)){
                            $checked = 'checked';
                        }else{
                            $checked = '';
                        }
                        $note .= '<div class="checkfile">' . $this->_translate->_('evaluate holdings file') . '&nbsp; <input style="position:absolute; top: 2px; " type="checkbox" ' . $checked . ' name="abc" value="xyz" class="filecheckbox" id="' . uniqid("aasdf") . '"></div>';
                    }

                    $labeldiv = '<div class="headline">' . $label . '</div>';
                    $resultArray['children'][] = array(
                        "title" => $labeldiv . $note ,
                        "selected" => $selection,
                        "hideCheckbox" => !$enableCheckbox,
                        "data" => array("collection" => $collection['collection']));
                }
            }
            $return[] = $resultArray;
            $resultArray = array();
        }

        // _naturally_ sort by source ID.
        function sortBySourceID($a, $b)
        {
            return strnatcmp(intval($a['sourceID']), $b['sourceID']);
        }

        usort($return, "sortBySourceID");

        return $return;
    }

    public function queryMetadataCollections($metadataSource)
    {

        $_owApp = OntoWiki::getInstance();
        $membership = $_owApp->getUser()->getIsMemberOf();

        if ($membership) {

            $query = "PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
            PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
            PREFIX amsl: <http://vocab.ub.uni-leipzig.de/amsl/>
            PREFIX foaf: <http://xmlns.com/foaf/0.1/>

            SELECT ?collection ?usedBy ?label ?isRestricted ?permittedForLibrary WHERE {
                ?collection a amsl:MetadataCollection .
                ?collection amsl:includedInMetadataSource <" . $metadataSource . "> .
                ?collection rdfs:label ?label
            OPTIONAL {
                ?collection amsl:metadataUsedByLibrary ?usedBy . FILTER (?usedBy = <" . $membership . ">)
            }
            OPTIONAL {
                ?collection amsl:metadataUsageRestricted ?isRestricted .
            }
            OPTIONAL {
                ?collection amsl:metadataUsagePermittedForLibrary ?permittedForLibrary . FILTER (?permittedForLibrary = <" . $membership . ">)
            }
        }";
            $result = $this->_model->sparqlQuery($query);
            return $result;
        }
        return null;
    }


    public function saveStatement($collection, $membership)
    {
        $this->_logger->debug('ArticleIndexHelper:saveStatement: ' . $collection . ' --> ' . $membership);
        $result = $this->_store->addStatement(
            $this->_modelUri,
            $collection,
            "http://vocab.ub.uni-leipzig.de/amsl/metadataUsedByLibrary",
            array('value' => $membership, 'type' => 'uri'),
            false);
        $this->_logger->debug('ArticleIndexHelper:saveStatement:result: ' . $result);

        // return resultId of added statement
        return $result;

    }

    public function saveCheckHoldingsFiles($collection, $membership)
    {
        $uri = $this->_modelUri;
        $this->_logger->debug('ArticleIndexHelper:saveCheckHoldingsFiles: ' . $collection . ' --> ' . $membership);
        $result = $this->_store->addStatement(
            $this->_modelUri,
            $collection,
            "http://vocab.ub.uni-leipzig.de/amsl/evaluateHoldingsFileFor",
            array('value' => $membership, 'type' => 'uri'),
            false);
        $this->_logger->debug('ArticleIndexHelper:saveCheckHoldingsFiles:result: ' . $result);

        // return resultId of added statement
        return $result;

    }

    public function deleteStatement($collection, $membership)
    {
        $this->_logger->debug('ArticleIndexHelper:deleteStatement: ' . $collection . ' --> ' . $membership);
        $return = $this->_store->deleteMatchingStatements(
            $this->_modelUri,
            $collection,
            "http://vocab.ub.uni-leipzig.de/amsl/metadataUsedByLibrary",
            array('value' => $membership, 'type' => 'uri'),
            false);
        $this->_logger->debug('ArticleIndexHelper:deleteStatement:$return: ' . $return);

        // return number of deleted statements
        return $return;

    }

    public function deleteCheckHoldingsFiles($collection, $membership)
    {
        $this->_logger->debug('ArticleIndexHelper:deleteCheckHoldingsFiles: ' . $collection . ' --> ' . $membership);
        $return = $this->_store->deleteMatchingStatements(
            $this->_modelUri,
            $collection,
            "http://vocab.ub.uni-leipzig.de/amsl/evaluateHoldingsFileFor",
            array('value' => $membership, 'type' => 'uri'),
            false);
        $this->_logger->debug('ArticleIndexHelper:deleteCheckHoldingsFiles:$return: ' . $return);

        // return number of deleted statements
        return $return;

    }
}