<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  System.Moduleversion
 *
 * @copyright   (C) 2018 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\System\Moduleversion;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Helper for plugin moduleversion
 *
 * @since  1.6
 */
abstract class Helper
{
	/**
	 * Datbase helper to get the current module versions
	 * @param   int $moduleId module ID
	 * @return array
	 */
	public static function getVersions($moduleId): array
	{
		$db = Factory::getContainer()->get('DatabaseDriver');

		$query = $db->getQuery(true);

		$query->select(
			$db->quoteName(
				array(
					'id',
					'current',
					'mod_id',
					'asset_id',
					'title',
					'note',
					'content',
					'ordering',
					'position',
					'published',
					'module',
					'access',
					'showtitle',
					'params',
					'client_id',
					'language',
					'changedate'
				)
			)
		);

		$query
			->from($db->quoteName('#__modules_versions'))
			->where($db->quoteName('mod_id') . ' LIKE ' . $db->quote($moduleId))
			->order($db->quoteName('id') . 'DESC');

		$db->setQuery($query);

		$results = $db->loadObjectList();

		return $results;
	}

	/**
	 * Datbase helper to store the current module versions
	 * @param   object $item Current module item
	 * @return  void
	 */
	public static function storeVersion($item)
	{
		$db = Factory::getContainer()->get('DatabaseDriver');

		$query = $db->getQuery(true);

		$columns = array(
			'mod_id',
			'current',
			'asset_id',
			'title',
			'note',
			'content',
			'ordering',
			'position',
			'published',
			'module',
			'access',
			'showtitle',
			'params',
			'client_id',
			'language'
		);

		$values = array(
			(int) $item->id,
			(bool) true,
			(int) $item->asset_id,
			(string) $db->quote($item->title),
			(string) $db->quote($item->note),
			(string) $db->quote($item->content),
			(int) $item->ordering,
			(string) $db->quote($item->position),
			(int) $item->published,
			(string) $db->quote($item->module),
			(int) $item->access,
			(int) $item->showtitle,
			(string) $db->quote($item->params),
			(int) $item->client_id,
			(string) $db->quote($item->language)
		);

		$query
			->insert($db->quoteName('#__modules_versions'))
			->columns($db->quoteName($columns))
			->values(implode(',', $values));

		$db->setQuery($query);

		$db->execute();
	}

	/**
	 * Datbase helper to reset the check icon
	 * @return  void
	 */
	public static function resetCurrent()
	{
		$db = Factory::getContainer()->get('DatabaseDriver');

		$query = $db->getQuery(true);

		$fields = array(
			$db->quoteName('current') . ' =' . 0
		);

		$conditions = array(
			$db->quoteName('current') . ' =' . 1
		);

		$query
			->update($db->quoteName('#__modules_versions'))
			->set($fields)
			->where($conditions);

		$db->setQuery($query);

		$db->execute();
	}

	/**
	 * Datbase helper to set the check icon
	 * @param   int $id Current module item
	 * @return  void
	 */
	public static function setCurrent($id)
	{
		$db = Factory::getContainer()->get('DatabaseDriver');

		$query = $db->getQuery(true);

		$fields = array(
			$db->quoteName('current') . ' = ' . 1
		);

		$conditions = array(
			$db->quoteName('id') . ' = ' . $id
		);

		$query
			->update($db->quoteName('#__modules_versions'))
			->set($fields)
			->where($conditions);

		$db->setQuery($query);

		$db->execute();
	}

	/**
	 * Datbase helper to update the module with the selected module versions
	 * @param   object $item Current module item
	 * @return  void
	 */
	public static function uodateVersion($item)
	{
		$db = Factory::getContainer()->get('DatabaseDriver');

		$query = $db->getQuery(true);

		$fields = array(
			$db->quoteName('asset_id') . ' = ' . $db->quote($item->asset_id),
			$db->quoteName('title') . ' = ' . $db->quote($item->title),
			$db->quoteName('note') . ' = ' . $db->quote($item->note),
			$db->quoteName('content') . ' = ' . $db->quote($item->content),
			$db->quoteName('ordering') . ' = ' . $db->quote($item->ordering),
			$db->quoteName('position') . ' = ' . $db->quote($item->position),
			$db->quoteName('published') . ' = ' . $db->quote($item->published),
			$db->quoteName('module') . ' = ' . $db->quote($item->module),
			$db->quoteName('access') . ' = ' . $db->quote($item->access),
			$db->quoteName('showtitle') . ' = ' . $db->quote($item->showtitle),
			$db->quoteName('params') . ' = ' . $db->quote($item->params),
			$db->quoteName('client_id') . ' = ' . $db->quote($item->client_id),
			$db->quoteName('language') . ' = ' . $db->quote($item->language)
		);

		$conditions = array(
			$db->quoteName('id') . ' = ' . $item->mod_id
		);

		$query->update($db->quoteName('#__modules'))->set($fields)->where($conditions);

		$db->setQuery($query);

		$db->execute();
	}

