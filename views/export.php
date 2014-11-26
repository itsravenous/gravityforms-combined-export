<style type="text/css">
	.rv-gravity-combined-export-form label {
		display: block;
		margin-bottom: 1rem;

		font-weight: bold;
	}
	.rv-gravity-combined-export-form label .label-help {
		display: block;

		color: #606060;
		font-size: 11px;
		font-weight: normal;
	}
</style>

<h2> Gravity forms combined export </h2>
<p>
	This tool allows you to export common fields from multiple forms. This instance is set up to export:
</p>
<ul>
<?php foreach (GFCEConfig::$fields as $field):?>
<li> <strong><?php echo $field;?></strong> </li>
<?php endforeach;?>
</ul>

<form class="rv-gravity-combined-export-form" action="?action=rv_gravity_combined_export" method="POST">

	<ul>
		<li>
			<label for="gf-combined-forms">
				Forms to export
				<span class="label-help">Hold <code>Ctrl</code> or <code>Cmd</code> to select multiple forms. To select all, select the first form, then hit <code>Shift</code> + <code>End</code></span>
			</label>
			<select id="gf-combined-forms" name="gf-combined-forms[]" multiple>
				<?php foreach ($forms as $form):?>
				<option value="<?php echo $form->id;?>"><?php echo $form->title;?></option>
				<?php endforeach;?>
			</select>
		</li>

		<li>
			<label for="gf-combined-date-start">Start date <span class="label-help">in <code>dd/mm/yyyy</code> format</span></label>
			<input id="gf-combined-date-start" name="gf-combined-date-start" type="date">
		</li>

		<li>
			<label for="gf-combined-date-end">End date <span class="label-help">in <code>dd/mm/yyyy</code> format</span></label>
			<input id="gf-combined-date-end" name="gf-combined-date-end" type="date">
		</li>
	</ul>

	<button type="submit">Export</button>
</form>
