<?php

class q2a_upload_s3 {

	// option's value is requested but the option has not yet been set
	function option_default($option)
	{
		switch($option) {
			case 'q2a_upload_s3_enabled':
				return 1; // true
			default:
				return null;
		}
	}

	function allow_template($template)
	{
		return ($template != 'admin');
	}

	function admin_form(&$qa_content){

		// process the admin form if admin hit Save-Changes-button
		$ok = null;
		if (qa_clicked('q2a_upload_s3_save')) {
			qa_opt('q2a_upload_s3_enabled', (bool)qa_post_text('q2a_upload_s3_enabled')); // empty or 1
			qa_opt('q2a_upload_s3_access_key_id', qa_post_text('q2a_upload_s3_access_key_id'));
			qa_opt('q2a_upload_s3_secret_access_key', qa_post_text('q2a_upload_s3_secret_access_key'));
			qa_opt('q2a_upload_s3_region', qa_post_text('q2a_upload_s3_region'));
			qa_opt('q2a_upload_s3_bucket', qa_post_text('q2a_upload_s3_bucket'));
			$ok = qa_lang('admin/options_saved');
		}

		// form fields to display frontend for admin
		$fields = array();

		$fields[] = array(
			'type' => 'checkbox',
			'label' => qa_lang('q2a_upload_s3_lang/enable_plugin'),
			'tags' => 'NAME="q2a_upload_s3_enabled"',
			'value' => qa_opt('q2a_upload_s3_enabled'),
		);

		$fields[] = array(
			'type' => 'input',
			'label' => qa_lang('q2a_upload_s3_lang/aws_access_key_id'),
			'tags' => 'name="q2a_upload_s3_access_key_id"',
			'value' => qa_opt('q2a_upload_s3_access_key_id'),
		);

		$fields[] = array(
			'type' => 'input',
			'label' => qa_lang('q2a_upload_s3_lang/aws_secret_access_key'),
			'tags' => 'name="q2a_upload_s3_secret_access_key"',
			'value' => qa_opt('q2a_upload_s3_secret_access_key'),
		);

		$s3_region = qa_opt('q2a_upload_s3_region'); // xhtml or bbcode
		$region_options = array(
			'us-east-1' => 'US East (N. Virginia)',
			'us-west-2' => 'US West (Oregon)',
			'us-west-1' => 'US West (N. California)',
			'eu-west-1' => 'EU (Ireland)',
			'eu-central-1' => 'EU (Frankfurt)',
			'ap-southeast-1' => 'Asia Pacific (Singapore)',
			'ap-northeast-1' => 'Asia Pacific (Tokyo)',
			'ap-southeast-2' => 'Asia Pacific (Sydney)',
			'ap-northeast-2' => 'Asia Pacific (Seoul)',
			'ap-south-1' => 'Asia Pacific (Mumbai)',
			'sa-east-1' => 'South America (São Paulo)',
		);
		$fields[] = array(
			'type' => 'select',
			'label' => qa_lang('q2a_upload_s3_lang/s3_region'),
			'tags' => 'name="q2a_upload_s3_region"',
			'options' => $region_options,
			'value' => $region_options[$s3_region],
		);

		$fields[] = array(
			'type' => 'input',
			'label' => qa_lang('q2a_upload_s3_lang/s3_bucket'),
			'tags' => 'name="q2a_upload_s3_bucket"',
			'value' => qa_opt('q2a_upload_s3_bucket'),
		);

		return array(
			'ok' => ($ok && !isset($error)) ? $ok : null,
			'fields' => $fields,
			'buttons' => array(
				array(
					'label' => qa_lang_html('main/save_button'),
					'tags' => 'NAME="q2a_upload_s3_save"',
				),
			),
		);
	}
}


/*
	Omit PHP closing tag to help avoid accidental output
*/
