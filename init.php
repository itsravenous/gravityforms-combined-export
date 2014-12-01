<?php
/**
 * Plugin name: Gravity Forms Combined Export
 */

$plugdir = dirname(__FILE__) . '/';

require_once($plugdir.'config.php');
require_once($plugdir.'inc/export-class.php');

class rv_gravity_combined_export {

	public static function create_menu() {
		add_menu_page('Gravity Combined Export', 'Gravity Combined Export', 'gform_full_access', 'gravity-combined-export', array('rv_gravity_combined_export', 'render_export_page'));
	}

	public static function render_export_page() {

		// Get forms
		$forms = rv_gravity::get_forms();

		include('views/export.php');
	}

	public static function process_form() {
		global $plugdir;

		// Get dates
		$date_start = !empty($_POST['gf-combined-date-start']) ? $_POST['gf-combined-date-start'] : FALSE;
		$date_end = !empty($_POST['gf-combined-date-end']) ? $_POST['gf-combined-date-end'] : FALSE;

		// Validate and format dates
		$date_regex = '/^(0[1-9]|[12][0-9]|3[01])[\/](0[1-9]|1[012])[\/](19|20)\d\d$/';
		if ($date_start && !preg_match($date_regex, $date_start)) {
			wp_die('Invalid start date.');
		} elseif($date_start) {
			$date_start = date('Y-m-d', strtotime(str_replace('/', '-', $date_start)));
		}
		if ($date_end && !preg_match($date_regex, $date_end)) {
			wp_die('Invalid end date.');
		} elseif($date_end) {
			$date_end = date('Y-m-d', strtotime(str_replace('/', '-', $date_end)));
		}

		$form_ids = $_POST['gf-combined-forms'];
		
		
		// Get desired fields from config
		$fields = GFCEConfig::$fields;

		$exporter = new rv_gravity_export(array(
			'db' => array(
				'host' => DB_HOST,
				'user' => DB_USER,
				'password' => DB_PASSWORD,
				'name' => DB_NAME,
			),
		));

		// Create timestamp for export files
		$timestamp = date('Y-m-d-H-i-s');

		$form_results = array();

		// Loop over forms and create array of entries with just the fields defined in config
		foreach ($form_ids as $form_id) {
			// Get all forms
			$forms = rv_gravity::get_forms();

			// Build export filename
			$form = array_values(array_filter($forms, function ($form) use ($form_id) {
				return $form->id == $form_id;
			}));
			$form = $form[0];
			$filename = $plugdir.'export/export-'.$form->title.'-'.$timestamp.'.csv';

			// Export form entries to CSV file
			$results = $exporter->get_entries(array(
				'form_id' => $form_id,
				'date_from' => $date_start,
				'date_to' => $date_end,
			));

			// Strip out all but fields defined in config
			$results = array_map(function ($result) use ($fields) {
				$result = array_intersect_key($result, array_flip($fields));
				// Pad with empty valyues for missing cols
				foreach ($fields as $key) {
					if (!isset($result[$key])) $result[$key] = '';
				}
				return $result;
			}, $results);

			$form_results = array_merge($form_results, $results);
			
		}

		// Create CSV from array
		$csv_header = $fields;
		$form_results = array_map(function ($form_result) use ($fields) {
			// Make sure fields are in correct order
			uksort($form_result, function ($key1, $key2) use ($fields) {
				$idx1 = array_search($key1, $fields);
				$idx2 = array_search($key2, $fields);
				if ($idx1 > $idx2) {
					return 1;
				} elseif ($idx1 < $idx2) {
					return -1;
				} else {
					return 0;
				}
			});

			return $form_result;
		}, $form_results);

		// Create CSV from array
		$csv_rows = array();
		// Header row
		$csv_rows[] = implode(',', $fields);
		// Data rows
		foreach ($form_results as $form_result) {
			$csv_rows[] = implode(',', array_map(function ($val) {
				return '"'.str_replace(array(
					'"',
					',',
				),
				array(
					'""',
					'',
				), $val).'"';
			}, array_values($form_result)));
		}

		// Write CSV to file
		$filename = $plugdir.'export/'.$timestamp.'.csv';
		file_put_contents($filename, implode(PHP_EOL, $csv_rows));

		// Send file to browser
		header('Content-type: text/csv');
		header('Content-Disposition: attachment; filename="'.basename($filename).'"');
		die(file_get_contents($filename));
	}

}

add_action('admin_menu', array('rv_gravity_combined_export', 'create_menu'));
add_action('admin_action_rv_gravity_combined_export', array('rv_gravity_combined_export', 'process_form'));

?>
