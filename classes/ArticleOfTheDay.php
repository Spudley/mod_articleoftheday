<?php
/**
 * @package  mod_articleoftheday
 *
 * @copyright   Copyright (C) 2018 Simon Champion.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

class ArticleOfTheDay
{
    private $params;
    private $articleId;

    public function __construct($params) {
        $this->params = $params;
    }

    public function getArticle() {
        //3. load the article for the ID we have.
        //4. return the article
        $article = $this->loadArticle();
        $article->readmore = strlen($article->fulltext) > 0;
        return $article;
    }

    public function getFields() {
        //5. load the fields for this article.
        //6. return the fields.
        //7. convert json data to sub-arrays.
        $fields = $this->loadFieldsForArticle();
        return array_map(function($field) {
            $field->params = json_decode($field->params);
            $field->fieldparams = json_decode($field->fieldparams);
            return $field;
        }, $fields);
    }

    private function loadArticle() {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->select(['a.id','a.title','a.alias','a.introtext','a.fulltext']);
        $query->from($db->quoteName('#__content').' as a');
        $query->where('a.id = '.$db->quote($this->articleId));

        $db->setQuery($query);
        return $db->loadObject();
    }

    private function loadFieldsForArticle() {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->select(['f.label','f.params','f.fieldparams','v.value']);
        $query->from($db->quoteName('#__fields').' as f');
        $query->join('inner', $db->quoteName('#__fields_values').' AS v ON f.id = v.field_id');
        $query->where('f.context = "com_content.article"');
        $query->where('f.state = 1');
        $query->where('v.item_id = '.$db->quote($this->articleId));
        $query->order('f.ordering');

        $db->setQuery($query);
        return $db->loadObjectList();
    }

    /**
     * Loads the article ID most recently marked as the article of the day.  This is specified in a field that is defined in config.
     */
    private function loadMostRecentField() {
        $fieldName = $this->params->get('fieldName');
        if (!$fieldName) {
            //throw new Exception(JText::_('MOD_ARTICLEOFTHEDAY_FIELD_NOT_SET'));
            throw new Exception('Article Of The Day field not specified');
        }

        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->select(['f.id','v.item_id','v.value']);
        $query->from($db->quoteName('#__fields_values').' as v');
        $query->join('inner', $db->quoteName('#__fields').' as f ON f.id = v.field_id');
        $query->where('f.'.$db->quoteName('context').' = "com_content.article"');
        $query->where('f.'.$db->quoteName('name').' = '.$db->quote($fieldName));
        $query->order('v.value DESC');
        $query->setLimit(1);

        $db->setQuery($query);
        return $db->loadObject();
    }

    public function checkAndUpdate() {
        //1. load the field specified in config; find the most recent (highest) value. Get the article ID from this field.
        $field = $this->loadMostRecentField();
        $needNew = !$field;
        if (!$needNew) {
            $this->articleId = $field->item_id;

            //2. if date is older than today, then pick new random article, save date to field, trigger events, and get its ID.
            $fieldDate = new DateTime(substr($field->value,0,10).' 00:00:00');
            $timeNow = (new DateTime())->setTime(0,0);
            $needNew = $timeNow > $fieldDate;
        }
        if ($needNew) {
            $this->articleId = $this->forceNewRandomArticle();
            //3. save today's date to new article's field to mark it permanently as being AotD for today.
            $this->markAsArticleOfTheDay($this->articleId);
        }
    }

    public function forceNewRandomArticle() {
        //@todo: cache $catIds and $articleIds so we can skip this bit.
        $catIds = $this->params->get('categories');
        if (!is_array($catIds) || !count($catIds)) {
            //throw new Exception(JText::_('MOD_ARTICLEOFTHEDAY_CATS_NOT_SET'), 404);
            throw new Exception('Categories not specified', 404);
        }


        $articleIds = $this->loadArticleIdList($catIds);

        if (!$articleIds or !count($articleIds)) {
            //throw new Exception(JText::_('MOD_ARTICLEOFTHEDAY_NO_ARTICLES'), 404);
            throw new Exception('No qualifying articles found', 404);
        }

        shuffle($articleIds);
        return array_pop($articleIds);
    }

    private function loadArticleIdList($catIds) {
        $db = JFactory::getDbo();
        $catsIn = implode(',',array_map(function($value) use($db) {
            return $db->quote($value);
        }, $catIds));

        $query = "SELECT a.id FROM #__content a WHERE a.catid in ({$catsIn}) AND a.state = '1' ";

        $language = JFactory::getLanguage();
        if($language->getTag()){
            $query .= "AND a.language IN('*','".$language->getTag()."') ";
        }

        $query .= "AND (a.publish_up <= '".date('Y-m-d H:i:s')."' OR a.publish_up = '0000-00-00 00:00:00') ".
                "AND (a.publish_down >= '".date('Y-m-d H:i:s')."' OR a.publish_down = '0000-00-00 00:00:00') ";

        $db->setQuery($query);
        return $db->loadColumn();
    }

    private function markAsArticleOfTheDay($articleId) {
        $fieldname = $this->params->get('fieldName');
        $fieldId = $this->fieldIdFromName($fieldname);
        if (!$fieldId) {
            //throw new Exception(JText::_('MOD_ARTICLEOFTHEDAY_FIELD_NOT_EXIST'));
            throw new Exception('Article Of The Day field does not exist');
        }

        //next delete any existing field
        $this->deleteExistingFieldForThisArticle($fieldId, $articleId);
        $this->deleteExistingFieldForThisDate($fieldId);

        //and finally insert a new one with today's date.
        $this->insertFieldData($fieldId, $articleId, date('Y-m-d 00:00:00'));

        //fire event so that plugins can do fun stuff like sending a tweet every time we get a new article of the day.
        $response = $this->fireEvent('onNewArticleOfTheDay', [$articleId, $fieldname]);
    }
    private function fieldIdFromName($fieldName) {
        //first get the fieldId
        $db = JFactory::getDbo();
        $query = "SELECT f.id FROM #__fields f WHERE f.".$db->quoteName('name').'='.$db->quote($fieldName);
        $db->setQuery($query);
        return $db->loadResult();
    }
    private function deleteExistingFieldForThisArticle($fieldId, $articleId) {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $conditions = array(
            $db->quoteName('field_id') . ' = '. $db->quote($fieldId), 
            $db->quoteName('item_id') . ' = ' . $db->quote($articleId)
        );
        $query->delete($db->quoteName('#__fields_values'));
        $query->where($conditions);
        $db->setQuery($query);
        $result = $db->execute();
    }
    private function deleteExistingFieldForThisDate($fieldId) {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $conditions = array(
            $db->quoteName('field_id') . ' = '. $db->quote($fieldId), 
            $db->quoteName('value') . ' = ' . $db->quote(date('Y-m-d 00:00:00'))
        );
        $query->delete($db->quoteName('#__fields_values'));
        $query->where($conditions);
        $db->setQuery($query);
        $result = $db->execute();
    }
    private function insertFieldData($fieldId, $articleId, $content) {
        $object = (object)[
            'field_id' => $fieldId,
            'item_id' => $articleId,
            'value' => $content
        ];

        // Update their details in the users table using id as the primary key.
        $result = JFactory::getDbo()->insertObject('#__fields_values', $object);
    }

    public function fireEvent($event, $args = [])
    {
        JPluginHelper::importPlugin('articleoftheday');
        $dispatcher = JEventDispatcher::getInstance();
        return $dispatcher->trigger($event, $args);
    }
}


