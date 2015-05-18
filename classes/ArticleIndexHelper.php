<?php

/**
 * Statments component controller.
 *
 * @category   OntoWiki
 * @package    Extensions_Statements
 * @author     Sebastian Nuck
 * @copyright  Copyright (c) 2015, {@link http://ub.uni-leipzig.de UB Leipzig}
 * @license    http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
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
            $resultArray['title'] = $titleHelper->getTItle($source['source']) . " (sourceID:" . $source['sourceID'] . ")";
            $resultArray['hideCheckbox'] = true;
            $resultArray['folder'] = true;
            $resultArray['data'] = array("sourceUri" => $source['source']);
            $collections = $this->queryMetadataCollections($source['source']);
            if (isset($collections)) {
                foreach ($collections as $collection) {
                    $selection = isset($collection['usedBy']);
                    $resultArray['children'][] = array(
                        "title" => isset($collection['label']) ? $collection['label'] : $collection['collection'],
                        "selected" => $selection,
                        "data" => array("collection" => $collection['collection']));
                }
            }
            $return[] = $resultArray;
            $resultArray = array();
        }
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

                    SELECT ?collection ?usedBy ?label WHERE {
                           ?collection a amsl:MetadataCollection .
                           ?collection amsl:includedInMetadataSource <" . $metadataSource . "> .
                           ?collection rdfs:label ?label
                           OPTIONAL {
                                ?collection amsl:metadataUsedByLibrary ?usedBy . FILTER (?usedBy = <" . $membership . ">)
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