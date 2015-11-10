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

    function __construct($model)
    {
        $this->_modelUri = $model;
        $this->_erfurt = Erfurt_App::getInstance();
        $this->_store = $this->_erfurt->getStore();
        $this->_model = $this->_store->getModel($model);
        $this->_owApp = OntoWiki::getInstance();
        $this->_logger = $this->_owApp->getCustomLogger('statements');
    }

    public function getMetadataSources()
    {
        $query = "SELECT ?source ?sourceID WHERE { ?source a <http://vocab.ub.uni-leipzig.de/amsl/MetadataSource> . ?source <http://vocab.ub.uni-leipzig.de/amsl/sourceID> ?sourceID}";
        $sources = $this->_model->sparqlQuery($query);
        return $this->buildCollectionArray($sources);
    }

    private function buildCollectionArray($sources)
    {

        $return = array();
        $titleHelper = new OntoWiki_Model_TitleHelper();

        foreach ($sources as $source) {
            $resultArray['title'] = $source['sourceID'] . " - " . $titleHelper->getTitle($source['source']);
            $resultArray['hideCheckbox'] = true;
            $resultArray['folder'] = true;
            $resultArray['sourceID'] = $source['sourceID'];
            $resultArray['data'] = array("sourceUri" => $source['source']);
            $collections = $this->queryMetadataCollections($source['source']);
            if (isset($collections)) {
                foreach ($collections as $collection) {
                    $selection = isset($collection['usedBy']);
                    $prohibited = isset($collection['usageProhibitedBy']);
                    $accessDenied = false;
                    $disableCheckbox = false;
                    if(isset($collection['isRestricted']) && $collection['isRestricted'] == 'http://vocab.ub.uni-leipzig.de/amsl/Yes'){
                        $accessDenied = true;
                        $disableCheckbox = true;
                    }
                    if($prohibited || $accessDenied){
                        $disableCheckbox = true;
                    }
                    $permitted = false;
                    if(isset($collection['permittedForLibrary']) && $collection['permittedForLibrary'] == 'http://lobid.org/organisation/DE-15'){
                        $permitted = true;
                    }

                    $usage_note = '';
                    if($accessDenied && $permitted){
                        $usage_note = '</br><b> -> you need to contact your admin to select/deselect this collection</b>';
                    }
                    $resultArray['children'][] = array(
                        "title" => isset($collection['label']) ? $collection['label'] . $usage_note : $collection['collection'] . $usage_note,
                        "selected" => $selection,
                        "hideCheckbox" => $disableCheckbox,
                        "accessDenied" => $accessDenied,
                        "permitted" => $permitted,
                        "data" => array("collection" => $collection['collection']));
                }
            }
            $return[] = $resultArray;
            $resultArray = array();
        }

        // _naturally_ sort by source ID.
        function sortBySourceID($a, $b) {
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

            // SELECT ?collection ?usedBy ?label ?isRestricted ?permittedForLibrary WHERE {
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

}