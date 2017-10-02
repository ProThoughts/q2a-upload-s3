<?php
if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../');
	exit;
}

require_once QA_PLUGIN_DIR . 'q2a-upload-s3/vendor/autoload.php';

// s3用のディレクトリ名取得
function s3_get_blob_directory($blobid)
{
	// return 's3://'. qa_opt(US3_S3_BUCKET) . '/' . substr(str_pad($blobid, 20, '0', STR_PAD_LEFT), 0, 3);
	return substr(str_pad($blobid, 20, '0', STR_PAD_LEFT), 0, 3);
}

// s3用のファイル名取得
function s3_get_blob_filename($blobid, $format)
{
	return s3_get_blob_directory($blobid).'/'.$blobid.'.'.preg_replace('/[^A-Za-z0-9]/', '', $format);
}

// S3クライアント取得
function get_s3_client()
{
	$aws_id = qa_opt(US3_AWS_ID);
	$aws_secret = qa_opt(US3_AWS_SECRET);
	$aws_s3_region = qa_opt(US3_S3_REGION);
	$aws_s3_bucket = qa_opt(US3_S3_BUCKET);
	if (!empty($aws_id) && !empty($aws_secret) && !empty($aws_s3_region) && !empty($aws_s3_bucket) ) {
		try {
			$s3 = Aws\S3\S3Client::factory(array(
				'key'    => $aws_id,
				'secret' => $aws_secret,
				'region' => $aws_s3_region,
			));

			// $s3->registerStreamWrapper();

		} catch (Aws\S3\Exception\S3Exception $e) {
			error_log('s3 wrapper failed :'. $e->getMessage());
			return null;
		}
		return $s3;
	}
	return null;
}

function qa_write_blob_file($blobid, $content, $format)
{
	$written = false;

	try {
		$s3 = get_s3_client();

		if ($s3) {

			$filename = s3_get_blob_filename($blobid, $format);

			$result = $s3->putObject(array(
				'Bucket' => qa_opt(US3_S3_BUCKET),
				'Key'    => $filename,
				'Body'   => $content,
				'ContentType' => 'image/' . $format
			));
			if ($result['ObjectURL']) {
				$written = true;
			}
		}
		$s3 = null;
	} catch (Aws\S3\Exception\S3Exception $e) {
		error_log('s3 upload failed : ' . $e->getMessage());
		$written = false;
		$s3 = null;
	}

	if (!$written) {
		// まだ書き込まれていない場合はサーバーのディレクトリに保存
		$directory = qa_get_blob_directory($blobid);
		if (is_dir($directory) || mkdir($directory, fileperms(rtrim(QA_BLOBS_DIRECTORY, '/')) & 0777)) {
			$filename = qa_get_blob_filename($blobid, $format);

			$file=fopen($filename, 'xb');
			if (is_resource($file)) {
				if (fwrite($file, $content)>=strlen($content))
					$written=true;

				fclose($file);

				if (!$written)
					unlink($filename);
			}
		}
	}

	return $written;
}

function qa_read_blob_file($blobid, $format)
{
	$contents = null;
	try {
		$s3 = get_s3_client();
		if ($s3) {
			$imgurl = qa_opt(US3_S3_IMGURL);
			$filename = s3_get_blob_filename($blobid, $format);

			// URLを指定して画像を読み込む
			$contents = file_get_contents($imgurl.$filename);

			if (!isset($contents)) {
				// 読み込み失敗時はAPIを使用する
				$result = $s3->getObject(array(
					'Bucket' => qa_opt(US3_S3_BUCKET),
					'Key'    => $filename,
				));
				if (isset($result['Body'])) {
					$contents = $result['Body'];
				}
			}

		}
		$s3 = null;
	} catch (Aws\S3\Exception\S3Exception $e) {
		error_log('s3 download failed : ' . $e->getMessage());
		error_log('file: '. $filename);
		$contents = null;
		$s3 = null;
	}

	if (isset($contents)) {
		return $contents;
	} else {
		$filename = qa_get_blob_filename($blobid, $format);
		if (is_readable($filename))
			return file_get_contents($filename);
		else
			return null;
	}
}
