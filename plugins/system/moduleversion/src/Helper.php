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
 * Helper for plugin moduleversion.
 *
 * @since  1.6
 */
abstract class Helper
{
	/**
	 * Datbase helper to get the current module versions.
	 *
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
	 * Datbase helper to store the current module versions.
	 *
	 * @param   object $item Current module item
	 * @return  void
	 */
	public static function storeVersion($item)
	{
		$db = Factory::getContainer()->get('DatabaseDriver');

		$query = $db->getQuery(true);

		$fields = array_merge(
			self::filterContent((array) $item),
			[
				'mod_id' => $item->id,
				'current' => true,
			]
		);

		$columns = array_keys($fields);

		$values = array_map(
			function ($value) use ($db) {
				return $db->quote($value);
			},
			$fields
		);

		$query
			->insert($db->quoteName('#__modules_versions'))
			->columns($db->quoteName($columns))
			->values(implode(',', $values));

		$db->setQuery($query);

		$db->execute();
	}

	/**
	 * Datbase helper to reset the check icon.
	 *
	 * @param   int $modId  The module ID.
	 * @return  void
	 */
	public static function resetCurrent(int $modId)
	{
		$db = Factory::getContainer()->get('DatabaseDriver');

		$query = $db->getQuery(true);

		$fields = array(
			$db->quoteName('current') . ' = ' . 0
		);

		$conditions = [
			$db->quoteName('current') . ' = ' . 1,
			$db->quoteName('mod_id') . ' = ' . $modId,
		];

		$query
			->update($db->quoteName('#__modules_versions'))
			->set($fields)
			->where($conditions);

		$db->setQuery($query);

		$db->execute();
	}

	/**
	 * Datbase helper to set the check icon.
	 *
	 * @param   int $id    Current module item.
	 * @param   int $modId The module ID.
	 * @return  void
	 */
	public static function setCurrent($id, $modId)
	{
		/**
		 * @var \Joomla\Database\DatabaseDriver $db
		 */
		$db = Factory::getContainer()->get('DatabaseDriver');

		$query = $db->getQuery(true);

		$fields = array(
			$db->quoteName('current') . ' = CASE WHEN ' .
				$db->quoteName('id') . ' = ' . $db->quote($id) . ' THEN ' . 1 . ' ELSE ' . 0 . ' END'
		);

		$conditions = array(
			$db->quoteName('mod_id') . ' = ' . $modId
		);

		$query
			->update($db->quoteName('#__modules_versions'))
			->set($fields)
			->where($conditions);

		$db->setQuery($query);

		$db->execute();
	}

	/**
	 * Database helper to update the module with the selected module versions.
	 *
	 * @param   \stdClass $item Current module item.
	 * @return  void
	 */
	public static function updateModuleToVersion(\stdClass $item): void
	{
		$db = Factory::getContainer()->get('DatabaseDriver');

		$query = $db->getQuery(true);

		$fields = array_map(
			function (string $key, $value) use ($db) {
				return $db->quoteName($key) . ' = ' . $db->quote($value);
			},
			array_keys($temp = self::filterContent((array) $item, ['mod_id'])), array_values($temp)
		);

		$conditions = array(
			$db->quoteName('id') . ' = ' . $item->mod_id //phpcs:ignore
		);

		$query->update($db->quoteName('#__modules'))->set($fields)->where($conditions);

		$db->setQuery($query);

		$db->execute();
	}

	/**
	 * Datbase helper to delete verions of trashed modules.
	 *
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
	 * @param   \stdClass $moduleSettings The current module item.
	 * @param   \stdClass $loadedVersion  The version loaded from the database.
	 * @return  boolean
	 */
	public static function compareVersion($moduleSettings, $loadedVersion)
	{
		$settingsChanged = false;

		$source = self::filterContent((array) $moduleSettings);
		$target = self::filterContent((array) $loadedVersion);

		return (bool) count(array_diff_assoc($source, $target));
	}

	/**
	 * Filters the module content for the values.
	 *
	 * @param   array $content  The module content.
	 * @param   array $excluded The excluded key from the filter.
	 * @return  array<string, mixed>
	 */
	protected static function filterContent(array $content, array $excluded = []): array
	{
		return array_filter(
			$content,
			function ($key) use ($excluded) {
				return !in_array($key, $excluded, true) && in_array(
					$key,
					[
						'mod_id',
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
					],
					true
				);
			},
			ARRAY_FILTER_USE_KEY
		);
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
	 * Datbase helper to remove obsolete versions.
	 *
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
