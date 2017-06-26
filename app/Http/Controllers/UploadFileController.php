<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Google_Client;
use Google_Service_YouTube;
use Google_Service_YouTube_VideoSnippet;
use Google_Service_YouTube_VideoStatus;
use Google_Service_YouTube_Video;
use Google_Http_MediaFileUpload;


class UploadFileController extends Controller
{
	public function index()
	{
		$client = new Google_Client();
		$client->setAuthConfigFile(__DIR__ . '/client_secret.json');
		$client->setRedirectUri('http://' . $_SERVER['HTTP_HOST'] . '/oauth2callback');
		$client->setScopes('https://www.googleapis.com/auth/youtube');
		$client->setAccessType('offline');
		$refreshToken = env('YOUTUBE_REFRESH_TOKEN');
		$client->fetchAccessTokenWithRefreshToken($refreshToken);
		$client->getAccessToken();
		
		/*Reference URL : https://developers.google.com/youtube/v3/docs/videos/insert */

		$youtube = new Google_Service_YouTube($client);
		
		$videoPath = __DIR__ . "/Test.mp4";

	    $snippet = new Google_Service_YouTube_VideoSnippet();
	    $snippet->setTitle("Test1 title");
	    $snippet->setDescription("Test description");
	    $snippet->setTags(array("tag1", "tag2"));

	    $snippet->setCategoryId("22");

	    $status = new Google_Service_YouTube_VideoStatus();
	    $status->privacyStatus = "public";

	    $video = new Google_Service_YouTube_Video();
	    $video->setSnippet($snippet);
	    $video->setStatus($status);

	    $chunkSizeBytes = 1 * 1024 * 1024;

	    $client->setDefer(true);

	    $insertRequest = $youtube->videos->insert("status,snippet", $video);

	    $media = new Google_Http_MediaFileUpload(
	        $client,
	        $insertRequest,
	        'video/*',
	        null,
	        true,
	        $chunkSizeBytes
	    );
	    $media->setFileSize(filesize($videoPath));

	    $status = false;
	    $handle = fopen($videoPath, "rb");
	    while (!$status && !feof($handle)) {
	      $chunk = fread($handle, $chunkSizeBytes);
	      $status = $media->nextChunk($chunk);
	    }

	    fclose($handle);

	    $client->setDefer(false);

	    dd($status['id']);

	    $htmlBody .= "<h3>Video Uploaded</h3><ul>";
	    $htmlBody .= sprintf('<li>%s (%s)</li>',
	        $status['snippet']['title'],
	        $status['id']);

	    $htmlBody .= '</ul>';
	}
}
