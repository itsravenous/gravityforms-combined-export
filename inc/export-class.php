<?php
/**
 * @file Class to export entries from a gravity form to a csv file
 * @author Tom Jenkins tom@itsravenous.com
 */

require(dirname(__FILE__).'/gravity-class.php');

Class rv_gravity_export {

	public function __construct ($options) {
		// Setup DB connection
		mysql_connect($options['db']['host'], $options['db']['user'], $options['db']['password']);
		mysql_select_db($options['db']['name']);
	}
	
	public function export_entries($options) {

		$form_id = $options['form_id'];
		$date_from = !empty($options['date_from']) ? $options['date_from'] : FALSE;
		$date_to = !empty($options['date_to']) ? $options['date_to'] : FALSE;
		$form = rv_gravity::get_form($form_id);
		$fields = rv_gravity::get_form_fields($form_id);
		$entries = rv_gravity::get_form_entries($form_id, $date_from, $date_to);
		$out = $options['out'];

		$csv_rows = array();
		foreach ($entries as $entry) {
			$csv_row = array();

			foreach ($fields as $field) {
				$field = (array) $field; // When form meta stored as JSON, fields wil be objects not arrays
				// Skip separator field
				if($field['type'] == 'section' || $field['type'] == 'page') continue;

				$ids = array();
				if ($field['inputs']) {
					foreach ($field['inputs'] as &$input) {
						$input = (array) $input;
						$ids[] = $input['id'];
						$input['label'] = $field['label'] . ' (' . $input['label'] . ')';
					}
				} else {
					$ids = array($field['id']);
				}

				// Get value for field from entry
				foreach ($ids as $id) {
					$value = array_filter($entry->values, function ($val) use($id) {
						return (string) $val->field_id == (string) $id;
					});
					if (empty($value)) {
						$value = '';
					} else {
						$value = end($value);
						$value = $value->value;
					}

					$csv_row[] = $value;
				}
			}

			// Add entry meta
			$csv_row[] = $entry->created_by;
			$csv_row[] = $entry->id;
			$csv_row[] = $entry->date_created;
			$csv_row[] = $entry->source_url;
			$csv_row[] = $entry->transaction_id;
			$csv_row[] = $entry->payment_amount;
			$csv_row[] = $entry->payment_date;
			$csv_row[] = $entry->payment_status;
			$csv_row[] = $entry->post_id;
			$csv_row[] = $entry->user_agent;
			$csv_row[] = $entry->ip;

			$csv_rows[]= $csv_row;
		}
		
		// Format CSV header from fields array
		$csv_header = rv_gravity::get_form_labels_by_id($form_id);

		// Add meta fields to header
		$csv_header[] = 'Created By (User Id)';
		$csv_header[] = 'Entry Id';
		$csv_header[] = 'Entry date';
		$csv_header[] = 'Source Url';
		$csv_header[] = 'Transaction Id';
		$csv_header[] = 'Payment Amount';
		$csv_header[] = 'Payment Date';
		$csv_header[] = 'Payment Status';
		$csv_header[] = 'Post Id';
		$csv_header[] = 'User Agent';
		$csv_header[] = 'User IP';

		// Build filename

		// Write to buffer
		$output = fopen($out, 'w') or wp_die("Can't open $out");
		fputcsv($output, $csv_header);
		foreach($csv_rows as $row) {
		    fputcsv($output, $row);
		}

		// Close buffer
		fclose($output) or wp_die("Can't close $out");		
	}

}

?>