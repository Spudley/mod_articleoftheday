<?php
/**
 * @package  mod_randomstrapline
 *
 * @copyright   Copyright (C) 2018 Simon Champion.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

$htmlclass = $params->get('htmlclass','articleoftheday');

JLoader::register('ArticleOfTheDay', __DIR__ . '/classes/ArticleOfTheDay.php');

$aotd = new ArticleOfTheDay($params);
if ($params->get('module_triggers_refresh', 1)) {
    $aotd->checkAndUpdate();
}

$item = $aotd->getArticle();
$fields = $aotd->getFields();

//fire events so that plugins can do fun stuff like sending a tweet every time we get a new article of the day.
$showBefore = $aotd->fireEvent('onBeforeShowArticleOfTheDay', [$item, $fields]);
$showAfter = $aotd->fireEvent('onAfterShowArticleOfTheDay', [$item, $fields]);

require JModuleHelper::getLayoutPath('mod_articleoftheday', 'default');

