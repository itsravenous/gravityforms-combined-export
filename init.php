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
		
		
		// $form_id = $form_ids[0];

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

		// Open zip archive
		$zipfile = $plugdir.'export/gravity-combined-export-'.$timestamp.'.zip';
		$zip = new ZipArchive();
		if ($zip->open($zipfile, ZipArchive::CREATE) !== TRUE) {
			wp_die("cannot open zipfile");
		}

		// Loop over forms and export to zip
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
			$exporter->export_entries(array(
				'form_id' => $form_id,
				'date_from' => $date_start,
				'date_to' => $date_end,
				'out' => $filename,
			));
			$zip->addFile($filename, basename($filename));
		}

		$zip->close();

		header('Content-type: application/zip');
		header('Content-Disposition: attachment; filename="'.basename($zipfile).'"');
		die(file_get_contents($zipfile));

		// wp_redirect( $_SERVER['HTTP_REFERER'] );
	 //    exit();
	}
}

add_action('admin_menu', array('rv_gravity_combined_export', 'create_menu'));
add_action('admin_action_rv_gravity_combined_export', array('rv_gravity_combined_export', 'process_form'));

?>
