<?php

class VodPackagerDeliveryUtils
{
	protected static function generateMultiUrl(array $flavors, entry $entry)
	{
		$urls = array();
		foreach ($flavors as $flavor)
		{
			$urls[] = $flavor['url'];
		}
		$urls = array_unique($urls);
	
		if (count($urls) == 1)
		{
			$baseUrl = reset($urls);
			return '/' . ltrim($baseUrl, '/');
		}
	
		$prefix = kString::getCommonPrefix($urls);
		$postfix = kString::getCommonPostfix($urls);
		
		if ($entry->getType() == entryType::PLAYLIST)
		{
			// in case of a playlist, need to merge the flavor params of the urls
			// instead of using a urlset, since nginx-vod does not support urlsets of 
			// non-trivial mapping responses.
			 
			// so instead of building:
			//		/p/123/serveFlavor/entryId/0_abc/flavorParamIds/100,1,2,3,/forceproxy/true/name/a.mp4.urlset
			// we build:
			//		/p/123/serveFlavor/entryId/0_abc/flavorParamIds/1001,1002,1003/forceproxy/true/name/a.mp4.urlset
			$prefix = substr($prefix, 0, strrpos($prefix, '/') + 1);
			$postfix = substr($postfix, strpos($postfix, '/'));
		}
		
		$prefixLen = strlen($prefix);
		$postfixLen = strlen($postfix);
		$middlePart = ',';
		foreach ($urls as $url)
		{
			$middlePart .= substr($url, $prefixLen, strlen($url) - $prefixLen - $postfixLen) . ',';
		}
		
		if ($entry->getType() == entryType::PLAYLIST &&
			strpos($middlePart, '/') === false)
		{
			$middlePart = rtrim(ltrim($middlePart, ','), ',');
			$result = $prefix . $middlePart . $postfix;
		}
		else
		{
			$result = $prefix . $middlePart . $postfix . '.urlset';
		}
	
		return '/' . ltrim($result, '/');
	}
	
	public static function getVodPackagerUrl($flavors, $urlPrefix, $urlSuffix, DeliveryProfileDynamicAttributes $params)
	{
		$entry = entryPeer::retrieveByPK($params->getEntryId());
		
		$url = self::generateMultiUrl($flavors, $entry);
		$url .= $urlSuffix;
	
		// move any folders on the url prefix to the url part, so that the protocol folder will always be first
		$urlPrefixWithProtocol = $urlPrefix;
		if (strpos($urlPrefix, '://') === false)
			$urlPrefixWithProtocol = 'http://' . $urlPrefix;
	
		$urlPrefixPath = parse_url($urlPrefixWithProtocol, PHP_URL_PATH);
		if ($urlPrefixPath && substr($urlPrefix, -strlen($urlPrefixPath)) == $urlPrefixPath)
		{
			$urlPrefix = substr($urlPrefix, 0, -strlen($urlPrefixPath));
			$url = rtrim($urlPrefixPath, '/') . '/' . ltrim($url, '/');
		}
	
		$urlPrefix = trim(preg_replace('#https?://#', '', $urlPrefix), '/');
		$urlPrefix = $params->getMediaProtocol() . '://' . $urlPrefix;
	
		return array('url' => $url, 'urlPrefix' => $urlPrefix);
	}
	
	public static function getExtraParams(DeliveryProfileDynamicAttributes $params) {
	
		$result = '';
		
		$seekStart = $params->getSeekFromTime();
		if($seekStart > 0) 
		{
			$result .= '/clipFrom/' . $seekStart;
		}
			
		$seekEnd = $params->getClipTo();
		if($seekEnd) 
		{
			$result .= '/clipTo/' . $seekEnd;
		}
		
		$playbackRate = $params->getPlaybackRate();
		if($playbackRate) 
		{
			$result .= '/speed/' . $playbackRate;
		}
	
		return $result;
	}
}
