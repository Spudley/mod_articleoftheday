Article Of The Day
=======================

A Joomla! module to provide your site with an Article of the Day feature.

Written by Simon Champion, September 2018.


Version History
---------------

* v1.0.0    Initial release.


Introduction
------------

This Joomla! module is designed to help a you surface the content on your site by selecting a random article from the site each day and displaying it in the module for the duration of the day. The following day, a different article will be selected, and so on each new day.


Setup
-----

1. Install the extension via the Extensions manager in Joomla!'s admin panel.
2. In the site admin menu, go to Content/Fields, and create a new field. It should be of type "Calendar" and should be linked with the article categories that you want the Articles of the Day to be picked from. Other than this, you may configure this field as you choose; you may or may not want it to be shown on the site with the articles. If you do show it, it will display the most recent date that each given article was chosen as article of the day.
3. Go to the Extensions/Modules menu, and add a module of the type "Article Of The Day". The key properties to set here are the category (or categories), which should be the same as the ones you selected for the field above, and AotD Fieldname, which should be the name of the field you defined above. You can set the remaining properties as need for your site.

Once the module has been created and positioned, you should be able to see it on the site. The first time it runs, it will scan the selected categories and pick an article to be Article of the Day for the current date. After that, it will always show the same article until the start of the next day.

You can force it to select a fresh article by editing the currently selected article in the admin panel. Go to the Fields tab, and either clear the field or set it to a date in the past.


Multiple Instances
------------------

As with all Joomla! modules, you can create duplicates of the module with different config parameters and different publishing positions, etc.

Use the "Duplicate" button on the list of modules to achieve this. This is standard Joomla! functionality, so please see the Joomla! documentation for more details.

If you specify the same field and categories in the module config for the second instance then the two instances of the module will display the same article of the day. However it is also possible to configure it with a different field and categories. Doing this will allow you to have multiple differnt articles of the day. For example, you may want to have a separate article of the day for each of your categories.


Plugins
--------

The module provides a set of events that can be implemented by a plugin of type 'articleoftheday'. These events are:

- onBeforeShowArticleOfTheDay and onBeforeShowArticleOfTheDay

    These two events are fired when the article of the day module is displayed. Both of them accept two arguments. These are: An object containing key properties of the article; and an object containing any fields associated with the article (including the calendar field required by the module). The return value of these two methods in your plugin class should be a string. This string will be rendered in the module, before or after the article itself.

- onNewArticleOfTheDay

     This event is fired when a new article of the day is selected. An example use-case for this would be for a plugin to add an automated tweet to your twitter feed telling your followers that there is a new article of the day. The arguments for this method are the article ID and the field name (ie the field specified in the module config). It should not return anything.

If you write a plugin for this module which you thing would be useful for others, please share it. I will be happy to include a link to it in this documentation.


Update the selected article from outside the module
---------------------------------------------------

If you want to programmatically trigger the module to get a new article of the day then you can do so with code like this:


    //initialise the Joomla environment...
    //(this bit is obviously only necessary if you are not already in the Joomla environment)
    define JPATH_BASE = "<path-to-your-joomla-installation>";
	define('_JEXEC', 1);
	require_once(JPATH_BASE . '/includes/defines.php');
	require_once(JPATH_BASE . '/includes/framework.php');
	$this->app = JFactory::getApplication('site');
	$this->app->initialise();

    //load the params for the module...
    //(where $title is the name you gave the instance of the module)
    jimport('joomla.application.module.helper');
    $module = JModuleHelper::getModule('mod_articleoftheday', $title);
    $moduleParams = new JRegistry;
    $moduleParams->loadString($module->params);

    //load the module's core class and instantiate an object from it...
    JLoader::register('ArticleOfTheDay',  JPATH_ROOT . '/modules/mod_articleoftheday/classes/ArticleOfTheDay.php');
    $aotd = new ArticleOfTheDay($moduleparams);

    //and then either this...
    $aotd->checkAndUpdate();        // checks to see if a new article of the day is due, and picks on if so.
    //or this...
    $aotd->forceNewRandomArticle()  // sets a new article of the day regardless of whether a change was due or not.

An example of when you might need code like this is if you want to refresh the article of the day using a cron job rather than letting the module do the work.

If you do this, you will probably also want to set the "Article refresh by module?" option in the module to "off", so that the module's automatic process for updating the article of the day doesn't interfere with your cron job.



Who wrote this?
---------------

This code was written by Simon Champion.

All code in this repository is released under the GPLv2 licence. The GPLv2 licence document should be included with the code.

If you like this module, please give it a rating in the Joomla Extensions Directory.


Known bugs and Limitations
--------------------------

- Note that this extension has only been tested against the current versions of Joomla! (3.8.11 at the time of writing).
- If your site uses caching (and it should do), then the module content may only be refreshed whenever the cache expires, rather than on every page load. This may result in the article of the day not being refreshed immediately at the start of the day.
- The code to display the article of the day is not the same as the code used to display articles elsewhere in Joomla!. Articles shown using this module may be rendered differently to how they would be rendered by other modules or the main content component. It also does not trigger the content component's events that are normally triggered when an article is displayed.


Todo
----

* Offer time and duration options to allow the AotD to expire at a time other than midnight, and to remain active for durations other than 24 hours.
* Implement caching to reduce database accesses by the module.


Trademarks and Licenses
-----------------------

* Joomla!® is a registered trademark of Open Source Matters, Inc.
* Joomla! is distributed under the GPLv2 licence.
* This package is distributed under the GPLv2 licence. The GPLv2 licence document should be included with the code.

