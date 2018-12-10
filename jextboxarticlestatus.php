<?php

/**
* @package       JExtBOX Article Status
* @author        Galaa
* @publisher     JExtBOX - BOX of Joomla Extensions (www.jextbox.com)
* @authorUrl     www.galaa.mn
* @copyright     copyright (C) 2012-2018 Galaa
* @license       This extension in released under the GNU/GPL License - http://www.gnu.org/copyleft/gpl.html
*/

// No direct access
defined('_JEXEC') or die;

// Import library dependencies
jimport('joomla.plugin.plugin');

class plgContentjextboxarticlestatus extends JPlugin
{

	public function onContentBeforeDisplay($context, &$article, &$params)
	{

		// check excluded views

		if (JFactory::getApplication()->isAdmin() || JFactory::getApplication()->input->get('option', '', 'cmd') != 'com_content' || !in_array($context, array('com_content.article', 'com_content.category', 'com_content.featured'))) :
			return;
		endif;

		$categories = $this->params->get('categories', array());
		$categories_option = $this->params->get('categories_option', 'exclude');
		$catid_in = in_array($article->catid, $categories);
		if (($catid_in && $categories_option == 'exclude') || (!$catid_in && $categories_option == 'include')) {
			return;
		}

		$articles = trim($this->params->get('articles', ''));
		if (!empty($articles)) {
			$articles = explode(',', $articles);
		}
		settype($articles, 'array');
		$articleid_in = in_array($article->id, $articles);
		$articles_option = $this->params->get('articles_option', 'exclude');
		if (($articleid_in && ($articles_option == 'exclude')) || (!$articleid_in && ($articles_option == 'include'))) {
			return;
		}
		$view = JFactory::getApplication()->input;
		if (($view == 'article') && ($this->params->get('show_in_full_view', 'yes') == 'no')) { 
			return;
		}
		if ((($view == 'featured') || ($view == 'frontpage')) && ($this->params->get('show_in_featured_view', 'yes') == 'no')) { 
			return;
		}
		if (($view == 'category') && ($this->params->get('show_in_category_view', 'yes') == 'no')) { 
			return;
		}

		// New or Modified

		$identify_new = $this->params->get('identify_new', 'yes');
		$identify_modified = $this->params->get('identify_modified', 'yes');

		// prepare article status sign

		if ($identify_new == 'yes') {
			if ($this->params->get('sign', 'default') == 'default') {
				$article_status_new = '<img style="border: 0pt none; vertical-align: middle;" alt="Just Added" title="" src="plugins/content/jextboxarticlestatus/images/just_added.png"/>';
			} else {
				$article_status_new = $this->params->get('sign_new_custom', '');
			}
		}
		if ($identify_modified == 'yes') {
			if ($this->params->get('sign', 'default') == 'default') {
				$article_status_modified = '<img style="border: 0pt none; vertical-align: middle;" alt="Modified" title="" src="plugins/content/jextboxarticlestatus/images/modified.png"/>';
			} else {
				$article_status_modified = $this->params->get('sign_modified_custom', '');
			}
		}

		// define article status

		$article_status = '';

		// define New or Modified status

		if ($identify_new == 'yes') {
			$created = strtotime($article->created);
			$articletime = $created;
			$status = $article_status_new;
		}
		if ($identify_modified == 'yes') {
			$modified = strtotime($article->modified);
			$articletime = $modified;
			$status = $article_status_modified;
		}
		if (($identify_new == 'yes') && ($identify_modified == 'yes')) {
			if (floor(($modified - $created) / 86400) >= 1) {
				$articletime = $modified;
				$status = $article_status_modified;
			} else {
				$articletime = $created;
				$status = $article_status_new;
			}
		}
		if (floor((time() - $articletime) / 86400) <= $this->params->get('days', '7')) {
			$article_status .= $status;
		}

		// define Featured status

		if ((($view == 'category') || ($view == 'article')) && ($this->params->get('identify_featured', 'yes') == 'yes') && ($article->featured == 1)) {
			if ($this->params->get('sign', 'default') == 'default') {
				$status = '<img style="border: 0pt none; vertical-align: middle;" alt="Featured" title="" src="plugins/content/jextboxarticlestatus/images/featured.png"/>';
			} else {
				$status = $this->params->get('sign_featured_custom', '');
			}
			$article_status .= $status;
		}

		// Hit or Popular status

		$hit_status = '';
		$db = JFactory::getDBO();
		$query = 'SELECT `hits` FROM `#__content` WHERE `state`=1 ';
		if (!empty($categories)) {
			$categories = implode(',', $categories);
			$query .= 'AND `catid` ';
			if ($this->params->get('categories_option', 'exclude') == 'exclude') {
				$query .= 'NOT ';
			}
			$query .= 'IN ('.$categories.') ';
		}
		$articles = trim($this->params->get('articles'));
		if (!empty($articles)) {
			$query .= 'AND `id` ';
			if ($this->params->get('articles_option', 'exclude') == 'exclude') {
				$query .= 'NOT ';
			}
			$query .= 'IN ('.$articles.') ';
		}
		$query .= 'ORDER BY `hits` ASC';
		$db->setQuery($query);
		$hits_array = $db->loadColumn();
		$sign_hit_limit = $this->params->get('sign_hit_limit', 0.9);
		$sign_hit_limit = $hits_array[floor($sign_hit_limit * count($hits_array))];
		$sign_popular_limit = $this->params->get('sign_popular_limit', 0.75);
		$sign_popular_limit = $hits_array[floor($sign_popular_limit * count($hits_array))];
		unset($hits_array);

		// define Most Hit status

		if (($this->params->get('identify_hit', 'yes') == 'yes') && ($article->hits >= $sign_hit_limit)) {
			if ($this->params->get('sign', 'default') == 'default') {
				$status = '<img style="border: 0pt none; vertical-align: middle;" alt="Most Hit" title="" src="plugins/content/jextboxarticlestatus/images/most_hit.png"/>';
			} else {
				$status = $this->params->get('sign_hit_custom', '');
			}
			$hit_status = $status;
		}

		// define Popular status

		if (($this->params->get('identify_popular', 'yes') == 'yes') && ($hit_status == '') && ($article->hits >= $sign_popular_limit)) {
			if ($this->params->get('sign', 'default') == 'default') {
				$status = '<img style="border: 0pt none; vertical-align: middle;" alt="Popular" title="" src="plugins/content/jextboxarticlestatus/images/popular.png"/>';
			} else {
				$status = $this->params->get('sign_popular_custom', '');
			}
			$hit_status = $status;
		}

		// Hit or Popular status

		$article_status .= $hit_status;

		// define Archived status

		$archived_status = '';
		if (($this->params->get('identify_archived') == 'yes') && isset($article->state) && ($article->state == 2)) {
			if($this->params->get('sign', 'default') == 'default') {
				$status = '<img style="border: 0pt none; vertical-align: middle;" alt="Archived" title="" src="plugins/content/jextboxarticlestatus/images/archived.png"/>';
			}else{
				$status = $this->params->get('sign_archived_custom', '');
			}
			$archived_status = $status;
		}

		// Archived status

		$article_status .= $archived_status;

		// DIV

		if ($this->params->get('wrap_by_div', 'no') == 'yes') {
			$class = trim($this->params->get('div_css_class_name', ''));
			$class = !empty($class) ? ' class="' . $class . '"' : '';
			$style = trim($this->params->get('div_css_style', ''));
			$style = !empty($style) ? ' style="' . $style . '"' : '';
			$article_status = '<div' . $class . $style . '>' . $article_status . '</div>';
		}

		// article status
		return $article_status;

	} // onContentBeforeDisplay

} // class

?>