	/**
	 * Datbase helper to delete verions of trashed modules
	 * @param   int   $item Current module item
	 * @return  void
	 */
	public static function deleteVersion($item)
	{
		$db = Factory::getContainer()->get('DatabaseDriver');

		$query = $db->getQuery(true);

		$conditions = array(
			$db->quoteName('mod_id') . ' = ' . $item,
		);

		$query
			->delete($db->quoteName('#__modules_versions'))
			->where($conditions);

		$db->setQuery($query);

		$db->execute();
	}

	/**
	 * Datbase helper to compare the current module settings and the latest version in DB
	 * @param   object   $moduleSettings 	The current module item
	 * @param   object   $loadedVersion 	The version loaded from the database
	 * @return boolean
	 */
	public static function compareVersion($moduleSettings, $loadedVersion)
	{
		$settingsChanged = false;

		(array) $source = [
			(int) $moduleSettings->asset_id,
			(string) $moduleSettings->title,
			(string) $moduleSettings->note,
			(string) $moduleSettings->content,
			(int) $moduleSettings->ordering,
			(string) $moduleSettings->position,
			(int) $moduleSettings->published,
			(string) $moduleSettings->module,
			(int) $moduleSettings->access,
			(int) $moduleSettings->showtitle,
			(string) $moduleSettings->params,
			(int) $moduleSettings->client_id,
			(string) $moduleSettings->language
		];

		(array) $target = [
			(int) $loadedVersion->asset_id,
			(string) $loadedVersion->title,
			(string) $loadedVersion->note,
			(string) $loadedVersion->content,
			(int) $loadedVersion->ordering,
			(string) $loadedVersion->position,
			(int) $loadedVersion->published,
			(string) $loadedVersion->module,
			(int) $loadedVersion->access,
			(int) $loadedVersion->showtitle,
			(string) $loadedVersion->params,
			(int) $loadedVersion->client_id,
			(string) $loadedVersion->language
		];

		(array) $result = array_diff_assoc($source, $target);

		if (count($result) !== 0)
		{
			$settingsChanged = true;
		}

		return $settingsChanged;
	}

	/**
	 * Datbase helper to format up the parameters object
	 * @param   string   $values   Object with module parameters
	 * @return string
	 */
	public static function formatOutput($values): string
	{
		$formatedData = json_decode($values);
		$valuesTable = '<table class="table table-sm table-hover"><thead><tr>';
		$valuesTable .= '<th scope="col">' . Text::_('PLG_SYSTEM_MODULEVERSION_KEY') . '</th>';
		$valuesTable .= '<th scope="col">' . Text::_('PLG_SYSTEM_MODULEVERSION_VALUE') . '</th>';
		$valuesTable .= '</tr></thead><tbody>';

		foreach ($formatedData as $key => $value)
		{
			if (is_object($value))
			{
				$value = (array) $value;
			}

			if (is_array($value))
			{
				$newValue = '';

				foreach ($value as $index => $separateValue)
				{
					$newValue .= $separateValue;

					if ($index !== array_key_last($value))
					{
						$newValue .= ', ';
					}
				}

				$value = $newValue;
			}

			$key = str_replace('_', ' ', $key);

			$valuesTable .= '<tr><td class="w-25 fw-bold" scope="row">' . $key . '</td><td>' . $value . '</td>';
		}

		$valuesTable .= '</tbody></table>';

		return($valuesTable);
	}

	/**
	 * Datbase helper to remove obsolete versions
	 * @param   int $id Current module item
	 * @return  void
	 */
	public static function removeObsolete($id)
	{
		$db = Factory::getContainer()->get('DatabaseDriver');

		$query = $db->getQuery(true);

		$query
			->delete($db->quoteName('#__modules_versions'))
			->where($db->quoteName('mod_id') . ' LIKE ' . $db->quote($id))
			->order($db->quoteName('id') . 'DESC')
			->setLimit(1);

		$db->setQuery($query);

		$db->execute();
	}
}
