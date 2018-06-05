<?php

/* Small class used to query a subsonic server using its REST api
 * API documentation at http://www.subsonic.org/pages/api.jsp
 */

class Subsonic
{
	protected $host;
	protected $user;
	protected $pass;
	protected $params;

	public function setHost($t)
	{
		$this->host = $t;
	}

	public function getHost()
	{
		return $this->host;
	}

	public function setUser($t)
	{
		$this->user = $t;
	}

	public function getUser()
	{
		return $this->user;
	}

	public function setPass($t)
	{
		$this->pass = $t;
	}

	public function getPass()
	{
		return $this->pass;
	}

	/*
	 * Returns a minimal set of parameters needed on all queries
	 */
	protected function getBaseParams()
	{
		static $salt;
		if($salt === null)
			$salt = bin2hex(random_bytes(5));

		return [
			'u' => $this->user,
			't' => md5($this->pass . $salt),
			's' => $salt,
			'f' => 'json',
			'v' => '1.16.0',
			'c' => 'uam',
		];
	}

	/*
	 * Returns a full url from the base url and parameters
	 */
	protected function generateUrlString($urn, $additionalParams)
	{
		$url = $this->host . $urn;
		$fp = true;

		$params = array_merge($this->getBaseParams(), $additionalParams);
		foreach ($params as $k => $v) {
			if($fp)
			{
				$url .= '?';
				$fp = false;
			} else {
				$url .= '&';

			}
			$url .= urlencode($k) . '=' . urlencode($v);
		}

		return $url;
	}

	/*
	 * Executes a request using curl and returns the subsonic-response object
	 */
	protected function makeRequest($url)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_URL, $url);
		$result = curl_exec($ch);
		curl_close($ch);

		$obj = json_decode($result, true);
		if($obj === null
			|| !is_array($obj)
			|| !array_key_exists('subsonic-response', $obj)
			|| !array_key_exists('status', $obj['subsonic-response'])
			|| $obj['subsonic-response']['status'] != 'ok')
			return null;

		return $obj['subsonic-response'];
	}

	/*
	 * Returns the stream url for a song id
	 */
	public function apiStream($songId)
	{
		return $this->generateUrlString(
			'/rest/stream',
			[
				'id' => $songId,
			]);
	}

	/*
	 * Returns the cover art url for a song id
	 */
	public function apiGetCoverArt($songId)
	{
		return $this->generateUrlString(
			'/rest/getCoverArt',
			[
				'id' => $songId,
			]);
	}

	/*
	 * Returns the song object for a song id
	 */
	public function apiGetSong($songId)
	{
		$url = $this->generateUrlString(
			'/rest/getSong',
			[
				'id' => $songId,
			]);

		$obj = $this->makeRequest($url);
		if($obj === null
			|| !array_key_exists('song', $obj)
			|| !is_array($obj['song']))
			return null;

		return $obj['song'];
	}

	/*
	 * Returns a music directory object from a directory id
	 */
	public function apiMusicDirectory($parentId)
	{
		$url = $this->generateUrlString(
			'/rest/getMusicDirectory',
			[
				'id' => $parentId,
			]);

		$obj = $this->makeRequest($url);
		if($obj === null
			|| !array_key_exists('directory', $obj)
			|| !is_array($obj['directory']))
			return null;

		return $obj['directory'];
	}

	/*
	 * Searches songs by title and artist
	 * Returns an array of songs, by default with a single item
	 */
	public function apiSearch($song, $artist, $count = 1)
	{
		$url = $this->generateUrlString(
			'/rest/search',
			[
				'artist' => $artist,
				'title' => $song,
				'count' => $count,
			]);

		$obj = $this->makeRequest($url);
		if($obj === null
			|| !array_key_exists('searchResult', $obj)
			|| !is_array($obj['searchResult']))
			return array();

		$obj = $obj['searchResult'];

		if(!array_key_exists('match', $obj))
			return array();

		$rets = [];
		foreach($obj['match'] as $i)
		{
			if($i['isDir'] == true)
				continue;

			$i['url'] = $this->apiStream($i['id']);
			$i['coverUrl'] = $this->apiGetCoverArt($i['id']);
			$rets[] = $i;
		}

		return $rets;
	}

	/*
	 * Looks up and returns the "next song" from a song id
	 */
	public function findNextSong($songId)
	{
		$curSong = $this->apiGetSong($songId);
		if($curSong === null || !array_key_exists('parent', $curSong))
			return null;

		$directory = $this->apiMusicDirectory($curSong['parent']);
		if($directory === null || !array_key_exists('child', $directory))
			return null;

		$oldFound = false;
		foreach($directory['child'] as $i)
		{
			if($i['isDir'] == true)
				continue;

			if($oldFound)
			{
				$i['url'] = $this->apiStream($i['id']);
				$i['coverUrl'] = $this->apiGetCoverArt($i['id']);
				return $i;
			}

			if($i['id'] == $songId)
				$oldFound = true;
		}
	}
}

